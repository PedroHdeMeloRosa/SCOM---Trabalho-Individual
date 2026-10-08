<?php
// Arquivo: php/api/auth.php
ob_start(); // Previne que qualquer aviso ou espaço em branco quebre o JSON
ini_set('display_errors', 0); // Desativa exibição direta de erros na saída HTTP
error_reporting(E_ALL);

session_start();
require_once '../config/database.php';

header("Content-Type: application/json; charset=UTF-8");

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

// Garante que o diretório de logs existe antes de tentar gravar
$log_dir = __DIR__ . '/../../logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0777, true);
}
$log_file = $log_dir . '/app.log';

try {
    $query = "SELECT id, nome, nickname, senha_hash, perfil, status, nacao_favorita FROM usuarios WHERE email = :email LIMIT 1";
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

        if (password_verify($senha, $row['senha_hash'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_nome'] = $row['nome'];
            $_SESSION['user_nickname'] = $row['nickname'];
            $_SESSION['user_perfil'] = $row['perfil'];
            $_SESSION['user_nacao'] = $row['nacao_favorita'];

            // No json_encode de sucesso:
            echo json_encode([
                "status" => "success",
                "message" => "Acesso autorizado, Comandante.",
                "perfil" => $row['perfil'],
                "nickname" => $row['nickname'],
                "nacao_favorita" => $row['nacao_favorita']
            ]);

            // Atualiza último login
            $update_query = "UPDATE usuarios SET ultimo_login = CURRENT_TIMESTAMP WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':id', $row['id']);
            $update_stmt->execute();

            @error_log(date('Y-m-d H:i:s') . " - Login Sucesso: " . $row['nickname'] . " (" . $email . ")\n", 3, $log_file);

            ob_clean();
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Acesso autorizado, Comandante.",
                "perfil" => $row['perfil'],
                "nickname" => $row['nickname']
            ]);
            exit;
        }
    }

    @error_log(date('Y-m-d H:i:s') . " - Falha de Login: " . $email . "\n", 3, $log_file);
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