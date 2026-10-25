<?php
// Arquivo: php/api/excluir_tanque.php (Obliteração / Exclusão de Blindados)
require_once '../config/bootstrap.php';

// Exige patente de Administrador e validação de sessão/CSRF
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

    // 2. Executa a exclusão física (Hard Delete)
    $stmt = $db->prepare("DELETE FROM tanques WHERE id = :id");
    $stmt->execute([':id' => $id]);

    // 3. Registra no Livro de Logs do Alto Comando (Auditoria do Banco de Dados)
    registrarAuditoria($db, $nome_tanque, 'VEÍCULO EXCLUÍDO', "Exclusão permanente do registro (ID: {$id}).");

    // 4. Observabilidade Básica: Log de Aplicação em arquivo de texto (app.log)
    registrarLogApp("Exclusão de entidade: Blindado '{$nome_tanque}' (ID: {$id}) obliterado", "ENTITY_DELETE");

    responderJSON(["status" => "success", "message" => "Dossiê militar incinerado com sucesso."]);

} catch (Exception $e) {
    // Observabilidade: Registra o erro técnico no arquivo de log do servidor
    registrarLogApp("Erro interno (excluir_tanque.php): " . $e->getMessage(), "ERROR");

    // Resposta genérica sem vazamento de detalhes internos do banco para o cliente
    responderJSON(["status" => "error", "message" => "Erro interno no servidor ao excluir o blindado."], 500);
}
?>