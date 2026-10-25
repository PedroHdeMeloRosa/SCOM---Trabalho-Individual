<?php 
// Arquivo: php/api/auth.php (Autenticação, Criptografia Bcrypt e Rate Limiting)
require_once '../config/bootstrap.php';

$metodo = $_SERVER['REQUEST_METHOD'];
if ($metodo !== 'POST') {
    responderJSON(["status" => "error", "message" => "Método não permitido."], 405);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        responderJSON(["status" => "error", "message" => "E-mail e senha são obrigatórios."], 400);
    }

    $query = "SELECT id, nome, nickname, senha_hash, perfil, status, nacao_favorita, tentativas_falhas, bloqueado_ate 
              FROM usuarios 
              WHERE email = :email 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch();

    // Mensagem uniforme para prevenir Enumeração de Usuários (User Enumeration)
    $mensagemErroGenerica = "As credenciais informadas estão incorretas.";

    if ($row) {
        if ($row['status'] !== 'ativo') {
            registrarLogApp("Tentativa de login em conta inativa/suspensa: " . $email, "AUTH_FAIL");
            responderJSON(["status" => "error", "message" => "Acesso negado. Conta inativa ou suspensa."], 403);
        }

        // 1. Verifica se a conta está sob bloqueio temporário (15 minutos)
        if ($row['bloqueado_ate']) {
            $tempoBloqueio = strtotime($row['bloqueado_ate']);
            $agora = time();
            
            if ($tempoBloqueio > $agora) {
                $minutosRestantes = ceil(($tempoBloqueio - $agora) / 60);
                registrarLogApp("Tentativa de autenticação barrada em conta bloqueada: {$email} ({$minutosRestantes} min restantes)", "AUTH_LOCK");
                responderJSON(["status" => "blocked", "minutos_restantes" => $minutosRestantes], 403);
            } else {
                // Período expirado: reseta as falhas e remove o bloqueio
                $db->prepare("UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = :id")
                   ->execute([':id' => $row['id']]);
                $row['tentativas_falhas'] = 0;
            }
        }

        // 2. Validação da Chave Criptográfica (Bcrypt)
        if (password_verify($senha, $row['senha_hash'])) {
            session_regenerate_id(true); // Prevenção contra Fixação/Sequestro de Sessão

            // Observabilidade: Login bem-sucedido
            registrarLogApp("Login bem-sucedido: " . $email . " (Perfil: " . $row['perfil'] . ")", "AUTH_SUCCESS");
            
            $db->prepare("UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL, ultimo_login = CURRENT_TIMESTAMP WHERE id = :id")
               ->execute([':id' => $row['id']]);

            $_SESSION['user_id']        = $row['id'];
            $_SESSION['user_nome']      = $row['nome'];
            $_SESSION['user_nickname']  = $row['nickname'];
            $_SESSION['user_perfil']    = $row['perfil'];
            $_SESSION['user_nacao']     = $row['nacao_favorita'];
            $_SESSION['csrf_token']     = bin2hex(random_bytes(32));

            responderJSON([
                "status"         => "success",
                "message"        => "Acesso autorizado, Comandante.",
                "perfil"         => $row['perfil'],
                "nickname"       => $row['nickname'],
                "nacao_favorita" => $row['nacao_favorita'],
                "csrf_token"     => $_SESSION['csrf_token']
            ], 200);
            
        } else {
            // 3. Senha incorreta: Contabilização de falhas (Rate Limiting)
            $tentativas = (int)$row['tentativas_falhas'] + 1;
            $bloqueio = null;
            $minutosRestantes = 0;

            registrarLogApp("Falha de autenticação: Senha incorreta para o e-mail " . $email . " (Strike: {$tentativas})", "AUTH_FAIL");

            if ($tentativas >= 5) {
                $bloqueio = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $minutosRestantes = 15;
                $tentativas = 0;
                registrarLogApp("Limite de tentativas excedido. Conta bloqueada por 15 minutos: " . $email, "AUTH_LOCK");
            }

            $stmtUpdate = $db->prepare("UPDATE usuarios SET tentativas_falhas = :t, bloqueado_ate = :b WHERE id = :id");
            $stmtUpdate->execute([':t' => $tentativas, ':b' => $bloqueio, ':id' => $row['id']]);

            if ($bloqueio) {
                responderJSON(["status" => "blocked", "minutos_restantes" => $minutosRestantes], 401);
            } else {
                responderJSON(["status" => "error", "message" => $mensagemErroGenerica], 401);
            }
        }
    } else {
        // Observabilidade: Tentativa com e-mail inexistente
        registrarLogApp("Falha de autenticação: E-mail não registrado tentado: " . $email, "AUTH_FAIL");

        // Atraso artificial para equiparar a latência da resposta ao hash Bcrypt
        usleep(rand(100000, 300000));
        responderJSON(["status" => "error", "message" => $mensagemErroGenerica], 401);
    }

} catch (Exception $e) {
    // Observabilidade: Captura do erro interno de SQL/conexão antes da resposta
    registrarLogApp("Erro interno (auth.php): " . $e->getMessage(), "ERROR");

    responderJSON(["status" => "error", "message" => "Falha interna de comunicação com a Base de Dados."], 500);
}
?>