<?php
// Arquivo: php/api/atualizar_lore_nacao.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// O texto que você já tinha no HTML, formatado com as quebras
$lore_usa = "A árvore tecnológica dos Estados Unidos é a definição máxima de versatilidade. Os veículos americanos são os verdadeiros \"paus para toda obra\" do War Thunder. Diferente das nações focadas puramente em blindagem ou canhões extremos, a doutrina dos EUA brilha na adaptabilidade, oferecendo excelente mobilidade, rotação de torre rápida e, acima de tudo, a melhor depressão de canhão do jogo.\n\nEssa depressão permite dominar táticas de \"Hull Down\" (esconder o chassi atrás de colinas e mostrar apenas a torre). Além disso, a presença de estabilizadores giroscópicos já em níveis baixos com a família Sherman garante a vantagem do primeiro disparo em combates em movimento. O suporte aéreo aproximado (CAS) também é um diferencial massivo para quem joga com esta nação.";

// Vamos garantir que a bandeira também esteja correta no banco
$bandeira_usa = "WT - Artes/USA/USA Flags/WT USA Flag.jpg";

try {
    // Atualiza a nação USA (ID 1)
    $stmt = $db->prepare("UPDATE nacoes SET lore_taticas = :lore, caminho_bandeira = :bandeira WHERE id = 1");
    $stmt->execute([':lore' => $lore_usa, ':bandeira' => $bandeira_usa]);
    
    echo "<h3 style='color:green;'>✅ Doutrina Nacional Americana atualizada!</h3>";
} catch (PDOException $e) {
    echo "<h3 style='color:red;'>❌ Erro: " . $e->getMessage() . "</h3>";
}
?>