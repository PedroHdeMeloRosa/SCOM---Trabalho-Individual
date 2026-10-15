<?php
// Arquivo: php/api/atualizar_ficha.php
ob_start();
ini_set('display_errors', 0);
session_start();
require_once '../config/database.php';
header("Content-Type: application/json; charset=UTF-8");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains"); // Força o uso de HTTPS

// Trava de segurança (Apenas Comandantes podem alterar os dados)
if (!isset($_SESSION['user_id']) || $_SESSION['user_perfil'] !== 'admin') {
    ob_clean();
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Acesso restrito ao Alto Comando."]);
    exit;
}

// Trava de Segurança: Prevenção CSRF (Passo 1.2)
$csrf_token = $_POST['csrf_token'] ?? '';
if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
    ob_clean();
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Falha de segurança: Token CSRF inválido ou ausente."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // 1. RECEBENDO AS ORDENS DA INTERFACE
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) throw new Exception("Identificação do blindado não recebida.");

    $nome = $_POST['nome'] ?? '';
    $descricao_breve = $_POST['descricao_breve'] ?? '';
    $caminho_imagem = $_POST['caminho_imagem'] ?? '';
    $classe_veiculo = $_POST['classe_veiculo'] ?? 'Médio';
    $tipo = $_POST['tipo'] ?? '';
    $nivel = $_POST['nivel'] ?? 'I';
    $armamento = $_POST['armamento'] ?? '';
    $velocidade = $_POST['velocidade'] ?? '';
    $blindagem_chassi = $_POST['blindagem_chassi'] ?? '';
    $blindagem_torre = $_POST['blindagem_torre'] ?? '';
    $historico = $_POST['historico'] ?? '';
    $taticas = $_POST['taticas'] ?? '';
    $detalhes_extras = $_POST['detalhes_extras'] ?? '{}';
    $galeria_imagens = $_POST['galeria_imagens'] ?? '[]';
    
    // O SOLDADO QUE ESTAVA A SER IGNORADO:
    $nacao_id = filter_input(INPUT_POST, 'nacao_id', FILTER_VALIDATE_INT); 

    // Se por acaso a nação não for enviada, procuramos a atual para não quebrar o banco
    if (!$nacao_id) {
        $stmt_fallback = $db->prepare("SELECT nacao_id FROM tanques WHERE id = :id");
        $stmt_fallback->execute([':id' => $id]);
        $nacao_id = $stmt_fallback->fetchColumn();
    }

    // 2. EXECUTANDO A ATUALIZAÇÃO (INCLUINDO O nacao_id)
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
        ':nacao_id' => $nacao_id,   // Ordem de transferência aplicada!
        ':id' => $id
    ]);

    ob_clean();
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Dossiê e Posicionamento Tático Atualizados!"]);

} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>