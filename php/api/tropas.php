<?php
// Arquivo: php/api/tropas.php
ob_start();
ini_set('display_errors', 0);
session_start();
require_once '../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// 1. Trava Global: O usuário precisa estar autenticado
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Acesso não autorizado. Identifique-se."]);
    exit;
}

// 2. Trava de Segurança CSRF (Atua APENAS em modificações - POST)
// Isso permite que o seu fetch(GET) passe livremente para listar as tropas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        ob_clean();
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Falha de segurança: Token CSRF inválido ou ausente."]);
        exit;
    }
}

$database = new Database();
$db = $database->getConnection();
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

// 3. Verificação de Patente (Modo Flexível / Inteligente)
// Procura a patente em todas as variáveis de sessão comuns e força tudo para letras minúsculas
$perfil_cru = $_SESSION['user_perfil'] ?? $_SESSION['perfil'] ?? $_SESSION['role'] ?? $_SESSION['userRole'] ?? $_SESSION['user_role'] ?? 'desconhecido';
$perfil = strtolower(trim($perfil_cru));

if ($perfil !== 'admin') {
    ob_clean();
    http_response_code(403);
    // O "Perfil detetado" vai dizer-nos o que a sessão realmente tem guardado!
    echo json_encode(["status" => "error", "message" => "Acesso restrito. (Perfil detetado: '$perfil_cru')"]);
    exit;
}

// =========================================================================
// ROTA GET: Listar todos os soldados
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $acao === 'listar') {
    try {
        $stmt = $db->prepare("SELECT id, nickname, email, nacao_favorita, perfil, status FROM usuarios ORDER BY id DESC");
        $stmt->execute();
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        ob_clean();
        http_response_code(200);
        echo json_encode(["status" => "success", "dados" => $usuarios]);
    } catch (Exception $e) {
        ob_clean(); 
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Erro ao acessar os registros classificados."]);
    }
    exit;
}

// =========================================================================
// ROTA POST: Suspender ou Reativar soldados
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'alterar_status') {
    $id_alvo = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $novo_status = $_POST['status'] ?? '';

    if (!$id_alvo || !in_array($novo_status, ['ativo', 'suspenso'])) {
        ob_clean();
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Parâmetros de operação inválidos."]); 
        exit;
    }

    if ($id_alvo == $_SESSION['user_id']) {
        ob_clean();
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Operação negada: Você não pode suspender a si mesmo."]); 
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE usuarios SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $novo_status, ':id' => $id_alvo]);
        
        ob_clean();
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Status do soldado atualizado com sucesso."]);
    } catch (Exception $e) {
        ob_clean(); 
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Falha ao processar a corte marcial."]);
    }
    exit;
}
?>