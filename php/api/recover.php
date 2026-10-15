<?php
// Arquivo: php/api/recover.php
ob_start();
ini_set('display_errors', 0);
require_once '../config/database.php';

// Importa o PHPMailer (Ajuste o caminho se necessário)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método não permitido."]); exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
if (empty($email)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Informe o e-mail cadastrado."]); exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT id, nickname FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Gera token de 6 caracteres (Fica melhor para o usuário digitar na tela)
        $token = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)); 
        $expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $update = $db->prepare("UPDATE usuarios SET token_recuperacao = :token, token_expira = :expira WHERE id = :id");
        $update->execute([':token' => $token, ':expira' => $expira, ':id' => $user['id']]);

        // ==========================================
        // DISPARO DO E-MAIL VIA SMTP DO GMAIL
        // ==========================================
        
        // Busca as credenciais escondidas no arquivo .env
        $envPath = __DIR__ . '/../../.env';
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
            // Configurações do Servidor
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser; // Usa a variável do .env
            $mail->Password   = $smtpPass; // Usa a variável do .env
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            // Destinatário
            $mail->setFrom($smtpUser, 'SCOM - War Thunder Wiki');
            $mail->addAddress($email, $user['nickname']);

            // Conteúdo do E-mail Estilizado (Tema Dark/Gold)
            $mail->isHTML(true);
            $mail->Subject = 'SCOM: Solicitação de Redefinição de Chave Criptográfica';
            
            $htmlEmail = "
            <div style='background-color:#111; color:#fff; font-family:Arial, sans-serif; padding:30px; border: 2px solid #444; border-radius:8px; max-width:500px; margin:auto;'>
                <div style='text-align:center; border-bottom:1px solid #F2B400; padding-bottom:15px; margin-bottom:20px;'>
                    <h2 style='color:#F2B400; margin:0; letter-spacing:2px;'>SCOM WT WIKI</h2>
                    <p style='color:#888; font-size:12px; margin-top:5px;'>COMANDO CENTRAL TÁTICO</p>
                </div>
                <p>Atenção Comandante <strong>{$user['nickname']}</strong>,</p>
                <p>Recebemos uma solicitação de emergência para redefinir a sua chave de acesso aos nossos servidores.</p>
                <div style='background-color:#1e1e1e; border-left:4px solid #F2B400; padding:15px; margin:20px 0; text-align:center;'>
                    <p style='margin:0; color:#aaa; font-size:12px;'>CÓDIGO DE AUTORIZAÇÃO (TOKEN):</p>
                    <h1 style='color:#F2B400; font-family:monospace; letter-spacing:5px; margin:10px 0;'>{$token}</h1>
                </div>
                <p style='color:#ff4d4d; font-size:12px;'>Este código de autorização tem validade máxima de <strong>15 minutos</strong>. Se você não solicitou esta alteração, ignore esta transmissão imediatamente; a sua chave atual permanece segura.</p>
                <br>
                <p style='font-size:11px; color:#666; text-align:center; border-top:1px dashed #333; padding-top:15px;'>Esta é uma transmissão automatizada. Não responda a este e-mail.</p>
            </div>
            ";
            
            $mail->Body = $htmlEmail;
            $mail->send();

            ob_clean();
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Transmissão efetuada. Verifique sua caixa de e-mail (e a pasta Spam)."]);
            exit;
        } catch (Exception $e) {
            ob_clean();
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Falha no satélite de comunicação. Não foi possível despachar o e-mail."]);
            exit;
        }
    }

    ob_clean();
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Se o e-mail estiver registrado, as instruções foram despachadas."]);
    exit;

} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno no servidor de dados."]);
    exit;
}
?>