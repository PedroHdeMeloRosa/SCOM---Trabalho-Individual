<?php
// Arquivo: php/api/register.php
require_once '../config/database.php';

header("Content-Type: application/json; charset=UTF-8");

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains"); // Força o uso de HTTPS

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$log_file = '../../logs/app.log';

// -------------------------------------------------------------------
// PASSO 3: HIGIENIZAÇÃO (SANITIZAÇÃO) CONTRA XSS REFLETIDO/ARMAZENADO
// O htmlspecialchars converte tags <script> em texto puro (&lt;script&gt;)
// -------------------------------------------------------------------
$nome = htmlspecialchars(trim($_POST['nome'] ?? ''), ENT_QUOTES, 'UTF-8');
$sobrenome = htmlspecialchars(trim($_POST['sobrenome'] ?? ''), ENT_QUOTES, 'UTF-8');
$nickname = htmlspecialchars(trim($_POST['nickname'] ?? ''), ENT_QUOTES, 'UTF-8');
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$email_contato = filter_input(INPUT_POST, 'email_contato', FILTER_SANITIZE_EMAIL);
$telefone = htmlspecialchars(trim($_POST['telefone'] ?? ''), ENT_QUOTES, 'UTF-8');
$senha = $_POST['senha'] ?? '';

// Validação estrita de formato de E-mail
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Formato de e-mail inválido."]);
    exit;
}

$partesEmail = explode('@', $email);
$dominio = strtolower(end($partesEmail));

// Bloqueio de Domínio Restrito (wtwiki.com)
if ($dominio === 'wtwiki.com') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Acesso restrito. Contas de Comando devem ser geradas internamente."]);
    exit;
}

// Detecção de erros de digitação (Typos)
$errosComuns = [
    'gamil.com', 'gmaill.com', 'gmai.com', 'gmail.com.br', 'gmeil.com',
    'hotmial.com', 'hotmai.com', 'hotmmail.com', 'outlok.com', 'yaho.com'
];

if (in_array($dominio, $errosComuns)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Domínio suspeito de erro de digitação. Verifique se digitou corretamente."]);
    exit;
}

// Validação de DNS do domínio de email (Sanidade)
if (!checkdnsrr($dominio, 'MX')) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "O servidor deste e-mail é inválido ou inexistente."]);
    exit;
}

// Validação de campos obrigatórios
if (empty($nome) || empty($sobrenome) || empty($nickname) || empty($email) || empty($senha)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Campos obrigatórios não preenchidos."]);
    exit;
}

try {
    // Verificação de Duplicidade
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

    // Criptografia Segura
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $perfil_padrao = 'visitante';

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
        @error_log(date('Y-m-d H:i:s') . " - Novo recruta alistado: " . $nickname . " (" . $email . ")\n", 3, $log_file);
        
        http_response_code(201); // Created
        echo json_encode(["status" => "success", "message" => "Alistamento concluído com sucesso."]);
    } else {
        throw new Exception("Falha ao executar a inserção.");
    }

} catch(PDOException $e) {
    http_response_code(500);
    @error_log(date('Y-m-d H:i:s') . " - Erro Interno (Registo): " . $e->getMessage() . "\n", 3, $log_file);
    echo json_encode(["status" => "error", "message" => "Erro interno no servidor."]);
}
?>