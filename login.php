<?php
// login.php
header('Content-Type: application/json'); // Avisa que a resposta será em formato JSON
require 'conexao.php';

// Recebe os dados enviados pelo JavaScript
$dados = json_decode(file_get_contents("php://input"));

if(isset($dados->email) && isset($dados->senha)) {
    $email_digitado = $dados->email;
    $senha_digitada = $dados->senha;

    // Busca o comandante no banco de dados
    $stmt = $pdo->prepare("SELECT * FROM comandantes WHERE email = :email");
    $stmt->execute(['email' => $email_digitado]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se o usuário existir E a senha bater com a criptografia
    if($usuario && password_verify($senha_digitada, $usuario['senha'])) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Acesso Autorizado', 'email' => $usuario['email']]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Credenciais Incorretas']);
    }
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados incompletos']);
}