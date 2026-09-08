<?php
// conexao.php
$host = "localhost";
$usuario = "root"; // Usuário padrão do XAMPP
$senha = "";       // O XAMPP vem sem senha por padrão
$banco = "warthunder_db";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8", $usuario, $senha);
    // Configura para lançar erros na tela caso algo dê errado
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Falha na comunicação com o QG: " . $e->getMessage());
}
