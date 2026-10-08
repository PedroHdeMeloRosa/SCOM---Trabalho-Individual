<?php
// Arquivo: php/api/register.php
require_once '../config/database.php';

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$log_file = '../../logs/app.log';

// Sanitização obrigatória de todos os inputs externos
$nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
$sobrenome = filter_input(INPUT_POST, 'sobrenome', FILTER_SANITIZE_STRING);
$nickname = filter_input(INPUT_POST, 'nickname', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
// Extrai o domínio do e-mail (ex: gmail.com)
$dominio = substr(strrchr($email, "@"), 1);

// Regra 1: Bloqueia domínios internos (Apenas Admins podem criar via painel/SQL)
if ($dominio === 'wtwiki.com') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Acesso restrito. Contas de Comando devem ser geradas internamente."]);
    exit;
}

// Regra 2: Validação de Sanidade (DNS MX)
if (!checkdnsrr($dominio, 'MX')) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "O servidor deste e-mail é inválido ou inexistente."]);
    exit;
}

$email_contato = filter_input(INPUT_POST, 'email_contato', FILTER_SANITIZE_EMAIL);
$telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
$senha = $_POST['senha'] ?? '';

// Validação de campos obrigatórios
if (empty($nome) || empty($sobrenome) || empty($nickname) || empty($email) || empty($senha)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Campos obrigatórios não preenchidos."]);
    exit;
}

try {
    // 1. Verificação de Duplicidade: Garante que as constraints UNIQUE do banco não vão quebrar
    $check_query = "SELECT id FROM usuarios WHERE email = :email OR nickname = :nickname LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->bindParam(':nickname', $nickname);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        http_response_code(409); // Conflict
        echo json_encode(["status" => "error", "message" => "Este E-mail ou Nickname já está em uso nas nossas fileiras."]);
        exit;
    }

    // 2. Criptografia Segura da Chave
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $perfil_padrao = 'visitante'; // Todo novo recruta começa sem privilégios de Admin

    // 3. Inserção na Base de Dados usando Prepared Statements (Proteção contra SQL Injection)
    $insert_query = "INSERT INTO usuarios (nome, sobrenome, nickname, email, email_contato, telefone, senha_hash, perfil) 
                     VALUES (:nome, :sobrenome, :nickname, :email, :email_contato, :telefone, :senha_hash, :perfil)";
    
    $stmt = $db->prepare($insert_query);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':sobrenome', $sobrenome);
    $stmt->bindParam(':nickname', $nickname);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':email_contato', $email_contato);
    $stmt->bindParam(':telefone', $telefone);
    $stmt->bindParam(':senha_hash', $senha_hash);
    $stmt->bindParam(':perfil', $perfil_padrao);

    if ($stmt->execute()) {
        error_log(date('Y-m-d H:i:s') . " - Novo recruta alistado: " . $nickname . " (" . $email . ")\n", 3, $log_file);
        
        http_response_code(201); // Created
        echo json_encode(["status" => "success", "message" => "Alistamento concluído com sucesso."]);
    } else {
        throw new Exception("Falha ao executar a inserção.");
    }

} catch(PDOException $e) {
    http_response_code(500);
    error_log(date('Y-m-d H:i:s') . " - Erro Interno (Registo): " . $e->getMessage() . "\n", 3, $log_file);
    echo json_encode(["status" => "error", "message" => "Erro interno no servidor."]);
}
?>