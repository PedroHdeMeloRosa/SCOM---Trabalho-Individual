<?php
// Arquivo: php/api/busca_veiculos.php (Radar de Busca Tática de Blindados)
require_once '../config/bootstrap.php';

// Sanitização da entrada de busca (compatível com PHP 8.1+)
$termo = trim(strip_tags($_GET['q'] ?? ''));

// Retorna conjunto vazio se a consulta tiver menos de 2 caracteres (evita sobrecarga no banco)
if (mb_strlen($termo) < 2) {
    responderJSON(["status" => "success", "resultados" => []]);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Consulta parametrizada com Prepared Statement (Prevenção contra SQL Injection)
    $query = "SELECT t.id, t.nome, t.url_pagina, t.caminho_imagem, t.classe_veiculo, t.nivel, n.nome AS nacao 
              FROM tanques t 
              LEFT JOIN nacoes n ON t.nacao_id = n.Id 
              WHERE t.nome LIKE :termo 
              LIMIT 10";
              
    $stmt = $db->prepare($query);
    $stmt->execute([':termo' => "%" . $termo . "%"]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalização dos rótulos de nível para o radar da interface
    foreach ($resultados as &$resultado) {
        if (!empty($resultado['nivel'])) {
            $resultado['nivel'] = trim(str_replace('Nível', '', $resultado['nivel']));
        }
    }
    unset($resultado); // Quebra a referência do ponteiro

    responderJSON([
        "status"     => "success",
        "resultados" => $resultados
    ], 200);

} catch (Exception $e) {
    // Observabilidade: Registro técnico de falha na consulta de busca no app.log
    registrarLogApp("Erro interno (busca_veiculos.php): " . $e->getMessage(), "ERROR");

    // Resposta genérica segura para o Front-End
    responderJSON(["status" => "error", "message" => "Falha na varredura do radar tático."], 500);
}
?>