<?php
// Arquivo: php/api/excluir_tanque.php
require_once '../config/bootstrap.php';

// Exige patente de Administrador e valida o Token CSRF automaticamente
exigirAdmin();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    responderJSON(["status" => "error", "message" => "Identificação do veículo inválida."], 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Captura o nome do tanque ANTES de excluí-lo
    $stmtNome = $db->prepare("SELECT nome FROM tanques WHERE id = :id LIMIT 1");
    $stmtNome->execute([':id' => $id]);
    $nome_tanque = $stmtNome->fetchColumn() ?: "Projeto Desconhecido #{$id}";

    // 2. Executa a exclusão (Obliteração)
    $stmt = $db->prepare("DELETE FROM tanques WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // 3. Registra no Livro de Logs do Alto Comando
    registrarAuditoria($db, $nome_tanque, 'VEÍCULO EXCLUÍDO', "Exclusão permanente do registro.");

    responderJSON(["status" => "success", "message" => "Dossiê militar incinerado com sucesso."]);

} catch (Exception $e) {
    responderJSON(["status" => "error", "message" => "Falha no servidor: " . $e->getMessage()], 500);
}
?>