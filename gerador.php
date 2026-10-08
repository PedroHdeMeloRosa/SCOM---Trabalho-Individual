<?php
// A senha em texto limpo definida para o teste
$senha = 'admin#01@wtwiki.com';

// Gera o hash seguro utilizando o algoritmo nativo mais forte do PHP (Bcrypt)
$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "<h2>Gerador Tático de Hash</h2>";
echo "<strong>Senha original:</strong> " . $senha . "<br><br>";
echo "<strong>Copie o Hash abaixo:</strong><br>";
echo "<input type='text' value='" . $hash . "' style='width: 500px; padding: 5px;' readonly>";
?>