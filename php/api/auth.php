<?php
// Arquivo: php/api/auth.php
session_start();
require_once '../config/database.php';

// Define o cabeçalho para retornar JSON e os códigos HTTP padronizados
header("Content-Type: application/json; charset=UTF-8");

// Proteção: Apenas requisições POST são aceitas
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["status" => "error", "message" => "Método HTTP não permitido."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Validação e Sanitização de entradas do formulário
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    http_response_code(400); // Bad Request
    echo json_encode(["status" => "error", "message" => "E-mail e senha são obrigatórios."]);
    exit;
}

// Caminho absoluto para o arquivo de log de observabilidade
$log_file = '../../logs/app.log';

try {
    // Consulta parametrizada (Prepared Statement) para impedir SQL Injection
    $query = "SELECT id, nome, senha_hash, perfil, status FROM usuarios WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Bloqueia contas inativas ou banidas
        if ($row['status'] !== 'ativo') {
            http_response_code(403); // Forbidden
            error_log(date('Y-m-d H:i:s') . " - Falha de login (Conta Inativa): " . $email . "\n", 3, $log_file);
            echo json_encode(["status" => "error", "message" => "Acesso negado. A conta não está ativa."]);
            exit;
        }

        // Verificação segura do hash criptografado
        if (password_verify($senha, $row['senha_hash'])) {
            
            // Inicializa as variáveis da Sessão Segura
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_nome'] = $row['nome'];
            $_SESSION['user_perfil'] = $row['perfil'];

            // Atualiza o registro de Último Login no banco
            $update_query = "UPDATE usuarios SET ultimo_login = CURRENT_TIMESTAMP WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':id', $row['id']);
            $update_stmt->execute();

            // Grava o log de sucesso (Observabilidade Básica)
            error_log(date('Y-m-d H:i:s') . " - Login BEM-SUCEDIDO: " . $email . " (" . $row['perfil'] . ")\n", 3, $log_file);

            http_response_code(200); // OK
            echo json_encode([
                "status" => "success", 
                "message" => "Acesso autorizado, Comandante.",
                "perfil" => $row['perfil'],
                "nome" => $row['nome']
            ]);
            
        } else {
            // Senha incorreta
            http_response_code(401); // Unauthorized
            error_log(date('Y-m-d H:i:s') . " - Falha de login (Senha incorreta): " . $email . "\n", 3, $log_file);
            echo json_encode(["status" => "error", "message" => "Credenciais inválidas."]);
        }
    } else {
        // E-mail não encontrado
        http_response_code(401); // Unauthorized
        error_log(date('Y-m-d H:i:s') . " - Falha de login (Usuário não encontrado): " . $email . "\n", 3, $log_file);
        echo json_encode(["status" => "error", "message" => "Credenciais inválidas."]);
    }
} catch(PDOException $e) {
    // Oculta o erro real do banco de dados do usuário final, registrando-o apenas no log interno
    http_response_code(500); // Internal Server Error
    error_log(date('Y-m-d H:i:s') . " - Erro Interno (DB): " . $e->getMessage() . "\n", 3, $log_file);
    echo json_encode(["status" => "error", "message" => "Erro interno no servidor."]);
}
?>