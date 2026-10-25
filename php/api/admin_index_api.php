<?php
// Arquivo: php/api/admin_index_api.php (Controlador Central de Nações e Criação de Frota)
require_once '../config/bootstrap.php';

exigirAdmin();

$acao = $_POST['acao'] ?? '';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Unifica 'salvar_nacao' (index.html) e 'editar_nacao' (nacao.html)
    if ($acao === 'salvar_nacao' || $acao === 'editar_nacao') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = trim($_POST['nome'] ?? '');
        $cor = trim($_POST['cor'] ?? '#111111');
        $bandeira = trim($_POST['bandeira'] ?? '');
        $silhueta = trim($_POST['silhueta'] ?? '');
        $resumo = trim($_POST['resumo'] ?? '');
        $lore = trim($_POST['lore'] ?? '');

        if (!$nome) {
            responderJSON(["status" => "error", "message" => "O nome da Nação é obrigatório."], 400);
        }

        if ($id) {
            $stmt = $db->prepare("
                UPDATE nacoes 
                SET nome = :nome, 
                    cor_hex = COALESCE(NULLIF(:cor, ''), cor_hex), 
                    caminho_bandeira = COALESCE(NULLIF(:bandeira, ''), caminho_bandeira), 
                    icone_silhueta = COALESCE(NULLIF(:silhueta, ''), icone_silhueta), 
                    resumo_card = COALESCE(NULLIF(:resumo, ''), resumo_card),
                    lore_taticas = COALESCE(NULLIF(:lore, ''), lore_taticas)
                WHERE Id = :id
            ");
            $stmt->execute([
                ':nome' => $nome, ':cor' => $cor, ':bandeira' => $bandeira,
                ':silhueta' => $silhueta, ':resumo' => $resumo, ':lore' => $lore, ':id' => $id
            ]);
            registrarAuditoria($db, $nome, 'NAÇÃO ATUALIZADA', "ID: {$id}");
            
            // LOG DE APLICAÇÃO (Atualização)
            registrarLogApp("Atualização de entidade: Nação ID {$id} alterada por um Admin", "ENTITY_UPDATE");

            responderJSON(["status" => "success", "message" => "Registros da Nação atualizados com sucesso!"]);
        } else {
            $loreInicial = $lore !== '' ? $lore : 'Doutrina pendente de desclassificação.';
            $stmt = $db->prepare("INSERT INTO nacoes (nome, cor_hex, caminho_bandeira, icone_silhueta, resumo_card, lore_taticas) VALUES (:nome, :cor, :bandeira, :silhueta, :resumo, :lore)");
            $stmt->execute([
                ':nome' => $nome, ':cor' => $cor, ':bandeira' => $bandeira,
                ':silhueta' => $silhueta, ':resumo' => $resumo, ':lore' => $loreInicial
            ]);
            
            $novo_id = $db->lastInsertId(); // Captura o ID recém-criado
            registrarAuditoria($db, $nome, 'NAÇÃO FUNDADA', "Nova nação inserida");
            
            // LOG DE APLICAÇÃO: Criação de Entidade
            registrarLogApp("Criação de entidade: Nova Nação '{$nome}' (ID: {$novo_id})", "ENTITY_CREATE");

            responderJSON(["status" => "success", "message" => "Nova Nação Fundada!"]);
        }

    } elseif ($acao === 'excluir_nacao') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) responderJSON(["status" => "error", "message" => "ID inválido."], 400);

        $db->beginTransaction();
        $db->prepare("DELETE FROM tanques WHERE nacao_id = :id")->execute([':id' => $id]);
        $db->prepare("DELETE FROM nacoes WHERE Id = :id")->execute([':id' => $id]);
        $db->commit();

        registrarAuditoria($db, "Nação #{$id}", 'NAÇÃO EXCLUÍDA', "Nação e frota removidas");
        
        // LOG DE APLICAÇÃO (Exclusão)
        registrarLogApp("Exclusão de entidade: Nação ID {$id} obliterada", "ENTITY_DELETE");

        responderJSON(["status" => "success", "message" => "Nação e sua frota foram obliteradas."]);

    } elseif ($acao === 'novo_tanque') {
        $nacao_id = filter_input(INPUT_POST, 'nacao_id', FILTER_VALIDATE_INT);
        if (!$nacao_id) responderJSON(["status" => "error", "message" => "ID da nação inválido."], 400);

        $nome_provisorio = "NOVO PROJETO (NÃO CLASSIFICADO)";
        $stmt = $db->prepare("INSERT INTO tanques (nome, nacao_id, url_pagina, caminho_imagem, classe_veiculo, tipo, nivel, historico, taticas, detalhes_extras, galeria_imagens) 
                              VALUES (:nome, :nacao_id, '', '', 'Médio', 'Tanque', 'I', 'Pendente...', 'Pendente...', '[]', '[]')");
        $stmt->execute([':nome' => $nome_provisorio, ':nacao_id' => $nacao_id]);
        $novo_id = $db->lastInsertId();

        registrarAuditoria($db, $nome_provisorio, 'NOVO BLINDADO', "ID: {$novo_id} na Nação #{$nacao_id}");
        
        // LOG DE APLICAÇÃO: Criação de Entidade
        registrarLogApp("Criação de entidade: Novo Blindado ID {$novo_id} alocado na Nação {$nacao_id}", "ENTITY_CREATE");

        responderJSON(["status" => "success", "message" => "Base criada.", "novo_id" => $novo_id]);

    } else {
        responderJSON(["status" => "error", "message" => "Comando de ação não reconhecido."], 400);
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    
    // LOG DE APLICAÇÃO: Erro Interno com Stack Trace técnico
    registrarLogApp("Erro interno de SQL (admin_index_api): " . $e->getMessage(), "ERROR");
    
    // Resposta legível ao Front-End (Ocultando detalhes do PDO)
    responderJSON(["status" => "error", "message" => "Erro interno no servidor ao processar a operação."], 500);
}
?>