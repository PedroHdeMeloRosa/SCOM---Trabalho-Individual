<?php
// Arquivo: php/config/bootstrap.php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

header("Content-Type: application/json; charset=UTF-8");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

function responderJSON(array $payload, int $httpCode = 200): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close(); // Libera a trava de sessão imediatamente
    }
    ob_clean();
    http_response_code($httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function obterPerfilSessao(): string {
    $perfil = $_SESSION['user_perfil'] ?? $_SESSION['perfil'] ?? $_SESSION['role'] ?? 'visitante';
    return strtolower(trim((string)$perfil));
}

function exigirLogin(): int {
    if (empty($_SESSION['user_id'])) {
        responderJSON([
            "status"  => "error",
            "code"    => "SESSION_EXPIRED",
            "message" => "Sessão expirada ou acesso não autorizado. Identifique-se novamente."
        ], 401);
    }
    return (int)$_SESSION['user_id'];
}

function validarCSRF(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tokenRecebido = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $tokenSessao = $_SESSION['csrf_token'] ?? '';

        if (empty($tokenRecebido) || empty($tokenSessao) || !hash_equals($tokenSessao, $tokenRecebido)) {
            responderJSON([
                "status"  => "error",
                "code"    => "CSRF_INVALID",
                "message" => "Falha de segurança: Token CSRF inválido ou sessão expirada."
            ], 403);
        }
    }
}

function exigirAdmin(): int {
    $userId = exigirLogin();
    if (obterPerfilSessao() !== 'admin') {
        responderJSON([
            "status"  => "error",
            "message" => "Acesso restrito ao Alto Comando."
        ], 403);
    }
    validarCSRF();
    return $userId;
}

function registrarAuditoria(PDO $db, string $alvoNick, string $acao, string $detalhes): void {
    $adminNick = $_SESSION['user_nickname'] ?? 'Comando Central';
    try {
        // Garante que a tabela exista para nunca travar ou perder registros
        $db->exec("CREATE TABLE IF NOT EXISTS logs_auditoria (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_nickname VARCHAR(100) NOT NULL,
            alvo_nickname VARCHAR(100) NOT NULL,
            acao VARCHAR(100) NOT NULL,
            detalhes TEXT,
            data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $stmt = $db->prepare("INSERT INTO logs_auditoria (admin_nickname, alvo_nickname, acao, detalhes) VALUES (:admin, :alvo, :acao, :detalhes)");
        $stmt->execute([
            ':admin'    => $adminNick,
            ':alvo'     => $alvoNick,
            ':acao'     => $acao,
            ':detalhes' => $detalhes
        ]);
    } catch (Exception $e) {
        // Continua silenciosamente se houver erro de permissão de escrita
    }
}

// Função para Observabilidade Básica (Requisito 10)
function registrarLogApp($mensagem, $tipo = "INFO") {
    $timestamp = date("Y-m-d H:i:s");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $logEntry = "[$timestamp] [$tipo] [IP: $ip] $mensagem" . PHP_EOL;
    
    // Salva o log num arquivo de texto na pasta do servidor
    $caminhoLog = __DIR__ . '/../../logs/app.log';
    error_log($logEntry, 3, $caminhoLog);
}

?>