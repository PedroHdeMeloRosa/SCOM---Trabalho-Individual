<?php
// Arquivo: php/api/seeder.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';

// Proteção básica: Comente ou apague este arquivo após o uso para evitar injeções repetidas
// if (!isset($_GET['autorizar']) || $_GET['autorizar'] !== 'sim') { die("Acesso Negado."); }

$database = new Database();
$db = $database->getConnection();

$nacoes = ['USA', 'GER', 'RUS', 'JPN', 'SWE'];
$status_opcoes = ['ativo', 'ativo', 'ativo', 'ativo', 'inativo']; // 80% de chance de ser ativo
$quantidade = 500;

// Geramos a senha apenas uma vez para não sobrecarregar o processador com 500 criptografias
// A senha de todos os bots será: Soldado@123
$senha_padrao = password_hash('Soldado@123', PASSWORD_DEFAULT);

echo "<h3>🎖️ Iniciando Operação Clonagem...</h3>";

$sucesso = 0;
$falhas = 0;

for ($i = 1; $i <= $quantidade; $i++) {
    // Gera dados aleatórios estilo militar
    $numero = str_pad($i, 3, "0", STR_PAD_LEFT);
    $nome = "Bot";
    $sobrenome = "Unidade " . $numero;
    $nickname = "Ghost_" . $numero . "_" . rand(10, 99);
    $email = "clone{$numero}_" . rand(1000, 9999) . "@testbot.com";
    $nacao = $nacoes[array_rand($nacoes)];
    $status = $status_opcoes[array_rand($status_opcoes)];

    try {
        $stmt = $db->prepare("
            INSERT INTO usuarios (nome, sobrenome, nickname, email, senha_hash, perfil, status, nacao_favorita) 
            VALUES (:nome, :sobrenome, :nickname, :email, :senha, 'visitante', :status, :nacao)
        ");
        
        $stmt->execute([
            ':nome' => $nome,
            ':sobrenome' => $sobrenome,
            ':nickname' => $nickname,
            ':email' => $email,
            ':senha' => $senha_padrao,
            ':status' => $status,
            ':nacao' => $nacao
        ]);
        
        $sucesso++;
    } catch (PDOException $e) {
        $falhas++;
    }
}

echo "<p style='color: green;'>✅ <strong>$sucesso</strong> tropas artificiais inseridas com sucesso!</p>";
if ($falhas > 0) {
    echo "<p style='color: red;'>❌ $falhas falhas durante a inserção.</p>";
}
echo "<p>Volte ao sistema e abra o Painel de Tropas para visualizar o exército.</p>";
?>