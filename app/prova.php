<?php
// Exige autenticação para que prova e resultados pertençam ao aluno logado.
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();
require_once __DIR__ . '/../includes/functions.php';

$usuario = obterUsuarioLogado();
$aluno = buscarAlunoPorEmail($conexao, $usuario['email']);
$erro = '';
$mensagem_resultado = null;
$matricula = false;
$prova = false;
$questoes = [];
$tentativas = [];
$certificado = false;
$pendentes = null;
// O ID da URL apenas seleciona o curso; o servidor ainda valida a matrícula.
$id_curso_raw = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['id_curso'] ?? '')
    : ($_GET['id_curso'] ?? '');
$id_curso = filter_var(
    is_string($id_curso_raw) || is_int($id_curso_raw) ? $id_curso_raw : '',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$conexao) {
    http_response_code(503);
    $erro = 'Não foi possível conectar ao sistema. Tente novamente mais tarde.';
} elseif (!$aluno) {
    http_response_code(403);
    $erro = 'Não foi possível localizar seu cadastro acadêmico.';
} elseif ($id_curso === false) {
    http_response_code(400);
    $erro = 'O curso informado é inválido.';
} else {
    // Carrega elegibilidade e questões sem enviar o gabarito correto ao navegador.
    $matricula = obterMatriculaAtivaAlunoCurso($conexao, $aluno['id'], $id_curso);
    if ($matricula === null) {
        http_response_code(500);
        $erro = 'Não foi possível validar sua matrícula.';
    } elseif (!$matricula) {
        http_response_code(403);
        $erro = 'Você precisa de uma matrícula ativa para acessar esta prova.';
    } else {
        $prova = obterProvaDoCurso($conexao, $id_curso, true);
        if ($prova === null) {
            http_response_code(500);
            $erro = 'Não foi possível carregar a prova.';
        } elseif (!$prova) {
            http_response_code(404);
            $erro = 'Ainda não há uma prova publicada para este curso.';
        } else {
            $pendentes = contarAulasObrigatoriasPendentes(
                $conexao,
                $matricula['matricula_id'],
                $id_curso
            );
            $questoes = listarQuestoesProvaAluno($conexao, $prova['id']);
            $tentativas = listarTentativasProvaAluno(
                $conexao,
                $matricula['matricula_id'],
                $prova['id']
            );
            $certificado = obterCertificadoDaMatricula($conexao, $matricula['matricula_id']);

            if ($pendentes === null || $questoes === false || $tentativas === false || $certificado === null) {
                http_response_code(500);
                $erro = 'Ocorreu um erro ao carregar os dados da sua prova.';
            }
        }
    }
}

// O servidor recalcula a nota; não confia em notas ou respostas corretas do cliente.
if ($matricula && $prova && $_SERVER['REQUEST_METHOD'] === 'POST' && $erro === '') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_prova']) ||
        !hash_equals($_SESSION['csrf_prova'], $token)) {
        http_response_code(400);
        $erro = 'A solicitação expirou ou não pôde ser validada. Atualize a página e tente novamente.';
    } else {
        $respostas = $_POST['respostas'] ?? null;
        if (!is_array($respostas)) {
            http_response_code(400);
            $erro = 'Envie uma resposta para cada questão.';
        } else {
            $resultado = enviarTentativaProva(
                $conexao,
                $matricula['matricula_id'],
                $prova['id'],
                $respostas
            );
            if (in_array($resultado['status'], ['passed', 'failed', 'already_passed'], true)) {
                $_SESSION['resultado_prova'] = [
                    'id_curso' => $id_curso,
                    'resultado' => $resultado
                ];
                header('Location: prova.php?id_curso=' . $id_curso);
                exit;
            }
            if ($resultado['status'] === 'lessons_pending') {
                http_response_code(403);
                $erro = 'Conclua todas as aulas obrigatórias antes de fazer a prova.';
            } elseif ($resultado['status'] === 'attempts_exhausted') {
                http_response_code(403);
                $erro = 'Você usou todas as tentativas disponíveis para esta prova.';
            } elseif ($resultado['status'] === 'invalid_answers') {
                http_response_code(400);
                $erro = 'As respostas enviadas não correspondem às questões desta prova. Revise e tente novamente.';
            } elseif ($resultado['status'] === 'unavailable') {
                http_response_code(403);
                $erro = 'A prova não está mais disponível para esta matrícula.';
            } else {
                http_response_code(500);
                $erro = 'Não foi possível registrar sua tentativa. Tente novamente mais tarde.';
            }
        }
    }
}

// Recupera o resultado após o redirecionamento e então o remove da sessão.
if (!empty($_SESSION['resultado_prova']) &&
    (int) $_SESSION['resultado_prova']['id_curso'] === (int) $id_curso) {
    $mensagem_resultado = $_SESSION['resultado_prova']['resultado'];
    unset($_SESSION['resultado_prova']);
    $certificado = $matricula
        ? obterCertificadoDaMatricula($conexao, $matricula['matricula_id'])
        : $certificado;
    $tentativas = $matricula && $prova
        ? listarTentativasProvaAluno($conexao, $matricula['matricula_id'], $prova['id'])
        : $tentativas;
}

// Token de sessão evita envio da prova por páginas externas.
if (empty($_SESSION['csrf_prova'])) {
    $_SESSION['csrf_prova'] = bin2hex(random_bytes(32));
}
$pode_fazer_prova = $matricula && $prova && $questoes && $pendentes === 0 &&
    !$certificado && count($tentativas) < (int) $prova['max_tentativas'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prova do Curso - HighTech School</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="container">
        <p style="margin: 1rem 0;"><a href="meu_painel.php#meus-cursos" class="btn btn-outline">Voltar ao meu painel</a></p>
        <?php if ($erro !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php elseif ($matricula && $prova): ?>
            <section class="admin-card">
                <div class="section-header">
                    <h2>Prova: <?php echo htmlspecialchars($matricula['curso_nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p>Nota mínima: <?php echo htmlspecialchars((string) $prova['nota_minima'], ENT_QUOTES, 'UTF-8'); ?>%. Tentativas utilizadas: <?php echo count($tentativas); ?> de <?php echo (int) $prova['max_tentativas']; ?>.</p>
                </div>

                <?php if ($mensagem_resultado): ?>
                    <?php if ($mensagem_resultado['status'] === 'passed' || $mensagem_resultado['status'] === 'already_passed'): ?>
                        <div class="alert alert-success">
                            <?php if ($mensagem_resultado['status'] === 'passed'): ?>
                                Aprovado! Sua nota foi <?php echo number_format((float) $mensagem_resultado['nota'], 2, ',', '.'); ?>%.
                            <?php else: ?>
                                Você já foi aprovado nesta prova.
                            <?php endif; ?>
                            <?php if ($certificado): ?>
                                <a href="certificado.php?codigo=<?php echo urlencode($certificado['codigo']); ?>">Acessar e imprimir certificado</a>.
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            Você não atingiu a nota mínima. Nota: <?php echo number_format((float) $mensagem_resultado['nota'], 2, ',', '.'); ?>%.
                            Tentativas restantes: <?php echo (int) $mensagem_resultado['tentativas_restantes']; ?>.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($certificado): ?>
                    <div class="alert alert-success">
                        Seu certificado já está disponível.
                        <a href="certificado.php?codigo=<?php echo urlencode($certificado['codigo']); ?>">Abrir certificado</a>.
                    </div>
                <?php elseif ($pendentes > 0): ?>
                    <div class="alert alert-warning">Conclua todas as aulas obrigatórias antes de iniciar a prova. Aulas restantes: <?php echo (int) $pendentes; ?>.</div>
                <?php elseif (empty($questoes)): ?>
                    <div class="alert alert-warning">A prova ainda não possui questões publicadas. Avise a administração do curso.</div>
                <?php elseif (count($tentativas) >= (int) $prova['max_tentativas']): ?>
                    <div class="alert alert-danger">Você atingiu o limite de tentativas desta prova.</div>
                <?php elseif ($pode_fazer_prova): ?>
                    <form method="post" action="prova.php?id_curso=<?php echo (int) $id_curso; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_prova'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="id_curso" value="<?php echo (int) $id_curso; ?>">
                        <?php foreach ($questoes as $indice => $questao): ?>
                            <fieldset class="card" style="margin-bottom: 1rem; border: 1px solid var(--border-color);">
                                <legend><strong><?php echo ($indice + 1) . '. ' . htmlspecialchars($questao['enunciado'], ENT_QUOTES, 'UTF-8'); ?></strong></legend>
                                <?php foreach ($questao['alternativas'] as $alternativa): ?>
                                    <label style="display: flex; gap: 0.5rem; align-items: flex-start; margin: 0.75rem 0;">
                                        <input type="radio" name="respostas[<?php echo (int) $questao['id']; ?>]" value="<?php echo (int) $alternativa['id']; ?>" required>
                                        <span><?php echo htmlspecialchars($alternativa['texto'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Enviar suas respostas? A tentativa será registrada e não poderá ser desfeita.');">Enviar prova</button>
                    </form>
                <?php endif; ?>
            </section>

            <?php if (!empty($tentativas)): ?>
                <section class="admin-card">
                    <h3>Histórico de tentativas</h3>
                    <ul>
                        <?php foreach ($tentativas as $tentativa): ?>
                            <li>
                                <?php echo date('d/m/Y H:i', strtotime($tentativa['realizada_em'])); ?> —
                                <?php echo number_format((float) $tentativa['nota'], 2, ',', '.'); ?>% —
                                <?php echo in_array(strtolower((string) $tentativa['aprovada']), ['1', 't', 'true', 'yes'], true) ? 'Aprovada' : 'Não aprovada'; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
