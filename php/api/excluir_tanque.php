<?php
// Arquivo: php/api/excluir_tanque.php
ob_start();
ini_set('display_errors', 0);
session_start();
require_once '../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

// Trava de Segurança: Apenas Administradores podem apagar dados
if (!isset($_SESSION['user_id']) || $_SESSION['user_perfil'] !== 'admin') {
    ob_clean();
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Acesso restrito ao Alto Comando."]);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Identificação do veículo inválida."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Ordem de exclusão direta na linha do ID selecionado
    $stmt = $db->prepare("DELETE FROM tanques WHERE id = :id");
    $stmt->execute([':id' => $id]);

    ob_clean();
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Dossiê militar incinerado com sucesso."]);
} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Falha no servidor: " . $e->getMessage()]);
}
?>