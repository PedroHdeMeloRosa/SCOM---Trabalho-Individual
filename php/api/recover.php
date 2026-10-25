<?php
// Arquivo: php/api/esqueci_senha.php (Despacho de Token de Redefinição via SMTP)
ob_start();
ini_set('display_errors', 0);

require_once '../config/bootstrap.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../PHPMailer/Exception.php';
require_once '../PHPMailer/PHPMailer.php';
require_once '../PHPMailer/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJSON(["status" => "error", "message" => "Método não permitido."], 405);
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
if (empty($email)) {
    responderJSON(["status" => "error", "message" => "Informe o e-mail cadastrado."], 400);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("SELECT id, nickname FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Geração de token alfanumérico seguro de 6 dígitos com expiração de 15 minutos
        $token  = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $update = $db->prepare("UPDATE usuarios SET token_recuperacao = :token, token_expira = :expira WHERE id = :id");
        $update->execute([
            ':token'  => $token,
            ':expira' => $expira,
            ':id'     => $user['id']
        ]);

        // Carregamento de credenciais SMTP via .env
        $envPath  = __DIR__ . '/../../.env';
        $smtpUser = '';
        $smtpPass = '';

        if (file_exists($envPath)) {
            $env = parse_ini_file($envPath);
            if ($env) {
                $smtpUser = $env['SMTP_USER'] ?? '';
                $smtpPass = $env['SMTP_PASS'] ?? '';
            }
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($smtpUser, 'SCOM - War Thunder Wiki');
            $mail->addAddress($email, $user['nickname']);
            $mail->isHTML(true);
            $mail->Subject = 'SCOM: Solicitação de Redefinição de Chave Criptográfica';

            $htmlEmail = "
            <div style='background-color:#111; color:#fff; font-family:Arial, sans-serif; padding:30px; border: 2px solid #444; border-radius:8px; max-width:500px; margin:auto;'>
                <div style='text-align:center; border-bottom:1px solid #F2B400; padding-bottom:15px; margin-bottom:20px;'>
                    <h2 style='color:#F2B400; margin:0; letter-spacing:2px;'>SCOM WT WIKI</h2>
                    <p style='color:#888; font-size:12px; margin-top:5px;'>COMANDO CENTRAL TÁTICO</p>
                </div>
                <p>Atenção Comandante <strong>" . htmlspecialchars($user['nickname'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                <p>Recebemos uma solicitação de emergência para redefinir a sua chave de acesso aos nossos servidores.</p>
                <div style='background-color:#1e1e1e; border-left:4px solid #F2B400; padding:15px; margin:20px 0; text-align:center;'>
                    <p style='margin:0; color:#aaa; font-size:12px;'>CÓDIGO DE AUTORIZAÇÃO (TOKEN):</p>
                    <h1 style='color:#F2B400; font-family:monospace; letter-spacing:5px; margin:10px 0;'>{$token}</h1>
                </div>
                <p style='color:#ff4d4d; font-size:12px;'>Este código de autorização tem validade máxima de <strong>15 minutos</strong>. Se você não solicitou esta alteração, ignore esta transmissão imediatamente; a sua chave atual permanece segura.</p>
                <br>
                <p style='font-size:11px; color:#666; text-align:center; border-top:1px dashed #333; padding-top:15px;'>Esta é uma transmissão automatizada. Não responda a este e-mail.</p>
            </div>";

            $mail->Body = $htmlEmail;
            $mail->send();

            // Observabilidade: Registro da emissão de token com sucesso
            registrarLogApp("Token de recuperação emitido e despachado para: {$email}", "AUTH_RECOVERY");

        } catch (Exception $e) {
            // Observabilidade: Falha no transporte SMTP
            registrarLogApp("Falha no satélite SMTP ao despachar token ({$email}): " . $mail->ErrorInfo, "ERROR");

            if (ob_get_length()) ob_clean();
            responderJSON(["status" => "error", "message" => "Falha no satélite de comunicação. Não foi possível despachar o e-mail."], 500);
        }
    } else {
        // E-mail não localizado: Registra no log para auditoria de segurança
        registrarLogApp("Solicitação de token para e-mail não registrado: {$email}", "AUTH_FAIL");
    }

    // Resposta uniforme independente da existência do e-mail para mitigar enumeração
    if (ob_get_length()) ob_clean();
    responderJSON([
        "status"  => "success",
        "message" => "Se o e-mail estiver registrado em nossa base tática, as instruções de recuperação foram despachadas para sua caixa postal (verifique também a pasta Spam)."
    ], 200);

} catch (PDOException $e) {
    registrarLogApp("Erro interno de banco (esqueci_senha.php): " . $e->getMessage(), "ERROR");

    if (ob_get_length()) ob_clean();
    responderJSON(["status" => "error", "message" => "Erro interno no servidor de dados."], 500);
}
?>