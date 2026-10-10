<?php
// Arquivo: php/api/listar_frota_nacao.php
ob_start();
ini_set('display_errors', 0);
require_once '../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

$nacao_id = filter_input(INPUT_GET, 'nacao_id', FILTER_VALIDATE_INT);

if (!$nacao_id) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Nação não especificada."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // 1. Busca os dados da Nação
    $stmtNacao = $db->prepare("SELECT nome, cor_hex, caminho_bandeira, lore_taticas FROM nacoes WHERE Id = :id LIMIT 1");
    $stmtNacao->execute([':id' => $nacao_id]);
    $dadosNacao = $stmtNacao->fetch(PDO::FETCH_ASSOC);

    if (!$dadosNacao) {
        ob_clean();
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Nação inexistente."]);
        exit;
    }

    // 2. Busca a frota pertencente a ela
    $query = "SELECT id, nome, caminho_imagem, descricao_breve FROM tanques WHERE nacao_id = :id ORDER BY id ASC";
    $stmtFrota = $db->prepare($query);
    $stmtFrota->execute([':id' => $nacao_id]);
    $frota = $stmtFrota->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    http_response_code(200);
    echo json_encode([
        "status" => "success", 
        "nacao" => $dadosNacao,
        "tanques" => $frota
    ]);
} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro de servidor."]);
}
?>