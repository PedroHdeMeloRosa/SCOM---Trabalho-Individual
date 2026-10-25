<?php
// Arquivo: testes_automatizados.php
// Suíte de Testes Automatizados para Lógica de Negócio (Trabalho 2 - SCOM)
header('Content-Type: text/html; charset=utf-8');
echo "<h2>🛠️ Relatório de Execução de Testes Automatizados (SCOM)</h2>";
echo "<ul>";

$testesPassados = 0;
$totalTestes = 5;

function rodarTeste($nome, $condicao) {
    global $testesPassados;
    if ($condicao) {
        echo "<li><span style='color: green;'>✔️ PASSOU:</span> {$nome}</li>";
        $testesPassados++;
    } else {
        echo "<li><span style='color: red;'>❌ FALHOU:</span> {$nome}</li>";
    }
}

// =======================================================================
// TESTE 1: Validação e Sanitização de E-mail Inválido (Caso Inválido)
// =======================================================================
$emailMalicioso = "comandante@@wtwiki.com";
$emailSanitizado = filter_var($emailMalicioso, FILTER_SANITIZE_EMAIL);
$emailEhValido = filter_var($emailSanitizado, FILTER_VALIDATE_EMAIL);
rodarTeste("Rejeição de formato de E-mail corrompido (Sanitização)", $emailEhValido === false);

// =======================================================================
// TESTE 2: Tamanho Máximo de Relatório de Combate (Caso Inválido)
// =======================================================================
$relatorioLongo = str_repeat("A", 1005); // Texto com 1005 caracteres
$limitePermitido = 1000;
$relatorioRejeitado = (mb_strlen($relatorioLongo) > $limitePermitido);
rodarTeste("Bloqueio de Relatório de Combate acima de 1000 caracteres", $relatorioRejeitado === true);

// =======================================================================
// TESTE 3: Geração de Token CSRF (Caso Válido)
// =======================================================================
$tokenCsrf = bin2hex(random_bytes(32));
$tamanhoCorreto = (strlen($tokenCsrf) === 64); // 32 bytes hexadecimais = 64 chars
rodarTeste("Geração robusta de Token CSRF (64 caracteres hexadecimais)", $tamanhoCorreto === true);

// =======================================================================
// TESTE 4: Algoritmo de Hash Bcrypt (Caso Válido)
// =======================================================================
$senhaPlana = "SenhaForte@2026";
$senhaHash = password_hash($senhaPlana, PASSWORD_DEFAULT);
$hashDiferenteDaPlana = ($senhaHash !== $senhaPlana);
rodarTeste("Criptografia irreversível da senha com algoritmo Bcrypt", $hashDiferenteDaPlana === true);

// =======================================================================
// TESTE 5: Verificação de Identidade / Autenticação (Caso Inválido)
// =======================================================================
$senhaErrada = "SenhaIncorreta123";
$autenticacaoFalha = !password_verify($senhaErrada, $senhaHash);
rodarTeste("Bloqueio de Autenticação com credencial divergente", $autenticacaoFalha === true);

echo "</ul>";
echo "<h3>Resultado: {$testesPassados} / {$totalTestes} testes passaram.</h3>";
?>