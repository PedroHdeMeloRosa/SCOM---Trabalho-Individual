<?php
// Arquivo: php/api/usuarios.php
require_once '../config/bootstrap.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. ROTA DE SINCRONIZAÇÃO DE SESSÃO POR CLIENTE
    if ($metodo === 'GET' && $acao === 'verificar_sessao') {
        if (!empty($_SESSION['user_id'])) {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            $stmtNacao = $db->prepare("SELECT nacao_favorita FROM usuarios WHERE id = :id LIMIT 1");
            $stmtNacao->execute([':id' => $_SESSION['user_id']]);
            $nacaoBanco = $stmtNacao->fetchColumn() ?: ($_SESSION['user_nacao'] ?? 'USA');
            $_SESSION['user_nacao'] = $nacaoBanco;

            responderJSON([
                "status"     => "success",
                "logado"     => true,
                "user_id"    => $_SESSION['user_id'],
                "nickname"   => $_SESSION['user_nickname'] ?? 'Comandante',
                "perfil"     => obterPerfilSessao(),
                "nacao"      => $nacaoBanco,
                "csrf_token" => $_SESSION['csrf_token']
            ]);
        } else {
            responderJSON(["status" => "success", "logado" => false]);
        }
    }

    $meuId = exigirLogin();

    // 2. SALVAR NAÇÃO FAVORITA (Aceita Sigla USA/GER/BRZ ou ID numérico)
    if ($metodo === 'POST' && ($acao === 'salvar_nacao' || $acao === 'salvar_nacao_usuario')) {
        validarCSRF();
        $nacao_raw = trim($_POST['nacao'] ?? $_POST['nacao_id'] ?? '');

        if ($nacao_raw === '') {
            responderJSON(["status" => "error", "message" => "Identificação de nação inválida."], 400);
        }

        // Corrigido: variável sem espaço após o cifrão
        $nacaoLimpa = htmlspecialchars($nacao_raw, ENT_QUOTES, 'UTF-8');

        $stmtUpdate = $db->prepare("UPDATE usuarios SET nacao_favorita = :nacao WHERE id = :id");
        $stmtUpdate->execute([':nacao' => $nacaoLimpa, ':id' => $meuId]);
        $_SESSION['user_nacao'] = $nacaoLimpa;

        responderJSON([
            "status"  => "success",
            "message" => "Doutrina nacional atualizada.",
            "nacao"   => $nacaoLimpa
        ]);
    }

    // 3. OPERAÇÕES DO ALTO COMANDO (ADMIN)
    exigirAdmin();
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // ROTA GET A: Carrega APENAS os Logs sob demanda
    if ($metodo === 'GET' && $acao === 'listar_logs') {
        $logs = [];
        try {
            $stmtLogs = $db->query("SELECT admin_nickname, alvo_nickname, acao, detalhes, data_hora FROM logs_auditoria ORDER BY id DESC LIMIT 50");
            $logs = $stmtLogs->fetchAll();
        } catch (Exception $e) {}
        responderJSON(["status" => "success", "logs" => $logs]);
    }

    // ROTA GET B: Carrega o Efetivo Ativo
    if ($metodo === 'GET') {
        $stmtUsers = $db->query("SELECT id, nickname, email, perfil, status, nacao_favorita FROM usuarios ORDER BY id DESC LIMIT 200");
        $usuarios = $stmtUsers->fetchAll();

        responderJSON([
            "status"   => "success",
            "dados"    => $usuarios,
            "usuarios" => $usuarios
        ]);
    }

    // ROTAS POST: Alterar Status ou Patente
    if ($metodo === 'POST') {
        $id_alvo = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT)
                ?? filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);

        if (!$id_alvo || !$acao) {
            responderJSON(["status" => "error", "message" => "Parâmetros de operação incompletos."], 400);
        }

        if ($id_alvo === $meuId) {
            responderJSON(["status" => "error", "message" => "Operação negada: Você não pode alterar sua própria patente ou status."], 400);
        }

        $stmtAlvo = $db->prepare("SELECT id, nickname, perfil, status FROM usuarios WHERE id = :id LIMIT 1");
        $stmtAlvo->execute([':id' => $id_alvo]);
        $alvo = $stmtAlvo->fetch();

        if (!$alvo) {
            responderJSON(["status" => "error", "message" => "Soldado não localizado."], 404);
        }

        if ($acao === 'alterar_status' || $acao === 'mudar_status') {
            $statusEnviado = strtolower(trim($_POST['status'] ?? ''));
            if (in_array($statusEnviado, ['inativo', 'suspenso'])) {
                $novo_status = 'inativo';
            } elseif ($statusEnviado === 'ativo') {
                $novo_status = 'ativo';
            } else {
                $novo_status = ($alvo['status'] === 'ativo') ? 'inativo' : 'ativo';
            }

            $update = $db->prepare("UPDATE usuarios SET status = :status WHERE id = :id");
            $update->execute([':status' => $novo_status, ':id' => $id_alvo]);

            registrarAuditoria($db, $alvo['nickname'], 'STATUS ALTERADO', "De '{$alvo['status']}' para '{$novo_status}'");

            responderJSON([
                "status"      => "success",
                "novo_status" => $novo_status,
                "message"     => "Status do soldado {$alvo['nickname']} atualizado para " . strtoupper($novo_status) . "."
            ]);
        }

        if ($acao === 'mudar_perfil' || $acao === 'alterar_perfil') {
            $novo_perfil = strtolower(trim($_POST['novo_perfil'] ?? ''));
            if (!in_array($novo_perfil, ['admin', 'visitante'])) {
                $novo_perfil = ($alvo['perfil'] === 'admin') ? 'visitante' : 'admin';
            }

            $update = $db->prepare("UPDATE usuarios SET perfil = :perfil WHERE id = :id");
            $update->execute([':perfil' => $novo_perfil, ':id' => $id_alvo]);

            registrarAuditoria($db, $alvo['nickname'], 'PATENTE ALTERADA', "De '{$alvo['perfil']}' para '{$novo_perfil}'");

            responderJSON([
                "status"      => "success",
                "novo_perfil" => $novo_perfil,
                "message"     => "Patente de {$alvo['nickname']} atualizada para " . strtoupper($novo_perfil) . "."
            ]);
        }

        responderJSON(["status" => "error", "message" => "Comando tático desconhecido."], 400);
    }

} catch (Exception $e) {
    responderJSON(["status" => "error", "message" => "Falha no Comando Central: " . $e->getMessage()], 500);
}
?>