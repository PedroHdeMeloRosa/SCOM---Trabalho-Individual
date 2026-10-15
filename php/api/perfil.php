<?php
// Arquivo: php/api/perfil.php
ob_start();
ini_set('display_errors', 0);
session_start();
require_once '../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['user_id'])) {
    ob_clean(); http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Não autenticado."]); exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

// ROTA GET: Buscar dados para preencher o formulário
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $acao === 'get') {
    $stmt = $db->prepare("SELECT nome, sobrenome, nickname, email_contato, telefone FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $dados = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(["status" => "success", "dados" => $dados]);
    exit;
}

// PROTEÇÃO CSRF PARA MÉTODOS POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        ob_clean(); http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Token CSRF inválido."]); exit;
    }

    // Busca a senha atual do banco para verificação
    $stmt = $db->prepare("SELECT senha_hash FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $userDB = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $senha_atual_fornecida = $_POST['senha_atual'] ?? '';
    if (!password_verify($senha_atual_fornecida, $userDB['senha_hash'])) {
        ob_clean(); http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Autorização negada: A senha atual está incorreta."]); exit;
    }

    // ATUALIZAR DADOS
    if ($acao === 'update_dados') {
        $nome = htmlspecialchars(trim($_POST['nome'] ?? ''), ENT_QUOTES, 'UTF-8');
        $sobrenome = htmlspecialchars(trim($_POST['sobrenome'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email_contato = filter_input(INPUT_POST, 'email_contato', FILTER_SANITIZE_EMAIL);
        $telefone = htmlspecialchars(trim($_POST['telefone'] ?? ''), ENT_QUOTES, 'UTF-8');

        $update = $db->prepare("UPDATE usuarios SET nome = :n, sobrenome = :s, email_contato = :ec, telefone = :t WHERE id = :id");
        $update->execute([':n' => $nome, ':s' => $sobrenome, ':ec' => $email_contato, ':t' => $telefone, ':id' => $user_id]);
        
        // Atualiza a sessão
        $_SESSION['user_nome'] = $nome;

        echo json_encode(["status" => "success", "message" => "Dossiê pessoal atualizado com sucesso."]);
        exit;
    }

    // ALTERAR SENHA
    if ($acao === 'update_senha') {
        $nova_senha = $_POST['nova_senha'] ?? '';
        if(strlen($nova_senha) < 8) {
            echo json_encode(["status" => "error", "message" => "A nova chave deve ter pelo menos 8 caracteres."]); exit;
        }

        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE usuarios SET senha_hash = :hash WHERE id = :id");
        $update->execute([':hash' => $hash, ':id' => $user_id]);

        echo json_encode(["status" => "success", "message" => "Credencial tática alterada com sucesso!"]);
        exit;
    }
}
?>