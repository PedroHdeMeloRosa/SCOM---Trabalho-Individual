<?php
// Arquivo: php/api/get_nacoes.php (Consulta ao Catálogo Tático de Nações)
require_once '../config/bootstrap.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        SELECT Id, nome, cor_hex, caminho_bandeira, icone_silhueta, resumo_card, lore_taticas 
        FROM nacoes 
        ORDER BY Id ASC
    ");
    $stmt->execute();
    $nacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    responderJSON([
        "status" => "success",
        "nacoes" => $nacoes
    ]);

} catch (PDOException $e) {
    registrarLogApp("Erro interno (get_nacoes.php): " . $e->getMessage(), "ERROR");
    responderJSON(["status" => "error", "message" => "Erro na base de dados das nações."], 500);
}
?>