<?php
// Arquivo: php/api/register.php (Alistamento de Soldados / Criação de Usuário)
require_once '../config/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJSON(["status" => "error", "message" => "Método não permitido."], 405);
}

// 1. Coleta e Sanitização de Entradas
$nome          = htmlspecialchars(trim($_POST['nome'] ?? ''), ENT_QUOTES, 'UTF-8');
$sobrenome     = htmlspecialchars(trim($_POST['sobrenome'] ?? ''), ENT_QUOTES, 'UTF-8');
$nickname      = htmlspecialchars(trim($_POST['nickname'] ?? ''), ENT_QUOTES, 'UTF-8');
$email         = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$email_contato = filter_input(INPUT_POST, 'email_contato', FILTER_SANITIZE_EMAIL);
$telefone      = htmlspecialchars(trim($_POST['telefone'] ?? ''), ENT_QUOTES, 'UTF-8');
$senha         = $_POST['senha'] ?? '';

// 2. Validações de Campos Obrigatórios e Formato
if (empty($nome) || empty($sobrenome) || empty($nickname) || empty($email) || empty($senha)) {
    responderJSON(["status" => "error", "message" => "Campos obrigatórios não preenchidos."], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderJSON(["status" => "error", "message" => "Formato de e-mail inválido."], 400);
}

// 3. Regras de Domínio e Segurança de E-mail
$partesEmail = explode('@', $email);
$dominio     = strtolower(end($partesEmail));

if ($dominio === 'wtwiki.com') {
    responderJSON([
        "status"  => "error",
        "message" => "Acesso restrito. Contas de Comando devem ser geradas internamente."
    ], 403);
}

$errosComuns = [
    'gamil.com', 'gmaill.com', 'gmai.com', 'gmail.com.br', 
    'gmeil.com', 'hotmial.com', 'hotmai.com', 'hotmmail.com', 
    'outlok.com', 'yaho.com'
];

if (in_array($dominio, $errosComuns)) {
    responderJSON([
        "status"  => "error",
        "message" => "Domínio suspeito de erro de digitação. Verifique se digitou corretamente."
    ], 400);
}

if (!checkdnsrr($dominio, 'MX')) {
    responderJSON([
        "status"  => "error",
        "message" => "O servidor deste e-mail é inválido ou inexistente."
    ], 400);
}

// 4. Persistência e Integridade na Base de Dados
try {
    $database = new Database();
    $db = $database->getConnection();

    // Verificação de Unicidade (E-mail ou Nickname duplicados)
    $check_query = "SELECT id FROM usuarios WHERE email = :email OR nickname = :nickname LIMIT 1";
    $check_stmt  = $db->prepare($check_query);
    $check_stmt->execute([
        ':email'    => $email,
        ':nickname' => $nickname
    ]);

    if ($check_stmt->rowCount() > 0) {
        responderJSON([
            "status"  => "error",
            "message" => "Este E-mail ou Nickname já está em uso nas nossas fileiras."
        ], 409);
    }

    // Hash criptográfico irreversível (Bcrypt) e perfil inicial
    $senha_hash    = password_hash($senha, PASSWORD_DEFAULT);
    $perfil_padrao = 'visitante';

    $insert_query = "INSERT INTO usuarios (nome, sobrenome, nickname, email, email_contato, telefone, senha_hash, perfil) 
                     VALUES (:nome, :sobrenome, :nickname, :email, :email_contato, :telefone, :senha_hash, :perfil)";
    
    $stmt = $db->prepare($insert_query);
    $executou = $stmt->execute([
        ':nome'          => $nome,
        ':sobrenome'     => $sobrenome,
        ':nickname'      => $nickname,
        ':email'         => $email,
        ':email_contato' => $email_contato,
        ':telefone'      => $telefone,
        ':senha_hash'    => $senha_hash,
        ':perfil'        => $perfil_padrao
    ]);

    if ($executou) {
        $novoUsuarioId = $db->lastInsertId();

        // Observabilidade: Log de criação de entidade com carimbo de tempo
        registrarLogApp("Criação de entidade: Novo recruta alistado '{$nickname}' ({$email}) - ID: {$novoUsuarioId}", "ENTITY_CREATE");

        responderJSON([
            "status"  => "success",
            "message" => "Alistamento concluído com sucesso."
        ], 201);
    } else {
        throw new Exception("Falha ao persistir registro na base de dados.");
    }

} catch (Exception $e) {
    // Observabilidade: Registro técnico de erro interno
    registrarLogApp("Erro interno (register.php): " . $e->getMessage(), "ERROR");

    responderJSON([
        "status"  => "error",
        "message" => "Erro interno no servidor ao processar o alistamento."
    ], 500);
}
?>