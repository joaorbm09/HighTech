<?php
// Área do aluno para consultar aulas e marcar as aulas concluídas.
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();
require_once __DIR__ . '/../includes/functions.php';

$usuario = obterUsuarioLogado();
$aluno = buscarAlunoPorEmail($conexao, $usuario['email']);
$erro = '';
$mensagem = '';
$matricula = false;
$aulas = [];
$id_curso_raw = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['id_curso'] ?? '')
    : ($_GET['id_curso'] ?? '');
$id_curso = filter_var(
    is_string($id_curso_raw) || is_int($id_curso_raw) ? $id_curso_raw : '',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

// Valida conexão, cadastro, curso e matrícula antes de liberar o conteúdo.
if (!$conexao) {
    http_response_code(503);
    $erro = 'Não foi possível conectar ao sistema. Tente novamente mais tarde.';
} elseif (!$aluno) {
    http_response_code(403);
    $erro = 'Não foi possível localizar seu cadastro acadêmico. Acesse Meu Painel para sincronizá-lo ou fale com a administração.';
} elseif ($id_curso === false) {
    http_response_code(400);
    $erro = 'O curso informado é inválido.';
} else {
    $matricula = obterMatriculaAtivaAlunoCurso($conexao, $aluno['id'], $id_curso);
    if ($matricula === null) {
        http_response_code(500);
        $erro = 'Não foi possível validar sua matrícula no momento.';
    } elseif (!$matricula) {
        http_response_code(403);
        $erro = 'Você ainda não tem matrícula ativa neste curso. Acesse Meu Painel, faça sua matrícula e volte para ver as aulas.';
    }
}
// Um POST marca uma aula; o token e a matrícula são conferidos antes de salvar.
if ($matricula && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token_enviado = $_POST['csrf_token'] ?? '';
    $token_sessao = $_SESSION['csrf_aulas'] ?? '';
    if (!is_string($token_enviado) || $token_sessao === '' || !hash_equals($token_sessao, $token_enviado)) {
        http_response_code(400);
        $erro = 'A solicitação expirou ou não pôde ser validada. Atualize a página e tente novamente.';
    } else {
        $id_aula = filter_var(
            $_POST['id_aula'] ?? '',
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($id_aula === false) {
            http_response_code(400);
            $erro = 'A aula informada é inválida.';
        } else {
            $resultado = concluirAulaDaMatricula($conexao, $matricula['matricula_id'], $id_aula);
            if ($resultado === true) {
                $mensagem = 'Aula marcada como concluída.';
            } elseif ($resultado === false) {
                $erro = 'Não foi possível registrar a conclusão desta aula. Verifique se ela pertence a este curso e tente novamente.';
            } else {
                http_response_code(500);
                $erro = 'Ocorreu um erro ao registrar sua conclusão. Tente novamente mais tarde.';
            }
        }
    }
}
// Carrega as aulas publicadas depois de preparar o token do formulário.
if ($matricula) {
    if (empty($_SESSION['csrf_aulas'])) {
        $_SESSION['csrf_aulas'] = bin2hex(random_bytes(32));
    }
    $aulas = listarAulasDaMatricula($conexao, $matricula['matricula_id']);
    if ($aulas === false) {
        http_response_code(500);
        $erro = 'Não foi possível carregar as aulas deste curso no momento.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aulas do Curso - HighTech School</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <!-- Exibe o conteúdo das aulas somente para quem tem matrícula ativa. -->
    <main class="container">
        <p style="margin: 1rem 0;"><a href="meu_painel.php#meus-cursos" class="btn btn-outline">Voltar ao meu painel</a></p>

        <?php if ($erro !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php elseif ($matricula): ?>
            <section class="admin-card">
                <div class="section-header">
                    <span class="badge"><?php echo htmlspecialchars($matricula['categoria'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <h2><?php echo htmlspecialchars($matricula['curso_nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo htmlspecialchars($matricula['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <?php if ($mensagem !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <?php if (empty($aulas)): ?>
                    <div class="alert alert-warning">Ainda não há aulas publicadas para este curso. Volte mais tarde.</div>
                <?php else: ?>
                    <div class="grid">
                        <?php foreach ($aulas as $aula): ?>
                            <article class="card">
                                <div>
                                    <span class="badge">Aula <?php echo (int) $aula['ordem']; ?></span>
                                    <?php if ($aula['concluida']): ?>
                                        <span class="badge badge-success">Concluída</span>
                                    <?php else: ?>
                                        <span class="badge">Pendente</span>
                                    <?php endif; ?>
                                    <h3><?php echo htmlspecialchars($aula['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <?php if (!empty($aula['descricao'])): ?>
                                        <p><?php echo nl2br(htmlspecialchars($aula['descricao'], ENT_QUOTES, 'UTF-8')); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($aula['conteudo'])): ?>
                                        <div style="margin-top: 1rem;">
                                            <?php echo nl2br(htmlspecialchars($aula['conteudo'], ENT_QUOTES, 'UTF-8')); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php
                                        $video_url = filter_var($aula['video_url'] ?? '', FILTER_VALIDATE_URL);
                                        $video_scheme = $video_url ? strtolower((string) parse_url($video_url, PHP_URL_SCHEME)) : '';
                                    ?>
                                    <?php if ($video_url && in_array($video_scheme, ['http', 'https'], true)): ?>
                                        <p style="margin-top: 1rem;">
                                            <a href="<?php echo htmlspecialchars($video_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Abrir material da aula</a>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <?php if (!$aula['concluida']): ?>
                                    <form method="post" action="aulas.php?id_curso=<?php echo (int) $matricula['curso_id']; ?>" style="margin-top: 1rem;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_aulas'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="id_curso" value="<?php echo (int) $matricula['curso_id']; ?>">
                                        <input type="hidden" name="id_aula" value="<?php echo (int) $aula['id']; ?>">
                                        <button type="submit" class="btn btn-primary">Marcar como concluída</button>
                                    </form>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
