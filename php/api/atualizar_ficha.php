<?php
// Arquivo: php/api/atualizar_ficha.php
require_once '../config/bootstrap.php';

// Exige patente de Administrador e valida o Token CSRF automaticamente
exigirAdmin();

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. RECEBENDO AS ORDENS DA INTERFACE
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) throw new Exception("Identificação do blindado não recebida.");

    $nome = trim($_POST['nome'] ?? '');
    $descricao_breve = trim($_POST['descricao_breve'] ?? '');
    $caminho_imagem = trim($_POST['caminho_imagem'] ?? '');
    $classe_veiculo = trim($_POST['classe_veiculo'] ?? 'Médio');
    $tipo = trim($_POST['tipo'] ?? '');
    $nivel = trim($_POST['nivel'] ?? 'I');
    $armamento = trim($_POST['armamento'] ?? '');
    $velocidade = trim($_POST['velocidade'] ?? '');
    $blindagem_chassi = trim($_POST['blindagem_chassi'] ?? '');
    $blindagem_torre = trim($_POST['blindagem_torre'] ?? '');
    $historico = trim($_POST['historico'] ?? '');
    $taticas = trim($_POST['taticas'] ?? '');
    $detalhes_extras = trim($_POST['detalhes_extras'] ?? '{}');
    $galeria_imagens = trim($_POST['galeria_imagens'] ?? '[]');
    
    // Nação Correspondente
    $nacao_id = filter_input(INPUT_POST, 'nacao_id', FILTER_VALIDATE_INT); 
    if (!$nacao_id) {
        $stmt_fallback = $db->prepare("SELECT nacao_id FROM tanques WHERE id = :id");
        $stmt_fallback->execute([':id' => $id]);
        $nacao_id = $stmt_fallback->fetchColumn();
    }

    // 2. EXECUTANDO A ATUALIZAÇÃO
    $query = "UPDATE tanques SET 
                nome = :nome,
                descricao_breve = :descricao_breve,
                caminho_imagem = :caminho_imagem,
                classe_veiculo = :classe_veiculo,
                tipo = :tipo,
                nivel = :nivel,
                armamento = :armamento,
                velocidade = :velocidade,
                blindagem_chassi = :blindagem_chassi,
                blindagem_torre = :blindagem_torre,
                historico = :historico,
                taticas = :taticas,
                detalhes_extras = :detalhes_extras,
                galeria_imagens = :galeria_imagens,
                nacao_id = :nacao_id 
              WHERE id = :id";

    $stmt = $db->prepare($query);
    $stmt->execute([
        ':nome' => $nome,
        ':descricao_breve' => $descricao_breve,
        ':caminho_imagem' => $caminho_imagem,
        ':classe_veiculo' => $classe_veiculo,
        ':tipo' => $tipo,
        ':nivel' => $nivel,
        ':armamento' => $armamento,
        ':velocidade' => $velocidade,
        ':blindagem_chassi' => $blindagem_chassi,
        ':blindagem_torre' => $blindagem_torre,
        ':historico' => $historico,
        ':taticas' => $taticas,
        ':detalhes_extras' => $detalhes_extras,
        ':galeria_imagens' => $galeria_imagens,
        ':nacao_id' => $nacao_id,
        ':id' => $id
    ]);

    // 3. GRAVAÇÃO NO LIVRO DE AUDITORIA
    registrarAuditoria($db, $nome, 'FICHA TÁTICA ATUALIZADA', "Dossiê militar do veículo modificado.");

    responderJSON(["status" => "success", "message" => "Dossiê e Posicionamento Tático Atualizados!"]);

} catch (Exception $e) {
    responderJSON(["status" => "error", "message" => $e->getMessage()], 500);
}
?>