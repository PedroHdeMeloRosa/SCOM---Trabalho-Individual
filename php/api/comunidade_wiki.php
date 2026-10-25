<?php
// Arquivo: php/api/comunidade_wiki.php (Relatórios de Combate, Votos e Catálogo da Wiki)
require_once '../config/bootstrap.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$acao   = $_POST['acao'] ?? $_GET['acao'] ?? '';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Garante a existência das tabelas relacionais
    $db->exec("CREATE TABLE IF NOT EXISTS comentarios_tanques (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tanque_id INT NOT NULL,
        usuario_id INT NOT NULL,
        parent_id INT DEFAULT NULL,
        conteudo TEXT NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tanque (tanque_id),
        INDEX idx_parent (parent_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE IF NOT EXISTS comentarios_votos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comentario_id INT NOT NULL,
        usuario_id INT NOT NULL,
        voto TINYINT NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_comentario_usuario (comentario_id, usuario_id),
        INDEX idx_comentario (comentario_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $meuId     = $_SESSION['user_id'] ?? 0;
    $meuPerfil = obterPerfilSessao();

    // =========================================================================
    // 1. CATÁLOGO PARA COMPARADOR DE BLINDADOS E HIPERLINKS DA WIKI (GET)
    // =========================================================================
    if ($metodo === 'GET' && $acao === 'catalogo_wiki') {
        $stmt = $db->query("
            SELECT t.id, t.nome, t.nacao_id, t.caminho_imagem, t.classe_veiculo, 
                   t.tipo, t.nivel, t.armamento, t.velocidade, 
                   t.blindagem_chassi, t.blindagem_torre, t.detalhes_extras,
                   n.nome AS nacao_nome, n.cor_hex
            FROM tanques t
            LEFT JOIN nacoes n ON t.nacao_id = n.Id
            ORDER BY t.nacao_id ASC, t.id ASC
        ");
        $tanques = $stmt->fetchAll();
        responderJSON(["status" => "success", "tanques" => $tanques]);
    }

    // =========================================================================
    // 2. LISTAR COMENTÁRIOS, RESPOSTAS E LIKES/DISLIKES DE UM TANQUE (GET)
    // =========================================================================
    if ($metodo === 'GET' && $acao === 'listar_comentarios') {
        $tanque_id = filter_input(INPUT_GET, 'tanque_id', FILTER_VALIDATE_INT);
        if (!$tanque_id) {
            responderJSON(["status" => "error", "message" => "ID do tanque inválido."], 400);
        }

        $query = "
            SELECT c.id, c.tanque_id, c.usuario_id, c.parent_id, c.conteudo, c.criado_em,
                   u.nickname, u.perfil, u.nacao_favorita,
                   COALESCE(SUM(CASE WHEN v.voto = 1 THEN 1 ELSE 0 END), 0) AS likes,
                   COALESCE(SUM(CASE WHEN v.voto = -1 THEN 1 ELSE 0 END), 0) AS dislikes,
                   COALESCE(MAX(CASE WHEN v.usuario_id = :meu_id THEN v.voto ELSE 0 END), 0) AS meu_voto
            FROM comentarios_tanques c
            INNER JOIN usuarios u ON c.usuario_id = u.id
            LEFT JOIN comentarios_votos v ON c.id = v.comentario_id
            WHERE c.tanque_id = :tanque_id
            GROUP BY c.id
            ORDER BY c.criado_em ASC
        ";

        $stmt = $db->prepare($query);
        $stmt->execute([
            ':meu_id'    => $meuId,
            ':tanque_id' => $tanque_id
        ]);
        $linhas = $stmt->fetchAll();

        // Organização hierárquica (Threads aninhadas)
        $principais = [];
        $respostasPorPai = [];

        foreach ($linhas as $item) {
            $item['id']           = (int)$item['id'];
            $item['usuario_id']   = (int)$item['usuario_id'];
            $item['parent_id']    = $item['parent_id'] ? (int)$item['parent_id'] : null;
            $item['likes']        = (int)$item['likes'];
            $item['dislikes']     = (int)$item['dislikes'];
            $item['meu_voto']     = (int)$item['meu_voto'];
            $item['pode_excluir'] = ($meuPerfil === 'admin' || ($meuId > 0 && $meuId === $item['usuario_id']));

            if ($item['parent_id'] === null) {
                $item['respostas'] = [];
                $principais[$item['id']] = $item;
            } else {
                $respostasPorPai[$item['parent_id']][] = $item;
            }
        }

        foreach ($respostasPorPai as $paiId => $listaRespostas) {
            if (isset($principais[$paiId])) {
                $principais[$paiId]['respostas'] = $listaRespostas;
            }
        }

        $resultadoFinal = array_reverse(array_values($principais));

        responderJSON([
            "status"      => "success",
            "comentarios" => $resultadoFinal,
            "total"       => count($linhas)
        ]);
    }

    // =========================================================================
    // 3. OPERAÇÕES DE MUTAÇÃO (EXIGEM USUÁRIO AUTENTICADO + TOKEN CSRF)
    // =========================================================================
    $usuarioLogadoId = exigirLogin();
    validarCSRF();

    // AÇÃO A: Publicar Comentário ou Resposta
    if ($metodo === 'POST' && $acao === 'publicar_comentario') {
        $tanque_id    = filter_input(INPUT_POST, 'tanque_id', FILTER_VALIDATE_INT);
        $parent_id    = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT) ?: null;
        $conteudo_cru = trim($_POST['conteudo'] ?? '');

        if (!$tanque_id || mb_strlen($conteudo_cru) < 2) {
            responderJSON(["status" => "error", "message" => "Escreva uma mensagem válida antes de transmitir."], 400);
        }

        if (mb_strlen($conteudo_cru) > 1000) {
            responderJSON(["status" => "error", "message" => "O relatório excede o limite máximo de 1000 caracteres."], 400);
        }

        // Sanitização contra XSS
        $conteudo_limpo = htmlspecialchars($conteudo_cru, ENT_QUOTES, 'UTF-8');

        $stmt = $db->prepare("INSERT INTO comentarios_tanques (tanque_id, usuario_id, parent_id, conteudo) VALUES (:t, :u, :p, :c)");
        $stmt->execute([
            ':t' => $tanque_id,
            ':u' => $usuarioLogadoId,
            ':p' => $parent_id,
            ':c' => $conteudo_limpo
        ]);

        $novoComentarioId = $db->lastInsertId();
        $tipoRelatorio = $parent_id ? "Resposta ao comentário #{$parent_id}" : "Comentário Principal";

        // Observabilidade: Criação de Entidade com Timestamp
        registrarLogApp("Criação de entidade: Relatório de Combate ({$tipoRelatorio}) ID #{$novoComentarioId} no Tanque #{$tanque_id} pelo Soldado ID {$usuarioLogadoId}", "ENTITY_CREATE");

        responderJSON(["status" => "success", "message" => "Relatório de combate transmitido!"]);
    }

    // AÇÃO B: Votar Like (1) ou Dislike (-1)
    if ($metodo === 'POST' && $acao === 'votar_comentario') {
        $comentario_id = filter_input(INPUT_POST, 'comentario_id', FILTER_VALIDATE_INT);
        $tipo_voto     = filter_input(INPUT_POST, 'voto', FILTER_VALIDATE_INT);

        if (!$comentario_id || !in_array($tipo_voto, [1, -1])) {
            responderJSON(["status" => "error", "message" => "Parâmetros de avaliação inválidos."], 400);
        }

        $stmtCheck = $db->prepare("SELECT voto FROM comentarios_votos WHERE comentario_id = :c AND usuario_id = :u LIMIT 1");
        $stmtCheck->execute([':c' => $comentario_id, ':u' => $usuarioLogadoId]);
        $votoAtual = $stmtCheck->fetchColumn();

        if ($votoAtual !== false) {
            if ((int)$votoAtual === $tipo_voto) {
                $db->prepare("DELETE FROM comentarios_votos WHERE comentario_id = :c AND usuario_id = :u")
                   ->execute([':c' => $comentario_id, ':u' => $usuarioLogadoId]);
                $meuNovoVoto = 0;
            } else {
                $db->prepare("UPDATE comentarios_votos SET voto = :v WHERE comentario_id = :c AND usuario_id = :u")
                   ->execute([':v' => $tipo_voto, ':c' => $comentario_id, ':u' => $usuarioLogadoId]);
                $meuNovoVoto = $tipo_voto;
            }
        } else {
            $db->prepare("INSERT INTO comentarios_votos (comentario_id, usuario_id, voto) VALUES (:c, :u, :v)")
               ->execute([':c' => $comentario_id, ':u' => $usuarioLogadoId, ':v' => $tipo_voto]);
            $meuNovoVoto = $tipo_voto;
        }

        $stmtCount = $db->prepare("
            SELECT COALESCE(SUM(CASE WHEN voto = 1 THEN 1 ELSE 0 END), 0) AS likes,
                   COALESCE(SUM(CASE WHEN voto = -1 THEN 1 ELSE 0 END), 0) AS dislikes
            FROM comentarios_votos
            WHERE comentario_id = :c
        ");
        $stmtCount->execute([':c' => $comentario_id]);
        $placar = $stmtCount->fetch();

        responderJSON([
            "status"   => "success",
            "likes"    => (int)$placar['likes'],
            "dislikes" => (int)$placar['dislikes'],
            "meu_voto" => $meuNovoVoto
        ]);
    }

    // AÇÃO C: Excluir Comentário (Autor ou Administrador)
    if ($metodo === 'POST' && $acao === 'excluir_comentario') {
        $comentario_id = filter_input(INPUT_POST, 'comentario_id', FILTER_VALIDATE_INT);
        if (!$comentario_id) {
            responderJSON(["status" => "error", "message" => "ID do comentário inválido."], 400);
        }

        $stmtBusca = $db->prepare("
            SELECT c.id, c.usuario_id, c.conteudo, u.nickname 
            FROM comentarios_tanques c
            INNER JOIN usuarios u ON c.usuario_id = u.id
            WHERE c.id = :id LIMIT 1
        ");
        $stmtBusca->execute([':id' => $comentario_id]);
        $alvo = $stmtBusca->fetch();

        if (!$alvo) {
            responderJSON(["status" => "error", "message" => "Comentário não encontrado."], 404);
        }

        if ($meuPerfil !== 'admin' && (int)$alvo['usuario_id'] !== $usuarioLogadoId) {
            responderJSON(["status" => "error", "message" => "Permissão negada para apagar este registro."], 403);
        }

        // Exclusão em cascata controlada
        $db->prepare("DELETE FROM comentarios_votos WHERE comentario_id = :id")->execute([':id' => $comentario_id]);
        $db->prepare("DELETE FROM comentarios_tanques WHERE parent_id = :id")->execute([':id' => $comentario_id]);
        $db->prepare("DELETE FROM comentarios_tanques WHERE id = :id")->execute([':id' => $comentario_id]);

        if ($meuPerfil === 'admin') {
            registrarAuditoria($db, $alvo['nickname'], 'COMENTÁRIO REMOVIDO', "Moderação no comentário #{$comentario_id}");
        }

        // Observabilidade: Exclusão de Entidade
        $responsavel = ($meuPerfil === 'admin') ? "Admin ID {$usuarioLogadoId}" : "Autor ID {$usuarioLogadoId}";
        registrarLogApp("Exclusão de entidade: Relatório de Combate ID #{$comentario_id} removido por {$responsavel}", "ENTITY_DELETE");

        responderJSON(["status" => "success", "message" => "Registro removido com sucesso."]);
    }

    responderJSON(["status" => "error", "message" => "Ação não reconhecida."], 400);

} catch (Exception $e) {
    // Observabilidade: Registro técnico de erro interno em app.log
    registrarLogApp("Erro interno (comunidade_wiki.php): " . $e->getMessage(), "ERROR");

    // Resposta genérica sem vazamento de detalhes técnicos ao cliente
    responderJSON(["status" => "error", "message" => "Erro interno no servidor ao processar a requisição comunitária."], 500);
}
?>