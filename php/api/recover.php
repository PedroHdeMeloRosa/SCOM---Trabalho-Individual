<?php
// Arquivo: php/api/recover.php
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

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

if (empty($email)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Informe o e-mail cadastrado."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT id, nickname FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Gera token seguro e define validade para 15 minutos
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $update = $db->prepare("UPDATE usuarios SET token_recuperacao = :token, token_expira = :expira WHERE id = :id");
        $update->bindParam(':token', $token);
        $update->bindParam(':expira', $expira);
        $update->bindParam(':id', $user['id']);
        $update->execute();

        ob_clean();
        http_response_code(200);
        // Em ambiente localhost (sem servidor SMTP externo), retornamos o token para teste direto
        echo json_encode([
            "status" => "success",
            "message" => "Chave de redefinição emitida com sucesso.",
            "token_teste" => $token
        ]);
        exit;
    }

    // Resposta genérica para evitar enumeração de contas
    ob_clean();
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Se o e-mail estiver registrado, as instruções foram despachadas."
    ]);
    exit;

} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro ao processar solicitação."]);
    exit;
}