<?php
// Arquivo: php/api/migrar_tanques.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Limpa a tabela de tanques antiga para não duplicar dados errados
$db->query("SET FOREIGN_KEY_CHECKS = 0;");
$db->query("TRUNCATE TABLE tanques;");
$db->query("SET FOREIGN_KEY_CHECKS = 1;");

$bancoVeiculos = [
    // ESTADOS UNIDOS (USA)
    ["nome" => "M4A3E2 Sherman Jumbo", "url" => "WT - Artes/USA/USA Tanks/JUMBO/JUMBO.html", "icone" => "WT - Artes/USA/USA Tanks/JUMBO/JUMBO shadow.svg", "nacao" => "USA"],
    ["nome" => "M18 Hellcat",          "url" => "WT - Artes/USA/USA Tanks/M18/Hellcat.html", "icone" => "WT - Artes/USA/USA Tanks/M18/M-18_Hellcat shadow.svg", "nacao" => "USA"],
    ["nome" => "T34 Heavy Tank",       "url" => "WT - Artes/USA/USA Tanks/T34/T34.html", "icone" => "WT - Artes/USA/USA Tanks/T34/T34 shadow.svg", "nacao" => "USA"],
    ["nome" => "T95 (Doom Turtle)",    "url" => "WT - Artes/USA/USA Tanks/T95/T95.html", "icone" => "WT - Artes/USA/USA Tanks/T95/T95 for shadow.svg", "nacao" => "USA"],
    ["nome" => "M1A2 Abrams",          "url" => "WT - Artes/USA/USA Tanks/M1A2-ABRAMS/M1A2-ABRAMS.html", "icone" => "WT - Artes/USA/USA Tanks/M1A2-ABRAMS/M1A2-Abrams-Shadow.svg", "nacao" => "USA"],

    // ALEMANHA (GER)
    ["nome" => "Panther D",   "url" => "WT - Artes/GER/Ger Tanks/PANTER D/PANTER-D.html", "icone" => "WT - Artes/GER/Ger Tanks/PANTER D/Panter D shadow.svg", "nacao" => "GER"],
    ["nome" => "Tiger H1",    "url" => "WT - Artes/GER/Ger Tanks/TIGER H1/TIGER-H1.html", "icone" => "WT - Artes/GER/Ger Tanks/TIGER H1/TIGER H1 shadow.svg", "nacao" => "GER"],
    ["nome" => "Tiger II V2", "url" => "WT - Artes/GER/Ger Tanks/TIGER II V2/TIGERII.html", "icone" => "WT - Artes/GER/Ger Tanks/TIGER II V2/Tiger II shadow.svg", "nacao" => "GER"],
    ["nome" => "Sturmtiger",  "url" => "WT - Artes/GER/Ger Tanks/STURMTIGER/Sturmtiger.html", "icone" => "WT - Artes/GER/Ger Tanks/STURMTIGER/Sturmtiger shadow.svg", "nacao" => "GER"],
    ["nome" => "MAUS",        "url" => "WT - Artes/GER/Ger Tanks/MAUS/MAUS.html", "icone" => "WT - Artes/GER/Ger Tanks/MAUS/MAUS shadow.svg", "nacao" => "GER"],

    // JAPÃO (JPN)
    ["nome" => "Chi-Ha LG", "url" => "WT - Artes/JPN/JPN Tanks/CHI-RA LG/CHI-RA LG.html", "icone" => "WT - Artes/JPN/JPN Tanks/CHI-RA LG/CHI-RA-LG-Shadow.svg", "nacao" => "JPN"],
    ["nome" => "Ho-Ri",     "url" => "WT - Artes/JPN/JPN Tanks/HO-RI/HO-RI.html", "icone" => "WT - Artes/JPN/JPN Tanks/HO-RI/HO-Ri-Shadow.svg", "nacao" => "JPN"],
    ["nome" => "Ta-Se",     "url" => "WT - Artes/JPN/JPN Tanks/Ta-Se/Ta-Se.html", "icone" => "WT - Artes/JPN/JPN Tanks/Ta-Se/Ta-Se-Shadow.svg", "nacao" => "JPN"],

    // RÚSSIA (RUS)
    ["nome" => "T-34-85",    "url" => "WT - Artes/RUS/RUS Tanks/T-34 85/T34-85.html", "icone" => "WT - Artes/RUS/RUS Tanks/T-34 85/T-34 85 shadow.svg", "nacao" => "RUS"],
    ["nome" => "KV-1 (ZiS-5)","url" => "WT - Artes/RUS/RUS Tanks/KV-1/KV-1.html", "icone" => "WT - Artes/RUS/RUS Tanks/KV-1/KV-1 shadow.svg", "nacao" => "RUS"],
    ["nome" => "IS-3",       "url" => "WT - Artes/RUS/RUS Tanks/IS-3/IS-3.html", "icone" => "WT - Artes/RUS/RUS Tanks/IS-3/IS-3 shadow.svg", "nacao" => "RUS"],
    ["nome" => "Object 279", "url" => "WT - Artes/RUS/RUS Tanks/OBJ-279/OBJ-279.html", "icone" => "WT - Artes/RUS/RUS Tanks/OBJ-279/OBJ-279 shadow.svg", "nacao" => "RUS"],
    ["nome" => "SU-100",     "url" => "WT - Artes/RUS/RUS Tanks/SU-100/SU-100.html", "icone" => "WT - Artes/RUS/RUS Tanks/SU-100/SU-100 shadow.svg", "nacao" => "RUS"],

    // SUÉCIA (SWE)
    ["nome" => "Bkan 1C",     "url" => "WT - Artes/SWE/SWE Tanks/BKAN 1/BKAN 1.html", "icone" => "WT - Artes/SWE/SWE Tanks/BKAN 1/BKAN 1 shadow.svg", "nacao" => "SWE"],
    ["nome" => "BT-42",       "url" => "WT - Artes/SWE/SWE Tanks/BT-42/BT-42.html", "icone" => "WT - Artes/SWE/SWE Tanks/BT-42/BT-42 shadow.svg", "nacao" => "SWE"],
    ["nome" => "SAV 20.12.48","url" => "WT - Artes/SWE/SWE Tanks/SAV 20.12.48/SAV-20.html", "icone" => "WT - Artes/SWE/SWE Tanks/SAV 20.12.48/SAV 20.12.48 shadow.svg", "nacao" => "SWE"]
];

$sucesso = 0;

foreach ($bancoVeiculos as $tanque) {
    // Busca a nação ou cria se não existir
    $stmtNacao = $db->prepare("SELECT Id FROM nacoes WHERE nome = :nome LIMIT 1");
    $stmtNacao->execute([':nome' => $tanque['nacao']]);
    $nacaoRow = $stmtNacao->fetch(PDO::FETCH_ASSOC);

    if ($nacaoRow) {
        $nacao_id = $nacaoRow['Id'];
    } else {
        $stmtInsertNacao = $db->prepare("INSERT INTO nacoes (nome, cor_hex, caminho_bandeira) VALUES (:nome, '#ffffff', '')");
        $stmtInsertNacao->execute([':nome' => $tanque['nacao']]);
        $nacao_id = $db->lastInsertId();
    }

    // Insere o tanque na base de dados (o campo url_pagina e caminho_imagem guardarão os espaços literais)
    $stmtTanque = $db->prepare("INSERT INTO tanques (nome, nacao_id, url_pagina, caminho_imagem, tipo, nivel) VALUES (:nome, :nacao_id, :url, :icone, 'Tanque', 'Desconhecido')");
    $stmtTanque->execute([
        ':nome' => $tanque['nome'],
        ':nacao_id' => $nacao_id,
        ':url' => $tanque['url'],
        ':icone' => $tanque['icone']
    ]);
    $sucesso++;
}

echo "<h3 style='color:green;'>✅ Tabela limpa e $sucesso Blindados reinseridos com sucesso (espaçamentos corrigidos)!</h3>";
?>