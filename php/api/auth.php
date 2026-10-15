<?php 
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
require_once '../config/database.php';

header("Content-Type: application/json; charset=UTF-8");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains"); // Força o uso de HTTPS

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "E-mail e senha são obrigatórios."]);
    exit;
}

$log_dir = __DIR__ . '/../../logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0777, true);
}
$log_file = $log_dir . '/app.log';

try {
    // Adicionamos tentativas_falhas e bloqueado_ate na busca
    $query = "SELECT id, nome, nickname, senha_hash, perfil, status, nacao_favorita, tentativas_falhas, bloqueado_ate FROM usuarios WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['status'] !== 'ativo') {
            @error_log(date('Y-m-d H:i:s') . " - Login Bloqueado (Status Inativo): " . $email . "\n", 3, $log_file);
            ob_clean();
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Acesso negado. Conta inativa ou suspensa."]);
            exit;
        }

        // VERIFICA SE O USUÁRIO ESTÁ NO PERÍODO DE BLOQUEIO DE 15 MIN
        if ($row['bloqueado_ate']) {
            $tempoBloqueio = strtotime($row['bloqueado_ate']);
            $agora = time();
            
            if ($tempoBloqueio > $agora) {
                // Ainda está bloqueado. Calcula os minutos restantes
                $minutosRestantes = ceil(($tempoBloqueio - $agora) / 60);
                
                ob_clean();
                http_response_code(403);
                echo json_encode([
                    "status" => "blocked", 
                    "minutos_restantes" => $minutosRestantes
                ]);
                exit;
            } else {
                // O tempo de bloqueio já passou. Zera as tentativas para ele tentar de novo
                $db->prepare("UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = :id")->execute([':id' => $row['id']]);
                $row['tentativas_falhas'] = 0;
            }
        }

        // VALIDA A SENHA
        if (password_verify($senha, $row['senha_hash'])) {
            // Se logou com sucesso, garante que as tentativas fiquem zeradas

           session_regenerate_id(true); // <--- Essa linha mágica impede o sequestro de sessão
            
            $db->prepare("UPDATE usuarios SET tentativas_falhas = 0, bloqueado_ate = NULL, ultimo_login = CURRENT_TIMESTAMP WHERE id = :id")->execute([':id' => $row['id']]);

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_nome'] = $row['nome'];
            $_SESSION['user_nickname'] = $row['nickname'];
            $_SESSION['user_perfil'] = $row['perfil'];
            $_SESSION['user_nacao'] = $row['nacao_favorita'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            @error_log(date('Y-m-d H:i:s') . " - Login Sucesso: " . $row['nickname'] . " (" . $email . ")\n", 3, $log_file);
            
            ob_clean();
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Acesso autorizado, Comandante.",
                "perfil" => $row['perfil'],
                "nickname" => $row['nickname'],
                "nacao_favorita" => $row['nacao_favorita'],
                "csrf_token" => $_SESSION['csrf_token']
            ]);
            exit;
            
        } else {
            // SENHA INCORRETA (Aplica o strike)
            $tentativas = (int)$row['tentativas_falhas'] + 1;
            $bloqueio = null;
            $minutosRestantes = 0;

            if ($tentativas >= 5) {
                $bloqueio = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $minutosRestantes = 15;
                $tentativas = 0; // Zera as tentativas para o próximo ciclo após os 15 min
            }

            $stmtUpdate = $db->prepare("UPDATE usuarios SET tentativas_falhas = :t, bloqueado_ate = :b WHERE id = :id");
            $stmtUpdate->execute([':t' => $tentativas, ':b' => $bloqueio, ':id' => $row['id']]);

            @error_log(date('Y-m-d H:i:s') . " - Falha de Login ($tentativas/5): " . $email . "\n", 3, $log_file);
            
            ob_clean();
            http_response_code(401);
            
            if ($bloqueio) {
                // Se acabou de bloquear no 5º erro
                echo json_encode(["status" => "blocked", "minutos_restantes" => $minutosRestantes]);
            } else {
                // Avisa quantas tentativas restam
                $restantes = 5 - $tentativas;
                echo json_encode(["status" => "error", "message" => "Credenciais inválidas. Restam $restantes tentativa(s)."]);
            }
            exit;
        }
    }

    // E-mail não encontrado no banco
    @error_log(date('Y-m-d H:i:s') . " - Falha de Login (Não existe): " . $email . "\n", 3, $log_file);
    ob_clean();
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Credenciais inválidas."]);
    exit;

} catch (PDOException $e) {
    @error_log(date('Y-m-d H:i:s') . " - DB Error: " . $e->getMessage() . "\n", 3, $log_file);
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Falha interna de comunicação com a Base de Dados."]);
    exit;
}
?>