<?php
require 'conexao.php';

// Aqui o PHP usa o algoritmo matemático real para criar o hash BCRYPT do gaijin123
$senha_real_criptografada = password_hash('gaijin123', PASSWORD_DEFAULT);

// Atualizamos a conta do comandante no banco de dados com a senha verdadeira
$stmt = $pdo->prepare("UPDATE comandantes SET senha = :senha WHERE email = 'comandante@gmail.com'");
$stmt->execute(['senha' => $senha_real_criptografada]);

echo "<h1 style='color: green;'>Chave Criptografica Atualizada com Sucesso!</h1>";
echo "<p>O Hash matematicamente verdadeiro gerado pelo servidor foi: <b>" . $senha_real_criptografada . "</b></p>";
echo "<p>Pode voltar para a pagina do seu site e tentar fazer o login novamente!</p>";
