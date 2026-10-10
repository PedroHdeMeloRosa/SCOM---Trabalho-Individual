<?php
// Arquivo: php/api/admin_nacao_api.php
ob_start();
ini_set('display_errors', 0);
session_start();
require_once '../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

// Trava de Segurança
if (!isset($_SESSION['user_id']) || $_SESSION['user_perfil'] !== 'admin') {
    ob_clean();
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Acesso restrito ao Alto Comando."]);
    exit;
}

$acao = $_POST['acao'] ?? '';
$database = new Database();
$db = $database->getConnection();

try {
    if ($acao === 'editar_nacao') {
        // Atualiza a Lore e Dados da Nação
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = $_POST['nome'] ?? '';
        $cor = $_POST['cor'] ?? '#ffffff';
        $bandeira = $_POST['bandeira'] ?? '';
        $lore = $_POST['lore'] ?? '';

        if (!$id || !$nome) throw new Exception("Dados insuficientes.");

        $stmt = $db->prepare("UPDATE nacoes SET nome = :nome, cor_hex = :cor, caminho_bandeira = :bandeira, lore_taticas = :lore WHERE Id = :id");
        $stmt->execute([':nome' => $nome, ':cor' => $cor, ':bandeira' => $bandeira, ':lore' => $lore, ':id' => $id]);

        ob_clean();
        echo json_encode(["status" => "success", "message" => "Doutrina Nacional atualizada."]);

    } elseif ($acao === 'novo_tanque') {
        // Cria um Tanque em Branco para esta Nação
        $nacao_id = filter_input(INPUT_POST, 'nacao_id', FILTER_VALIDATE_INT);
        if (!$nacao_id) throw new Exception("ID da nação inválido.");

        $nome_provisorio = "NOVO PROJETO (NÃO CLASSIFICADO)";
        
        $stmt = $db->prepare("INSERT INTO tanques (nome, nacao_id, url_pagina, caminho_imagem, tipo, nivel, historico, taticas, detalhes_extras, galeria_imagens) 
                              VALUES (:nome, :nacao_id, '', '', 'Tanque', 'Desconhecido', 'Pendente...', 'Pendente...', '[]', '[]')");
        $stmt->execute([':nome' => $nome_provisorio, ':nacao_id' => $nacao_id]);
        
        $novo_id = $db->lastInsertId();

        ob_clean();
        echo json_encode(["status" => "success", "message" => "Base criada.", "novo_id" => $novo_id]);
    } else {
        throw new Exception("Ação tática não reconhecida.");
    }
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>