<?php
// Arquivo: php/api/ficha_api.php
ob_start();
ini_set('display_errors', 0);
require_once '../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Identificação do veículo não informada."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Puxa todos os dados do tanque e a cor/bandeira da nação correspondente
    $query = "SELECT t.*, n.nome AS nacao_nome, n.cor_hex 
              FROM tanques t 
              LEFT JOIN nacoes n ON t.nacao_id = n.Id 
              WHERE t.id = :id LIMIT 1";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $tanque = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tanque) {
        ob_clean();
        http_response_code(200);
        echo json_encode(["status" => "success", "dados" => $tanque]);
    } else {
        ob_clean();
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Blindado não encontrado nos registros."]);
    }
} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro na base central."]);
}
?>