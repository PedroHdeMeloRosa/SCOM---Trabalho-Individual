<?php
// Arquivo: php/api/busca_veiculos.php
require_once '../config/bootstrap.php';

// Substitui FILTER_SANITIZE_STRING (depreciado no PHP 8.1+) por trim + strip_tags
$termo = trim(strip_tags($_GET['q'] ?? ''));

if (mb_strlen($termo) < 2) {
    responderJSON(["status" => "success", "resultados" => []]);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT t.id, t.nome, t.url_pagina, t.caminho_imagem, t.classe_veiculo, t.nivel, n.nome AS nacao 
              FROM tanques t 
              LEFT JOIN nacoes n ON t.nacao_id = n.Id 
              WHERE t.nome LIKE :termo 
              LIMIT 10";
              
    $stmt = $db->prepare($query);
    $stmt->execute([':termo' => "%" . $termo . "%"]);
    $resultados = $stmt->fetchAll();

    foreach ($resultados as &$resultado) {
        if (!empty($resultado['nivel'])) {
            $resultado['nivel'] = trim(str_replace('Nível', '', $resultado['nivel']));
        }
    }

    responderJSON(["status" => "success", "resultados" => $resultados]);
} catch (Exception $e) {
    responderJSON(["status" => "error", "message" => "Falha no radar."], 500);
}
?>