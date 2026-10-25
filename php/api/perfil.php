<?php
// Arquivo: php/api/perfil.php (Gestão de Dossiê Pessoal e Redefinição de Credencial)
require_once '../config/bootstrap.php';

$user_id = exigirLogin();
$acao    = $_POST['acao'] ?? $_GET['acao'] ?? '';
$metodo  = $_SERVER['REQUEST_METHOD'];

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Consulta do Dossiê Pessoal (GET)
    if ($metodo === 'GET' && $acao === 'get') {
        $stmt = $db->prepare("SELECT nome, sobrenome, nickname, email, email_contato, telefone, perfil, nacao_favorita, criado_em FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $user_id]);
        $dados = $stmt->fetch();

        responderJSON([
            "status" => "success",
            "dados"  => $dados
        ]);
    }

    // 2. Operações Mutantes de Perfil e Credenciais (POST)
    if ($metodo === 'POST') {
        validarCSRF();

        // Validação da Chave Atual antes de autorizar qualquer mutação de dados sensíveis
        $stmt = $db->prepare("SELECT senha_hash, nickname, email FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $user_id]);
        $userDB = $stmt->fetch();

        $senha_atual_fornecida = $_POST['senha_atual'] ?? '';

        if (!$userDB || !password_verify($senha_atual_fornecida, $userDB['senha_hash'])) {
            registrarLogApp("Falha de autenticação: Senha incorreta informada na alteração de perfil do soldado ID {$user_id}", "AUTH_FAIL");
            responderJSON(["status" => "error", "message" => "Autorização negada: A senha atual está incorreta."], 401);
        }

        // Ação A: Atualização de Dados Cadastrais
        if ($acao === 'update_dados') {
            $nome          = htmlspecialchars(trim($_POST['nome'] ?? ''), ENT_QUOTES, 'UTF-8');
            $sobrenome     = htmlspecialchars(trim($_POST['sobrenome'] ?? ''), ENT_QUOTES, 'UTF-8');
            $email_contato = filter_input(INPUT_POST, 'email_contato', FILTER_SANITIZE_EMAIL);
            $telefone      = htmlspecialchars(trim($_POST['telefone'] ?? ''), ENT_QUOTES, 'UTF-8');

            if (empty($nome) || empty($sobrenome)) {
                responderJSON(["status" => "error", "message" => "Nome e Sobrenome são obrigatórios."], 400);
            }

            $update = $db->prepare("UPDATE usuarios SET nome = :n, sobrenome = :s, email_contato = :ec, telefone = :t WHERE id = :id");
            $update->execute([
                ':n'  => $nome,
                ':s'  => $sobrenome,
                ':ec' => $email_contato,
                ':t'  => $telefone,
                ':id' => $user_id
            ]);

            $_SESSION['user_nome'] = $nome;

            registrarLogApp("Atualização de dossiê: Soldado {$userDB['nickname']} (ID: {$user_id}) atualizou suas informações pessoais", "USER_ACTION");

            responderJSON([
                "status"  => "success",
                "message" => "Dossiê pessoal atualizado com sucesso."
            ]);
        }

        // Ação B: Redefinição de Chave Criptográfica (Senha)
        if ($acao === 'update_senha') {
            $nova_senha = $_POST['nova_senha'] ?? '';

            if (strlen($nova_senha) < 8) {
                responderJSON(["status" => "error", "message" => "A nova chave deve ter pelo menos 8 caracteres."], 400);
            }

            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $update = $db->prepare("UPDATE usuarios SET senha_hash = :hash WHERE id = :id");
            $update->execute([
                ':hash' => $hash,
                ':id'   => $user_id
            ]);

            registrarLogApp("Segurança: Soldado {$userDB['nickname']} (ID: {$user_id}) redefiniu com sucesso sua chave criptográfica", "AUTH_SECURITY");

            responderJSON([
                "status"  => "success",
                "message" => "Credencial tática alterada com sucesso!"
            ]);
        }
    }

    responderJSON(["status" => "error", "message" => "Ação inválida."], 400);

} catch (Exception $e) {
    registrarLogApp("Erro interno (perfil.php): " . $e->getMessage(), "ERROR");
    responderJSON(["status" => "error", "message" => "Erro interno ao processar o perfil."], 500);
}
?>