<?php
// Arquivo: php/api/admin_index_api.php
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
    if ($acao === 'salvar_nacao') {
        // Recebe os dados
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = $_POST['nome'] ?? '';
        $cor = $_POST['cor'] ?? '#111111';
        $bandeira = $_POST['bandeira'] ?? '';
        $silhueta = $_POST['silhueta'] ?? '';
        $resumo = $_POST['resumo'] ?? '';
        $lore = $_POST['lore'] ?? 'Doutrina pendente de desclassificação.';

        if (!$nome) throw new Exception("Nome da Nação é obrigatório.");

        if ($id) {
            // ATUALIZAR NAÇÃO EXISTENTE
            // O coalesce (COALESCE) garante que se um campo não for enviado (ex: editar via nacao.html não envia resumo), ele não apaga o que já tem no banco.
            $stmt = $db->prepare("
                UPDATE nacoes 
                SET nome = :nome, 
                    cor_hex = :cor, 
                    caminho_bandeira = :bandeira, 
                    icone_silhueta = COALESCE(NULLIF(:silhueta, ''), icone_silhueta), 
                    resumo_card = COALESCE(NULLIF(:resumo, ''), resumo_card),
                    lore_taticas = COALESCE(NULLIF(:lore, ''), lore_taticas)
                WHERE Id = :id
            ");
            $stmt->execute([
                ':nome' => $nome, 
                ':cor' => $cor, 
                ':bandeira' => $bandeira, 
                ':silhueta' => $silhueta, 
                ':resumo' => $resumo, 
                ':lore' => $lore,
                ':id' => $id
            ]);
            $msg = "Dados da Nação atualizados!";
        } else {
            // CRIAR NOVA NAÇÃO
            $stmt = $db->prepare("INSERT INTO nacoes (nome, cor_hex, caminho_bandeira, icone_silhueta, resumo_card, lore_taticas) VALUES (:nome, :cor, :bandeira, :silhueta, :resumo, :lore)");
            $stmt->execute([
                ':nome' => $nome, 
                ':cor' => $cor, 
                ':bandeira' => $bandeira, 
                ':silhueta' => $silhueta, 
                ':resumo' => $resumo, 
                ':lore' => $lore
            ]);
            $msg = "Nova Nação Fundada!";
        }

        ob_clean();
        echo json_encode(["status" => "success", "message" => $msg]);

    } elseif ($acao === 'excluir_nacao') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) throw new Exception("ID inválido.");

        // Apaga todos os tanques vinculados a esta nação primeiro (para não dar erro de Chave Estrangeira)
        $db->prepare("DELETE FROM tanques WHERE nacao_id = :id")->execute([':id' => $id]);
        // Apaga a nação
        $db->prepare("DELETE FROM nacoes WHERE Id = :id")->execute([':id' => $id]);

        ob_clean();
        echo json_encode(["status" => "success", "message" => "Nação e sua frota foram obliteradas."]);
    } else {
        throw new Exception("Comando de ação não reconhecido.");
    }
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>