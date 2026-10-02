<?php
// A página pública verifica certificados somente pelo código aleatório.
require_once __DIR__ . '/../includes/functions.php';

$erro = '';
$certificado = false;
$codigo = isset($_GET['codigo']) && is_string($_GET['codigo'])
    ? strtolower(trim($_GET['codigo']))
    : '';
// Valida o formato UUID antes da consulta e retorna status HTTP apropriado.
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $codigo)) {
    http_response_code(400);
    $erro = 'O código informado não possui um formato válido.';
} elseif (!$conexao) {
    http_response_code(503);
    $erro = 'Não foi possível validar o certificado no momento. Tente novamente mais tarde.';
} else {
    $certificado = validarCertificadoPorCodigo($conexao, $codigo);
    if ($certificado === null) {
        http_response_code(500);
        $erro = 'Não foi possível consultar o registro do certificado.';
    } elseif (!$certificado) {
        http_response_code(404);
        $erro = 'Nenhum certificado válido foi encontrado para este código.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $certificado ? 'Certificado - ' . htmlspecialchars($certificado['curso_nome'], ENT_QUOTES, 'UTF-8') : 'Validar Certificado'; ?> - HighTech School</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .certificate {
            max-width: 900px;
            margin: 3rem auto;
            padding: 4rem 3rem;
            border: 10px double var(--primary);
            background: #fff;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }
        .certificate h1 { color: var(--text-heading); font-size: 2.5rem; margin: 1rem 0; }
        .certificate .recipient { color: var(--primary); font-size: 2rem; font-weight: 800; margin: 1.5rem 0; }
        .certificate .course { color: var(--text-heading); font-size: 1.5rem; font-weight: 700; }
        .certificate-code { overflow-wrap: anywhere; font-family: monospace; }
        /* Na impressão, remove menus e botões para a página virar um certificado limpo. */
        @media print {
            header, nav, footer, .no-print { display: none !important; }
            body { background: #fff; }
            .container { max-width: none; padding: 0; }
            .certificate { margin: 0; min-height: 95vh; box-shadow: none; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="container">
        <?php if ($erro !== ''): ?>
            <section class="admin-card" style="margin-top: 2rem;">
                <h2>Validação do certificado</h2>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></div>
            </section>
        <?php elseif ($certificado): ?>
            <article class="certificate">
                <span class="badge">Certificado verificado</span>
                <h1>Certificado de Conclusão</h1>
                <p>Certificamos que</p>
                <p class="recipient"><?php echo htmlspecialchars($certificado['aluno_nome'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>concluiu com aproveitamento o curso</p>
                <p class="course"><?php echo htmlspecialchars($certificado['curso_nome'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p style="margin-top: 1rem;">Carga horária: <?php echo (int) $certificado['carga_horaria']; ?> horas</p>
                <p>Data de emissão: <?php echo date('d/m/Y', strtotime($certificado['emitido_em'])); ?></p>
                <p style="margin-top: 2rem;">Código de validação</p>
                <p class="certificate-code"><?php echo htmlspecialchars($certificado['codigo'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="no-print" style="margin-top: 2rem;">Este certificado pode ser verificado publicamente pelo código acima.</p>
            </article>
            <div class="no-print" style="text-align: center; margin-bottom: 3rem;">
                <button class="btn btn-primary" type="button" onclick="window.print()">Imprimir / Salvar como PDF</button>
            </div>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
