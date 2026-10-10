<?php
// Arquivo: php/api/atualizar_jumbo.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Textos com as marcações HTML (strong) exatamente como você pediu
$tipo = "Tanque Pesado (Assalto)";
$nivel = "Nível III";
$armamento = "Canhão M3 de 75 mm";
$blindagem_chassi = "101 mm / 76 mm / 38 mm";
$blindagem_torre = "152 mm / 152 mm / 152 mm";
$velocidade = "35 km/h";

$historico = "O <strong>M4A3E2 Sherman Jumbo</strong> foi a resposta americana à necessidade de um tanque de assalto pesado capaz de liderar avanços contra posições fortificadas na Europa. Com blindagem extra soldada diretamente sobre o chassi do M4 padrão e uma torre redesenhada e massiva, ele se tornou uma rocha no campo de batalha.";

$taticas = "A sua principal vantagem é a <strong>blindagem frontal impenetrável</strong> para a grande maioria dos canhões do seu nível. Nunca angule o chassi, pois a blindagem lateral inferior é extremamente fina. Enfrente o inimigo de frente, use seu recarregamento rápido para desativar os canhões inimigos atirando nos canos, e proteja o seu ponto fraco: <strong>a porta da metralhadora no chassi</strong>.";

try {
    $stmt = $db->prepare("
        UPDATE tanques 
        SET tipo = :tipo, 
            nivel = :nivel, 
            armamento = :armamento, 
            blindagem_chassi = :chassi, 
            blindagem_torre = :torre, 
            velocidade = :velocidade, 
            historico = :historico, 
            taticas = :taticas 
        WHERE nome LIKE '%Jumbo%'
    ");

    $stmt->execute([
        ':tipo' => $tipo,
        ':nivel' => $nivel,
        ':armamento' => $armamento,
        ':chassi' => $blindagem_chassi,
        ':torre' => $blindagem_torre,
        ':velocidade' => $velocidade,
        ':historico' => $historico,
        ':taticas' => $taticas
    ]);

    echo "<h3 style='color:green;'>✅ Ficha do Jumbo atualizada com sucesso no banco de dados!</h3>";
} catch (PDOException $e) {
    echo "<h3 style='color:red;'>❌ Erro: " . $e->getMessage() . "</h3>";
}
?>