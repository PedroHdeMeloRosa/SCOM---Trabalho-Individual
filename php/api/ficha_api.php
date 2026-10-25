<?php
// Arquivo: php/api/ficha_api.php (Consulta Detalhada do Blindado e Doutrina Tática)
require_once '../config/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    responderJSON(["status" => "error", "message" => "Identificação do veículo não informada ou inválida."], 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Consulta os atributos do veículo e agrega metadados heráldicos da nação associada
    $query = "SELECT t.*, n.nome AS nacao_nome, n.cor_hex 
              FROM tanques t 
              LEFT JOIN nacoes n ON t.nacao_id = n.Id 
              WHERE t.id = :id 
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);

    $tanque = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tanque) {
        responderJSON([
            "status" => "success",
            "dados"  => $tanque
        ], 200);
    } else {
        responderJSON([
            "status"  => "error",
            "message" => "Blindado não encontrado nos registros."
        ], 404);
    }

} catch (PDOException $e) {
    registrarLogApp("Erro interno (ficha_api.php): " . $e->getMessage(), "ERROR");
    responderJSON(["status" => "error", "message" => "Erro na base central."], 500);
}
?>