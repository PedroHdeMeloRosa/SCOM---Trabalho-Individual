<?php
// Arquivo: php/api/logout.php
session_start();

// Esvazia todas as variáveis da sessão atual
$_SESSION = array();

// Destrói a sessão no servidor de forma irreversível
session_destroy();

// Retorna o aviso de sucesso para o Front-End
header("Content-Type: application/json; charset=UTF-8");
http_response_code(200);
echo json_encode(["status" => "success", "message" => "Sessão encerrada com sucesso."]);
?>