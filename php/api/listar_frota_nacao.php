<?php
// Arquivo: php/api/get_frota_nacao.php (Consulta de Doutrina da Nação e Catálogo de Blindados)
require_once '../config/bootstrap.php';

$nacao_id = filter_input(INPUT_GET, 'nacao_id', FILTER_VALIDATE_INT);
if (!$nacao_id) {
    responderJSON(["status" => "error", "message" => "Nação não especificada ou identificador inválido."], 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Busca dos dados institucionais da Nação
    $stmtNacao = $db->prepare("
        SELECT nome, cor_hex, caminho_bandeira, lore_taticas 
        FROM nacoes 
        WHERE Id = :id 
        LIMIT 1
    ");
    $stmtNacao->execute([':id' => $nacao_id]);
    $dadosNacao = $stmtNacao->fetch(PDO::FETCH_ASSOC);

    if (!$dadosNacao) {
        responderJSON(["status" => "error", "message" => "Nação não localizada na base de dados."], 404);
    }

    // 2. Consulta dos blindados pertencentes à frota da Nação
    $query = "SELECT id, nome, caminho_imagem, descricao_breve FROM tanques WHERE nacao_id = :id ORDER BY id ASC";
    $stmtFrota = $db->prepare($query);
    $stmtFrota->execute([':id' => $nacao_id]);
    $frota = $stmtFrota->fetchAll(PDO::FETCH_ASSOC);

    responderJSON([
        "status"  => "success",
        "nacao"   => $dadosNacao,
        "tanques" => $frota
    ]);

} catch (PDOException $e) {
    registrarLogApp("Erro interno (get_frota_nacao.php): " . $e->getMessage(), "ERROR");
    responderJSON(["status" => "error", "message" => "Erro interno no servidor ao carregar a frota."], 500);
}
?>