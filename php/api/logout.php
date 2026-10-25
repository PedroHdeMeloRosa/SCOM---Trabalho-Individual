<?php
// Arquivo: php/api/logout.php
require_once '../config/bootstrap.php';

// Destrói todas as variáveis de sessão
$_SESSION = [];

// Invalida o cookie de sessão do navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Oblitera a sessão no servidor
session_destroy();

registrarLogApp("Logout executado pelo usuário", "AUTH_LOGOUT");
responderJSON(["status" => "success", "message" => "Desconexão concluída. Câmbio desligo."]);
?>