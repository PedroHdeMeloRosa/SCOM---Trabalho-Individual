<?php
// Arquivo: php/api/clonar_usa.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2 style='color:#F2B400; font-family:monospace;'>Iniciando Operação Overlord: Atualização da Frota Americana...</h2>";

// Dicionário Tático com os dados estruturados de cada tanque
$frota_usa = [
    [
        "nome" => "M4A3E2 Sherman Jumbo",
        "tipo" => "Tanque Pesado (Assalto)",
        "nivel" => "Nível III",
        "armamento" => "Canhão M3 de 75 mm",
        "blindagem_chassi" => "101 mm",
        "blindagem_torre" => "152 mm",
        "velocidade" => "35 km/h",
        "detalhes_extras" => [
            ["chave" => "Munição Padrão", "valor" => "APCBC (Carga Explosiva) / APCR"],
            ["chave" => "Tripulação", "valor" => "5 Membros"]
        ],
        "galeria_imagens" => [
            ["url" => "WT - Artes/USA/USA Tanks/JUMBO/JUMBO foto.webp", "legenda" => "Visão Padrão M4A3E2 Jumbo no jogo"],
            ["url" => "WT - Artes/USA/USA Tanks/JUMBO/JUMBO foto2.webp", "legenda" => "Desenho Técnico do M4A3E2 Jumbo"],
            ["url" => "WT - Artes/USA/USA Tanks/JUMBO/JUMBO foto3.webp", "legenda" => "Visão alternativa do M4A3E2 Jumbo"]
        ]
    ],
    [
        "nome" => "M18 Hellcat",
        "tipo" => "Caça-Tanques Rápido",
        "nivel" => "Nível III",
        "armamento" => "Canhão M1 de 76 mm",
        "blindagem_chassi" => "12 mm",
        "blindagem_torre" => "25 mm",
        "velocidade" => "72 km/h",
        "detalhes_extras" => [
            ["chave" => "Munição Padrão", "valor" => "APCBC"],
            ["chave" => "Tripulação", "valor" => "5 Membros"]
        ],
        "galeria_imagens" => [
            ["url" => "WT - Artes/USA/USA Tanks/M18/Hellcat foto.webp", "legenda" => "Visão em Combate"],
            ["url" => "WT - Artes/USA/USA Tanks/M18/Hellcat foto2.webp", "legenda" => "Mobilidade e Flanqueamento"]
        ]
    ],
    [
        "nome" => "T34 Heavy Tank",
        "tipo" => "Tanque Pesado",
        "nivel" => "Nível IV",
        "armamento" => "Canhão T53 de 120 mm",
        "blindagem_chassi" => "101 mm",
        "blindagem_torre" => "279 mm",
        "velocidade" => "35 km/h",
        "detalhes_extras" => [
            ["chave" => "Munição Padrão", "valor" => "AP (Projétil Sólido)"],
            ["chave" => "Tripulação", "valor" => "6 Membros"]
        ],
        "galeria_imagens" => [
            ["url" => "WT - Artes/USA/USA Tanks/T34/T34 foto.webp", "legenda" => "Visão em Combate"],
            ["url" => "WT - Artes/USA/USA Tanks/T34/T34 foto2.webp", "legenda" => "Canhão Maciço de 120mm"]
        ]
    ],
    [
        "nome" => "T95 (Doom Turtle)",
        "tipo" => "Canhão Autopropulsado Superpesado",
        "nivel" => "Nível IV",
        "armamento" => "Canhão T5E1 de 105 mm",
        "blindagem_chassi" => "305 mm",
        "blindagem_torre" => "Casamata (305 mm)",
        "velocidade" => "12 km/h",
        "detalhes_extras" => [
            ["chave" => "Munição Padrão", "valor" => "APCBC"],
            ["chave" => "Tripulação", "valor" => "4 Membros"]
        ],
        "galeria_imagens" => [
            ["url" => "WT - Artes/USA/USA Tanks/T95/T95 foto.webp", "legenda" => "Fortaleza Móvel Frontal"],
            ["url" => "WT - Artes/USA/USA Tanks/T95/T95 foto2.webp", "legenda" => "Esteiras Duplas"]
        ]
    ],
    [
        "nome" => "M1A2 Abrams",
        "tipo" => "Tanque Principal de Batalha (MBT)",
        "nivel" => "Nível VII",
        "armamento" => "Canhão M256 de 120 mm (Alma Lisa)",
        "blindagem_chassi" => "Composta (Chobham)",
        "blindagem_torre" => "Composta (Urânio Empobrecido)",
        "velocidade" => "68 km/h",
        "detalhes_extras" => [
            ["chave" => "Munição Padrão", "valor" => "APFSDS (Dardo) / HEAT-FS"],
            ["chave" => "Tripulação", "valor" => "4 Membros"],
            ["chave" => "Motor", "valor" => "Turbina a Gás Honeywell AGT1500 (1500 hp)"],
            ["chave" => "Tecnologia", "valor" => "Estabilizador Duplo, Visão Térmica, LRF"]
        ],
        "galeria_imagens" => [
            ["url" => "WT - Artes/USA/USA Tanks/M1A2-ABRAMS/M1A2 foto.webp", "legenda" => "Operações no Deserto"],
            ["url" => "WT - Artes/USA/USA Tanks/M1A2-ABRAMS/M1A2 foto2.webp", "legenda" => "Blindagem Chobham"]
        ]
    ]
];

$sucesso = 0;

foreach ($frota_usa as $tanque) {
    // Converte os arrays para JSON estruturado
    $json_extras = json_encode($tanque['detalhes_extras'], JSON_UNESCAPED_UNICODE);
    $json_galeria = json_encode($tanque['galeria_imagens'], JSON_UNESCAPED_UNICODE);

    try {
        $stmt = $db->prepare("
            UPDATE tanques 
            SET tipo = :tipo, 
                nivel = :nivel, 
                armamento = :armamento, 
                blindagem_chassi = :chassi, 
                blindagem_torre = :torre, 
                velocidade = :velocidade,
                detalhes_extras = :extras,
                galeria_imagens = :galeria
            WHERE nome = :nome
        ");

        $stmt->execute([
            ':tipo' => $tanque['tipo'],
            ':nivel' => $tanque['nivel'],
            ':armamento' => $tanque['armamento'],
            ':chassi' => $tanque['blindagem_chassi'],
            ':torre' => $tanque['blindagem_torre'],
            ':velocidade' => $tanque['velocidade'],
            ':extras' => $json_extras,
            ':galeria' => $json_galeria,
            ':nome' => $tanque['nome']
        ]);

        echo "<p style='color:green; font-family:monospace;'>[OK] Dossiê de <strong>{$tanque['nome']}</strong> atualizado com sucesso!</p>";
        $sucesso++;
    } catch (PDOException $e) {
        echo "<p style='color:red; font-family:monospace;'>[FALHA] Erro ao atualizar <strong>{$tanque['nome']}</strong>: " . $e->getMessage() . "</p>";
    }
}

echo "<h3 style='color:#F2B400; font-family:monospace;'>Missão Cumprida: $sucesso veículos americanos registrados.</h3>";
?>