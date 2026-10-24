<?php
ob_start();
ini_set('display_errors', 0);
session_start();
require_once '../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

$metodo = $_SERVER['REQUEST_METHOD'];
$acao = $_POST['acao'] ?? $_GET['acao'] ?? ''; // Aceita GET ou POST

// 1. Trava Global: O utilizador precisa estar autenticado
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Acesso não autorizado. Identifique-se."]);
    exit;
}

// 2. Trava de Segurança CSRF (Apenas exigida se for POST)
if ($metodo === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
        ob_clean();
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Falha de segurança: Token CSRF inválido."]);
        exit;
    }
}

$database = new Database();
$db = $database->getConnection();
$admin_nick = $_SESSION['user_nickname'] ?? 'Comando Central';

try {
    // [Se houver lógica de salvar_nacao aqui, deixe ficar]

    // =========================================================================
    // OPERAÇÕES RESTRITAS AO ALTO COMANDO (ADMIN)
    // =========================================================================
    
    // Verifica o perfil de forma flexível (suporta 'user_perfil' ou apenas 'perfil')
    $perfil = $_SESSION['user_perfil'] ?? $_SESSION['perfil'] ?? '';
    
    if ($perfil !== 'admin') {
        ob_clean();
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Acesso restrito ao Alto Comando."]);
        exit;
    }

    // Listagem do efetivo (Ação: listar)
    if ($metodo === 'GET' && $acao === 'listar') {
        $queryUsers = "SELECT id, nome, sobrenome, nickname, email, perfil, status, nacao_favorita, criado_em, ultimo_login 
                       FROM usuarios ORDER BY id DESC";
        $stmtUsers = $db->prepare($queryUsers);
        $stmtUsers->execute();
        $usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        ob_clean();
        http_response_code(200);
        // IMPORTANTE: Mudei "usuarios" para "dados" para bater certo com o seu JavaScript
        echo json_encode(["status" => "success", "dados" => $usuarios]);
        exit;
    }
    
try {
    // =========================================================================
    // 1. SALVAMENTO DA NAÇÃO (Permitido para qualquer soldado logado)
    // =========================================================================
    if ($metodo === 'POST' && ($acao === 'salvar_nacao' || $acao === 'salvar_nacao_usuario')) {
        // Aceita tanto o parâmetro 'nacao_id' quanto 'nacao'
        $nacao_id = filter_input(INPUT_POST, 'nacao_id', FILTER_VALIDATE_INT) 
                 ?? filter_input(INPUT_POST, 'nacao', FILTER_VALIDATE_INT);

        if (!$nacao_id) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Identificação de nação inválida."]);
            exit;
        }

        // Verifica se a nação realmente existe no banco de dados
        $stmtCheck = $db->prepare("SELECT Id, nome FROM nacoes WHERE Id = :id LIMIT 1");
        $stmtCheck->execute([':id' => $nacao_id]);
        $nacaoInfo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$nacaoInfo) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Nação não cadastrada nos registros."]);
            exit;
        }

        // Atualiza a escolha do soldado no banco
        $stmtUpdate = $db->prepare("UPDATE usuarios SET nacao_favorita = :nacao WHERE id = :id");
        $stmtUpdate->execute([
            ':nacao' => $nacao_id,
            ':id'    => $_SESSION['user_id']
        ]);

        $_SESSION['user_nacao'] = $nacao_id;

        ob_clean();
        http_response_code(200);
        echo json_encode([
            "status"  => "success",
            "message" => "Doutrina nacional vinculada a " . $nacaoInfo['nome'] . ".",
            "nacao"   => $nacao_id
        ]);
        exit;
    }

    // =========================================================================
    // 2. OPERAÇÕES RESTRITAS AO ALTO COMANDO (ADMIN)
    // =========================================================================
    if (($_SESSION['user_perfil'] ?? '') !== 'admin') {
        ob_clean();
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Acesso restrito ao Alto Comando."]);
        exit;
    }

    // Listagem do efetivo e logs de auditoria
    if ($metodo === 'GET') {
        $queryUsers = "SELECT id, nome, sobrenome, nickname, email, perfil, status, nacao_favorita, criado_em, ultimo_login 
                       FROM usuarios ORDER BY id DESC";
        $stmtUsers = $db->prepare($queryUsers);
        $stmtUsers->execute();
        $usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        $queryLogs = "SELECT admin_nickname, alvo_nickname, acao, detalhes, data_hora 
                      FROM logs_auditoria ORDER BY data_hora DESC LIMIT 50";
        $stmtLogs = $db->prepare($queryLogs);
        $stmtLogs->execute();
        $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

        ob_clean();
        http_response_code(200);
        echo json_encode(["status" => "success", "usuarios" => $usuarios, "logs" => $logs]);
        exit;
    }

    // Alteração de Patente e Status de Soldados
    if ($metodo === 'POST') {
        $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);

        if (!$usuario_id || !$acao) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Parâmetros incompletos."]);
            exit;
        }

        $stmtAlvo = $db->prepare("SELECT id, nickname, perfil, status FROM usuarios WHERE id = :id LIMIT 1");
        $stmtAlvo->execute([':id' => $usuario_id]);
        $alvo = $stmtAlvo->fetch(PDO::FETCH_ASSOC);

        if (!$alvo) {
            ob_clean();
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Soldado não localizado."]);
            exit;
        }

        // Bloqueia ações destrutivas contra o próprio administrador autenticado
        if ($alvo['id'] == $_SESSION['user_id'] && ($acao === 'mudar_perfil' || $acao === 'mudar_status')) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Operação negada: Não é permitido alterar a própria patente ou status."]);
            exit;
        }

        // AÇÃO: Mudar Patente (Perfil)
        if ($acao === 'mudar_perfil') {
            $novo_perfil = filter_input(INPUT_POST, 'novo_perfil', FILTER_DEFAULT);
            if (!in_array($novo_perfil, ['admin', 'visitante'])) {
                ob_clean();
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Patente inválida."]);
                exit;
            }

            $update = $db->prepare("UPDATE usuarios SET perfil = :perfil WHERE id = :id");
            $update->execute([':perfil' => $novo_perfil, ':id' => $usuario_id]);

            $logStmt = $db->prepare("INSERT INTO logs_auditoria (admin_nickname, alvo_nickname, acao, detalhes) 
                                     VALUES (:admin, :alvo, 'PATENTE ALTERADA', :detalhes)");
            $detalhes = "De '{$alvo['perfil']}' para '{$novo_perfil}'";
            $logStmt->execute([
                ':admin'    => $admin_nick,
                ':alvo'     => $alvo['nickname'],
                ':detalhes' => $detalhes
            ]);

            ob_clean();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Patente de {$alvo['nickname']} atualizada para {$novo_perfil}."]);
            exit;
        }

        // AÇÃO: Mudar Status (Suspensão/Ativação)
        if ($acao === 'mudar_status') {
            $novo_status = ($alvo['status'] === 'ativo') ? 'inativo' : 'ativo';
            
            // Executa a alteração no banco (Estava em falta no seu código)
            $update = $db->prepare("UPDATE usuarios SET status = :status WHERE id = :id");
            $update->execute([':status' => $novo_status, ':id' => $usuario_id]);

            // Grava o log de auditoria desta ação militar
            $logStmt = $db->prepare("INSERT INTO logs_auditoria (admin_nickname, alvo_nickname, acao, detalhes) 
                                     VALUES (:admin, :alvo, 'STATUS ALTERADO', :detalhes)");
            $detalhes = "De '{$alvo['status']}' para '{$novo_status}'";
            $logStmt->execute([
                ':admin'    => $admin_nick,
                ':alvo'     => $alvo['nickname'],
                ':detalhes' => $detalhes
            ]);

            // Devolve a resposta final em JSON
            ob_clean();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Status de {$alvo['nickname']} alterado para " . strtoupper($novo_status) . "."]);
            exit;
        }
    }

} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    // Em produção real, nunca mostre a mensagem de erro do MySQL diretamente, mas no ambiente académico é útil para debug
    echo json_encode(["status" => "error", "message" => "Falha no Comando Central: Erro de base de dados."]);
    exit;
}
}
?>