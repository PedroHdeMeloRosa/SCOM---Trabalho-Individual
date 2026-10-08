<?php
// Arquivo: php/api/usuarios.php
ob_start();
ini_set('display_errors', 0);
session_start();

require_once '../config/database.php';
header("Content-Type: application/json; charset=UTF-8");

// Trava Geral: É necessário estar autenticado
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Acesso não autorizado. Identifique-se."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$admin_nick = $_SESSION['user_nickname'] ?? 'Comando Central';
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    // -------------------------------------------------------------
    // SALVAR NAÇÃO FAVORITA (Qualquer usuário logado pode fazer)
    // -------------------------------------------------------------
    if ($metodo === 'POST' && ($_POST['acao'] ?? '') === 'salvar_nacao') {
        $nacao = strtoupper(trim($_POST['nacao'] ?? ''));
        $nacoesValidas = ['USA', 'GER', 'RUS', 'JPN', 'SWE'];

        if (!in_array($nacao, $nacoesValidas)) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Doutrina nacional inválida."]);
            exit;
        }

        $stmt = $db->prepare("UPDATE usuarios SET nacao_favorita = :nacao WHERE id = :id");
        $stmt->bindParam(':nacao', $nacao);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();

        $_SESSION['user_nacao'] = $nacao;

        ob_clean();
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Doutrina nacional definida para $nacao.", "nacao" => $nacao]);
        exit;
    }

    // TRAVA RESTRITA: Todas as demais ações abaixo exigem perfil de administrador
    if (($_SESSION['user_perfil'] ?? '') !== 'admin') {
        ob_clean();
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Acesso restrito ao Alto Comando."]);
        exit;
    }

    // -------------------------------------------------------------
    // GET: Listar Tropas e Logs de Auditoria
    // -------------------------------------------------------------
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
        echo json_encode([
            "status" => "success",
            "usuarios" => $usuarios,
            "logs" => $logs
        ]);
        exit;
    }

    // -------------------------------------------------------------
    // POST: Ações Administrativas (Mudar Perfil / Status)
    // -------------------------------------------------------------
    if ($metodo === 'POST') {
        $acao = filter_input(INPUT_POST, 'acao', FILTER_DEFAULT);
        $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);

        if (!$usuario_id || !$acao) {
            ob_clean();
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Parâmetros incompletos."]);
            exit;
        }

        $stmtAlvo = $db->prepare("SELECT id, nickname, perfil, status FROM usuarios WHERE id = :id LIMIT 1");
        $stmtAlvo->bindParam(':id', $usuario_id);
        $stmtAlvo->execute();
        $alvo = $stmtAlvo->fetch(PDO::FETCH_ASSOC);

        if (!$alvo) {
            ob_clean();
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Soldado não localizado."]);
            exit;
        }

        if ($alvo['id'] == $_SESSION['user_id'] && $acao === 'mudar_perfil') {
            ob_clean();
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Não é permitido alterar a própria patente de comando."]);
            exit;
        }

        if ($acao === 'mudar_perfil') {
            $novo_perfil = filter_input(INPUT_POST, 'novo_perfil', FILTER_DEFAULT);
            if (!in_array($novo_perfil, ['admin', 'visitante'])) {
                ob_clean();
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Patente inválida."]);
                exit;
            }

            $update = $db->prepare("UPDATE usuarios SET perfil = :perfil WHERE id = :id");
            $update->bindParam(':perfil', $novo_perfil);
            $update->bindParam(':id', $usuario_id);
            $update->execute();

            $logStmt = $db->prepare("INSERT INTO logs_auditoria (admin_nickname, alvo_nickname, acao, detalhes) 
                                     VALUES (:admin, :alvo, 'PATENTE ALTERADA', :detalhes)");
            $detalhes = "De '{$alvo['perfil']}' para '{$novo_perfil}'";
            $logStmt->bindParam(':admin', $admin_nick);
            $logStmt->bindParam(':alvo', $alvo['nickname']);
            $logStmt->bindParam(':detalhes', $detalhes);
            $logStmt->execute();

            ob_clean();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Patente de {$alvo['nickname']} atualizada para {$novo_perfil}."]);
            exit;
        }

        if ($acao === 'mudar_status') {
            $novo_status = ($alvo['status'] === 'ativo') ? 'inativo' : 'ativo';

            $update = $db->prepare("UPDATE usuarios SET status = :status WHERE id = :id");
            $update->bindParam(':status', $novo_status);
            $update->bindParam(':id', $usuario_id);
            $update->execute();

            $logStmt = $db->prepare("INSERT INTO logs_auditoria (admin_nickname, alvo_nickname, acao, detalhes) 
                                     VALUES (:admin, :alvo, 'STATUS ALTERADO', :detalhes)");
            $detalhes = "Status alterado para '{$novo_status}'";
            $logStmt->bindParam(':admin', $admin_nick);
            $logStmt->bindParam(':alvo', $alvo['nickname']);
            $logStmt->bindParam(':detalhes', $detalhes);
            $logStmt->execute();

            ob_clean();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Soldado {$alvo['nickname']} marcado como {$novo_status}."]);
            exit;
        }
    }

    ob_clean();
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método não suportado."]);
    exit;

} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro na Base Central: " . $e->getMessage()]);
    exit;
}