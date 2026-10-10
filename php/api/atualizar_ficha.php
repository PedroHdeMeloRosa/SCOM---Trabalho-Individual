<?php
// Arquivo: php/api/atualizar_ficha.php
ob_start();
ini_set('display_errors', 0);
session_start();
require_once '../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

// Trava de Segurança
if (!isset($_SESSION['user_id']) || $_SESSION['user_perfil'] !== 'admin') {
    ob_clean();
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Acesso restrito."]);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    ob_clean();
    echo json_encode(["status" => "error", "message" => "Identificação inválida."]);
    exit;
}

// Novos campos de Identidade e Classe
$nome = $_POST['nome'] ?? '';
$descricao_breve = $_POST['descricao_breve'] ?? '';
$caminho_imagem = $_POST['caminho_imagem'] ?? '';

// Os 3 pilares do Badge (Distintivo)
$classe_veiculo = $_POST['classe_veiculo'] ?? 'Médio';
$tipo = $_POST['tipo'] ?? ''; // Função tática (ex: Flanqueador)
$nivel = $_POST['nivel'] ?? 'I';

$armamento = $_POST['armamento'] ?? '';
$velocidade = $_POST['velocidade'] ?? '';
$blindagem_chassi = $_POST['blindagem_chassi'] ?? '';
$blindagem_torre = $_POST['blindagem_torre'] ?? '';
$historico = $_POST['historico'] ?? '';
$taticas = $_POST['taticas'] ?? '';
$detalhes_extras = $_POST['detalhes_extras'] ?? '[]';
$galeria_imagens = $_POST['galeria_imagens'] ?? '[]';

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("
        UPDATE tanques 
        SET nome = :nome,
            descricao_breve = :descricao_breve,
            caminho_imagem = :caminho_imagem,
            classe_veiculo = :classe_veiculo,
            tipo = :tipo,
            nivel = :nivel,
            armamento = :armamento,
            velocidade = :velocidade,
            blindagem_chassi = :chassi,
            blindagem_torre = :torre,
            historico = :historico,
            taticas = :taticas,
            detalhes_extras = :extras,
            galeria_imagens = :galeria
        WHERE id = :id
    ");

    $stmt->execute([
        ':nome' => $nome,
        ':descricao_breve' => $descricao_breve,
        ':caminho_imagem' => $caminho_imagem,
        ':classe_veiculo' => $classe_veiculo,
        ':tipo' => $tipo,
        ':nivel' => $nivel,
        ':armamento' => $armamento,
        ':velocidade' => $velocidade,
        ':chassi' => $blindagem_chassi,
        ':torre' => $blindagem_torre,
        ':historico' => $historico,
        ':taticas' => $taticas,
        ':extras' => $detalhes_extras,
        ':galeria' => $galeria_imagens,
        ':id' => $id
    ]);

    ob_clean();
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Dossiê atualizado na Central."]);
} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro: " . $e->getMessage()]);
}
?>