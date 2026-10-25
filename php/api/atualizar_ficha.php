<?php
// Arquivo: php/api/atualizar_ficha.php (Edição e Atualização do Dossiê do Blindado)
require_once '../config/bootstrap.php';

// Exige patente de Administrador e valida o Token CSRF automaticamente
exigirAdmin();

// 1. Recebimento e Validação da Identificação
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    responderJSON(["status" => "error", "message" => "Identificação do blindado não recebida ou inválida."], 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // 2. Coleta e Normalização dos Dados
    $nome             = trim($_POST['nome'] ?? '');
    $descricao_breve  = trim($_POST['descricao_breve'] ?? '');
    $caminho_imagem   = trim($_POST['caminho_imagem'] ?? '');
    $classe_veiculo   = trim($_POST['classe_veiculo'] ?? 'Médio');
    $tipo             = trim($_POST['tipo'] ?? '');
    $nivel            = trim($_POST['nivel'] ?? 'I');
    $armamento        = trim($_POST['armamento'] ?? '');
    $velocidade       = trim($_POST['velocidade'] ?? '');
    $blindagem_chassi = trim($_POST['blindagem_chassi'] ?? '');
    $blindagem_torre  = trim($_POST['blindagem_torre'] ?? '');
    $historico        = trim($_POST['historico'] ?? '');
    $taticas          = trim($_POST['taticas'] ?? '');
    $detalhes_extras  = trim($_POST['detalhes_extras'] ?? '{}');
    $galeria_imagens  = trim($_POST['galeria_imagens'] ?? '[]');

    if (empty($nome)) {
        responderJSON(["status" => "error", "message" => "O nome do veículo é obrigatório."], 400);
    }
    
    // Nação Correspondente (com busca de fallback caso não enviada)
    $nacao_id = filter_input(INPUT_POST, 'nacao_id', FILTER_VALIDATE_INT); 
    if (!$nacao_id) {
        $stmt_fallback = $db->prepare("SELECT nacao_id FROM tanques WHERE id = :id LIMIT 1");
        $stmt_fallback->execute([':id' => $id]);
        $nacao_id = $stmt_fallback->fetchColumn();
    }

    // 3. Execução da Atualização Parametrizada (Prepared Statement)
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
        ':nome'             => $nome,
        ':descricao_breve'  => $descricao_breve,
        ':caminho_imagem'   => $caminho_imagem,
        ':classe_veiculo'   => $classe_veiculo,
        ':tipo'             => $tipo,
        ':nivel'            => $nivel,
        ':armamento'        => $armamento,
        ':velocidade'       => $velocidade,
        ':blindagem_chassi' => $blindagem_chassi,
        ':blindagem_torre'  => $blindagem_torre,
        ':historico'        => $historico,
        ':taticas'          => $taticas,
        ':detalhes_extras'  => $detalhes_extras,
        ':galeria_imagens'  => $galeria_imagens,
        ':nacao_id'         => $nacao_id,
        ':id'               => $id
    ]);

    // 4. Auditoria de Base de Dados (Livro de Registros do Painel)
    registrarAuditoria($db, $nome, 'FICHA TÁTICA ATUALIZADA', "Dossiê militar modificado (ID: {$id}).");

    // 5. Observabilidade: Gravação em arquivo de log da aplicação (app.log)
    registrarLogApp("Atualização de entidade: Blindado '{$nome}' (ID: {$id}) atualizado por um Administrador", "ENTITY_UPDATE");

    responderJSON(["status" => "success", "message" => "Dossiê e Posicionamento Tático Atualizados!"]);

} catch (Exception $e) {
    // Observabilidade: Captura o erro técnico com timestamp no arquivo interno
    registrarLogApp("Erro interno (atualizar_ficha.php): " . $e->getMessage(), "ERROR");

    // Resposta genérica sem vazamento de stack trace ao front-end
    responderJSON(["status" => "error", "message" => "Erro interno no servidor ao atualizar a ficha do blindado."], 500);
}
?>