<?php
// Painel privado do aluno; exige uma sessão de usuário válida.
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();
require_once __DIR__ . '/../includes/functions.php';

$usuario = obterUsuarioLogado();
// Localiza o cadastro acadêmico associado à conta; cria-o se ainda não existir.
$aluno = buscarAlunoPorEmail($conexao, $usuario['email']);

if (!$aluno && $conexao) {
    cadastrarAluno($conexao, $usuario['nome'], null, $usuario['email'], 'HT-2026', null);
    $aluno = buscarAlunoPorEmail($conexao, $usuario['email']);
}

$mensagem = '';
// O token protege a solicitação de matrícula enviada pelo formulário.
if (empty($_SESSION['csrf_matricula'])) {
    $_SESSION['csrf_matricula'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Valida o pedido de matrícula e apresenta o resultado ao aluno.
    $token = $_POST['csrf_token'] ?? '';
    $idCurso = filter_var($_POST['id_curso'] ?? '', FILTER_VALIDATE_INT);

    if (!is_string($token) || !hash_equals($_SESSION['csrf_matricula'], $token)) {
        $mensagem = 'A sessão expirou. Atualize a página e tente novamente.';
    } elseif (!$aluno || !$idCurso) {
        $mensagem = 'Selecione um curso válido.';
    } else {
        $resultado = matricularAlunoEmCursoAtivo($conexao, $aluno['id'], $idCurso);
        if ($resultado === 'created') {
            $mensagem = 'A matrícula foi realizada.';
        } elseif ($resultado === 'already_enrolled') {
            $mensagem = 'Você já está matriculado neste curso.';
        } elseif ($resultado === 'course_unavailable') {
            $mensagem = 'Este curso não está disponível para matrícula.';
        } else {
            $mensagem = 'Não foi possível realizar a matrícula.';
        }
    }
}

// Carrega os cursos já matriculados e os cursos que ainda aceitam matrícula.
$cursosAluno = $aluno ? listarCursosDoAluno($conexao, $aluno['id']) : [];
$cursosDisponiveis = $aluno
    ? listarCursosDisponiveisParaMatricula($conexao, $aluno['id'])
    : [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu painel | HighTech School</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Mostra progresso, aulas, prova e certificado disponíveis em cada curso. -->
    <main class="container">
        <h1>Meu painel</h1>
        <p>Olá, <?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?>.</p>

        <?php if ($mensagem !== ''): ?>
            <div class="alert"><?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="admin-card">
            <h2>Meus cursos</h2>
            <?php if (!$cursosAluno): ?>
                <p>Você ainda não tem matrículas.</p>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($cursosAluno as $curso): ?>
                        <?php
                        $aulasTotal = (int) $curso['aulas_total'];
                        $aulasFeitas = (int) $curso['aulas_concluidas'];
                        $obrigatoriasOk = (int) $curso['aulas_obrigatorias_total']
                            === (int) $curso['aulas_obrigatorias_concluidas'];
                        $tentativasRestantes = (int) $curso['prova_max_tentativas']
                            - (int) $curso['prova_tentativas_usadas'];
                        ?>
                        <article class="card">
                            <h3><?php echo htmlspecialchars($curso['curso_nome'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><?php echo htmlspecialchars($curso['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><?php echo $aulasFeitas; ?> de <?php echo $aulasTotal; ?> aulas concluídas</p>

                            <?php if (strcasecmp($curso['matricula_status'], 'Ativa') === 0): ?>
                                <?php if ($aulasTotal > 0): ?>
                                    <p><a class="btn" href="aulas.php?id_curso=<?php echo (int) $curso['curso_id']; ?>">Abrir aulas</a></p>
                                <?php else: ?>
                                    <p class="muted">As aulas ainda não foram publicadas.</p>
                                <?php endif; ?>

                                <?php if (!empty($curso['certificado_codigo'])): ?>
                                    <a class="btn btn-outline" href="certificado.php?codigo=<?php echo urlencode($curso['certificado_codigo']); ?>">Ver certificado</a>
                                <?php elseif (!empty($curso['prova_id']) && $obrigatoriasOk && $tentativasRestantes > 0): ?>
                                    <a class="btn btn-outline" href="prova.php?id_curso=<?php echo (int) $curso['curso_id']; ?>">Fazer prova</a>
                                <?php elseif (!empty($curso['prova_id']) && !$obrigatoriasOk): ?>
                                    <p class="muted">Conclua as aulas obrigatórias para liberar a prova.</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="muted">Matrícula inativa.</p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="admin-card">
            <h2>Matricular-se em outro curso</h2>
            <?php if (!$cursosDisponiveis): ?>
                <p>Não há outros cursos disponíveis no momento.</p>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_matricula'], ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-group">
                        <label for="id_curso">Curso</label>
                        <select name="id_curso" id="id_curso" required>
                            <option value="">Selecione um curso</option>
                            <?php foreach ($cursosDisponiveis as $curso): ?>
                                <option value="<?php echo (int) $curso['id']; ?>">
                                    <?php echo htmlspecialchars($curso['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit">Matricular</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
