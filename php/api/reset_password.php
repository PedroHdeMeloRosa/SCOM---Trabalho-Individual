<?php
// Arquivo: php/api/reset_password.php
ob_start();
ini_set('display_errors', 0);
require_once '../config/database.php';

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método não permitido."]);
    exit;
}

$token = filter_input(INPUT_POST, 'token', FILTER_DEFAULT);
$nova_senha = $_POST['nova_senha'] ?? '';

if (empty($token) || empty($nova_senha)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Token e nova senha são obrigatórios."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verifica token válido e não expirado
    $query = "SELECT id FROM usuarios WHERE token_recuperacao = :token AND token_expira > NOW() LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        // Atualiza a senha e inutiliza o token
        $update = $db->prepare("UPDATE usuarios SET senha_hash = :hash, token_recuperacao = NULL, token_expira = NULL WHERE id = :id");
        $update->bindParam(':hash', $novo_hash);
        $update->bindParam(':id', $user['id']);
        $update->execute();

        ob_clean();
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Chave de acesso redefinida com sucesso."]);
        exit;
    }

    ob_clean();
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Token inválido ou expirado."]);
    exit;

} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro ao redefinir a chave."]);
    exit;
}