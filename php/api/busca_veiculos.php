<?php
// Arquivo: php/api/busca_veiculos.php
ob_start();
ini_set('display_errors', 0);
require_once '../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

$termo = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_STRING);

if (!$termo || strlen($termo) < 2) {
    ob_clean();
    echo json_encode(["status" => "success", "resultados" => []]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Busca na tabela 'tanques' com JOIN na tabela 'nacoes' para pegar a sigla/nome[cite: 6, 7]
    $query = "SELECT t.id, t.nome, t.url_pagina, t.caminho_imagem, n.nome AS nacao 
              FROM tanques t 
              LEFT JOIN nacoes n ON t.nacao_id = n.Id 
              WHERE t.nome LIKE :termo 
              LIMIT 10";
              
    $stmt = $db->prepare($query);
    $busca = "%" . $termo . "%";
    $stmt->bindParam(':termo', $busca);
    $stmt->execute();

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    http_response_code(200);
    echo json_encode(["status" => "success", "resultados" => $resultados]);
} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Falha no radar: " . $e->getMessage()]);
}
?>