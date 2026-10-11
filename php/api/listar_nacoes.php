<?php
// Arquivo: php/api/listar_nacoes.php
ob_start();
ini_set('display_errors', 0);
require_once '../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

$database = new Database();
$db = $database->getConnection();

try {
    // Puxa todos os dados das nações, ordenados pelo ID (EUA primeiro, Alemanha segundo...)
    $stmt = $db->prepare("SELECT Id, nome, cor_hex, caminho_bandeira, icone_silhueta, resumo_card, lore_taticas FROM nacoes ORDER BY Id ASC");
    $stmt->execute();
    $nacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    http_response_code(200);
    echo json_encode(["status" => "success", "nacoes" => $nacoes]);

} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro na base de dados das nações."]);
}
?>