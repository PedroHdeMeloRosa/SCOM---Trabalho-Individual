<?php
// Arquivo: php/compartilhar.php
// Gera as meta tags Open Graph no servidor para robôs (Discord, WhatsApp, Telegram) e redireciona usuários para ficha.html
require_once __DIR__ . '/config/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 1;
$titulo = "SCOM - War Thunder Wiki";
$descricao = "Central de Inteligência Tática e Dossiês de Blindados.";
$imagem = "WT - Artes/War-Thunder-logo.webp";

try {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT nome, classe_veiculo, nivel, descricao_breve, caminho_imagem FROM tanques WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $tanque = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tanque) {
        $titulo = $tanque['nome'] . " (" . ($tanque['classe_veiculo'] ?: 'Blindado') . " | Nível " . ($tanque['nivel'] ?: '?') . ")";
        $descricao = $tanque['descricao_breve'] ?: "Confira as especificações técnicas, blindagem e doutrina de combate deste veículo na SCOM Wiki.";
        if (!empty($tanque['caminho_imagem'])) {
            $imagem = $tanque['caminho_imagem'];
        }
    }
} catch (Exception $e) {}

$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$urlDestino = "{$protocolo}://{$host}{$baseUrl}/ficha.html?id={$id}";
$urlImagem = "{$protocolo}://{$host}{$baseUrl}/" . str_replace(' ', '%20', $imagem);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></title>
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="SCOM - War Thunder Wiki">
    <meta property="og:title" content="<?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($urlImagem, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($urlDestino, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($urlDestino, ENT_QUOTES, 'UTF-8') ?>">
    <script>window.location.replace(<?= json_encode($urlDestino) ?>);</script>
</head>
<body style="background:#111; color:#F2B400; font-family:monospace; text-align:center; padding-top:20%;">
    Redirecionando para o Dossiê Militar...
</body>
</html>