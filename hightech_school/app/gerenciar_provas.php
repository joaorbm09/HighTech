<?php
// Área administrativa de configuração de provas e gerenciamento de questões.
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();
require_once __DIR__ . '/../includes/functions.php';

$erro = '';
$mensagem = '';
$questao_edicao = null;
$cursos = $conexao
    ? $conexao->query('SELECT id, nome, ativo FROM cursos ORDER BY nome ASC')->fetchAll()
    : false;
$id_curso_raw = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['id_curso'] ?? '')
    : ($_GET['curso_id'] ?? '');
$id_curso = filter_var(
    is_string($id_curso_raw) || is_int($id_curso_raw) ? $id_curso_raw : '',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
// Confere banco, curso e token antes de aceitar alterações nas provas.
if (!$conexao) {
    http_response_code(503);
    $erro = 'Não foi possível conectar ao banco de dados. Tente novamente mais tarde.';
} elseif ($cursos === false) {
    http_response_code(500);
    $erro = 'Não foi possível carregar os cursos.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_provas_admin']) ||
        !hash_equals($_SESSION['csrf_provas_admin'], $token)) {
        http_response_code(400);
        $erro = 'A solicitação expirou ou não pôde ser validada. Atualize a página e tente novamente.';
    } elseif ($id_curso === false) {
        http_response_code(400);
        $erro = 'Selecione um curso válido.';
    } else {
        $curso_selecionado = false;
        foreach ($cursos as $curso) {
            if ((int) $curso['id'] === $id_curso) {
                $curso_selecionado = $curso;
                break;
            }
        }

        if (!$curso_selecionado) {
            http_response_code(400);
            $erro = 'O curso selecionado não existe.';
        } else {
            // Cada formulário envia uma ação: configurar, publicar ou editar questão.
            $acao = $_POST['acao'] ?? '';
            $prova = obterProvaDoCurso($conexao, $id_curso);

            if ($prova === null) {
                http_response_code(500);
                $erro = 'Não foi possível carregar a avaliação deste curso.';
            } elseif ($acao === 'salvar_configuracao') {
                // Valida nota mínima e número máximo de tentativas.
                $nota_raw = $_POST['nota_minima'] ?? '';
                $nota = filter_var(
                    is_string($nota_raw) || is_int($nota_raw) || is_float($nota_raw) ? $nota_raw : '',
                    FILTER_VALIDATE_FLOAT
                );
                $limite_raw = $_POST['max_tentativas'] ?? '';
                $limite = filter_var(
                    is_string($limite_raw) || is_int($limite_raw) ? $limite_raw : '',
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1, 'max_range' => 20]]
                );
                if ($nota === false || $nota < 1 || $nota > 100) {
                    $erro = 'A nota mínima deve estar entre 1 e 100.';
                } elseif ($limite === false) {
                    $erro = 'O limite deve ser um número entre 1 e 20 tentativas.';
                } elseif (salvarProvaDoCurso($conexao, $id_curso, $nota, $limite)) {
                    $_SESSION['flash_provas_admin'] = 'Configuração da prova salva.';
                    header('Location: gerenciar_provas.php?curso_id=' . $id_curso);
                    exit;
                } else {
                    $erro = 'Não foi possível salvar a configuração da prova.';
                }
            } elseif ($acao === 'alternar_publicacao' && $prova) {
                $ativa = in_array(strtolower((string) $prova['ativa']), ['1', 't', 'true', 'yes'], true);
                $resultado_publicacao = definirProvaAtiva($conexao, $prova['id'], !$ativa);
                if (in_array($resultado_publicacao, ['published', 'unpublished', 'unchanged'], true)) {
                    $_SESSION['flash_provas_admin'] = $ativa
                        ? 'Prova desativada. Questões e resultados foram preservados.'
                        : 'Prova publicada para alunos que concluíram as aulas obrigatórias.';
                    header('Location: gerenciar_provas.php?curso_id=' . $id_curso);
                    exit;
                }
                $erro = $resultado_publicacao === 'invalid_questions'
                    ? 'Antes de publicar, cadastre pelo menos uma questão com quatro alternativas e exatamente uma correta.'
                    : 'Não foi possível alterar a publicação da prova.';
            } elseif ($acao === 'salvar_questao' && $prova) {
                // Lê e valida enunciado, ordem, quatro alternativas e resposta correta.
                $id_questao_value = $_POST['id_questao'] ?? '';
                $id_questao_raw = is_string($id_questao_value) || is_int($id_questao_value)
                    ? trim((string) $id_questao_value)
                    : 'invalid';
                $id_questao = $id_questao_raw === ''
                    ? null
                    : filter_var($id_questao_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $enunciado = is_string($_POST['enunciado'] ?? null)
                    ? trim($_POST['enunciado'])
                    : '';
                $ordem_raw = $_POST['ordem'] ?? '';
                $ordem = filter_var(
                    is_string($ordem_raw) || is_int($ordem_raw) ? $ordem_raw : '',
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );
                $alternativas_recebidas = $_POST['alternativas'] ?? null;
                $alternativas_validas = is_array($alternativas_recebidas) &&
                    count($alternativas_recebidas) === 4;
                $alternativas = [];
                for ($indice = 0; $indice < 4; $indice++) {
                    $valor = is_array($alternativas_recebidas)
                        ? ($alternativas_recebidas[$indice] ?? null)
                        : null;
                    if (!is_string($valor)) $alternativas_validas = false;
                    $alternativas[$indice] = is_string($valor) ? trim($valor) : '';
                }
                $correta_raw = $_POST['correta'] ?? '';
                $indice_correto = filter_var(
                    is_string($correta_raw) || is_int($correta_raw) ? $correta_raw : '',
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 0, 'max_range' => 3]]
                );

                if ($id_questao_raw !== '' && $id_questao === false) {
                    http_response_code(400);
                    $erro = 'O identificador da questão é inválido.';
                } elseif ($enunciado === '') {
                    $erro = 'Preencha o enunciado da questão.';
                } elseif ($ordem === false) {
                    $erro = 'A ordem deve ser um número inteiro maior que zero.';
                } elseif (!$alternativas_validas || in_array('', $alternativas, true)) {
                    $erro = 'Preencha as quatro alternativas.';
                } elseif ($indice_correto === false) {
                    $erro = 'Selecione a alternativa correta.';
                } else {
                    $resultado = salvarQuestaoProva(
                        $conexao,
                        $prova['id'],
                        $id_questao,
                        $enunciado,
                        $ordem,
                        $alternativas,
                        $indice_correto
                    );
                    if ($resultado === 'created' || $resultado === 'updated') {
                        $_SESSION['flash_provas_admin'] = $resultado === 'created'
                            ? 'Questão adicionada à prova.'
                            : 'Questão atualizada.';
                        header('Location: gerenciar_provas.php?curso_id=' . $id_curso);
                        exit;
                    }
                    $erro = $resultado === 'order_conflict'
                        ? 'Já existe uma questão nessa posição. Escolha outro número para a ordem.'
                        : ($resultado === 'not_found'
                            ? 'A questão não pertence a esta prova ou não existe.'
                            : 'Não foi possível salvar a questão.');
                }

                if ($erro !== '') {
                    $questao_edicao = [
                        'id' => $id_questao ?: '',
                        'enunciado' => $enunciado,
                        'ordem' => $_POST['ordem'] ?? '',
                        'alternativas' => $alternativas,
                        'correta' => $indice_correto === false ? 0 : $indice_correto
                    ];
                }
            } elseif ($acao === 'excluir_questao' && $prova) {
                $id_questao_raw = $_POST['id_questao'] ?? '';
                $id_questao = filter_var(
                    is_string($id_questao_raw) || is_int($id_questao_raw) ? $id_questao_raw : '',
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );
                if ($id_questao === false) {
                    http_response_code(400);
                    $erro = 'O identificador da questão é inválido.';
                } elseif (excluirQuestaoProva($conexao, $prova['id'], $id_questao)) {
                    $_SESSION['flash_provas_admin'] = 'Questão removida. As notas já registradas foram preservadas.';
                    header('Location: gerenciar_provas.php?curso_id=' . $id_curso);
                    exit;
                } else {
                    $erro = 'Não foi possível remover a questão. Verifique se ela pertence a esta prova.';
                }
            } else {
                http_response_code(400);
                $erro = 'A prova precisa estar configurada e a ação solicitada deve ser válida.';
            }
        }
    }
}
if (!empty($_SESSION['flash_provas_admin'])) {
    $mensagem = $_SESSION['flash_provas_admin'];
    unset($_SESSION['flash_provas_admin']);
}

if ($id_curso === false && $cursos) {
    foreach ($cursos as $curso) {
        if (in_array(strtolower((string) $curso['ativo']), ['1', 't', 'true', 'yes'], true)) {
            $id_curso = (int) $curso['id'];
            break;
        }
    }
    if ($id_curso === false) $id_curso = (int) $cursos[0]['id'];
}

$curso_selecionado = null;
$prova = false;
$questoes = [];
$proxima_ordem = 1;
if ($conexao && $cursos && $id_curso !== false && $id_curso !== null) {
    foreach ($cursos as $curso) {
        if ((int) $curso['id'] === $id_curso) {
            $curso_selecionado = $curso;
            break;
        }
    }
    if (!$curso_selecionado) {
        http_response_code(404);
        $erro = 'O curso selecionado não existe.';
    } else {
        $prova = obterProvaDoCurso($conexao, $id_curso);
        if ($prova === null) {
            http_response_code(500);
            $erro = 'Não foi possível carregar a prova do curso.';
        } elseif ($prova) {
            $questoes = listarQuestoesProvaAdmin($conexao, $prova['id']);
            if ($questoes === false) {
                http_response_code(500);
                $erro = 'Não foi possível carregar as questões.';
            } else {
                foreach ($questoes as $questao) {
                    $proxima_ordem = max($proxima_ordem, $questao['ordem'] + 1);
                }
            }
        }
    }
}

if (!$questao_edicao && isset($_GET['editar_questao']) && $prova && is_array($questoes)) {
    $id_edicao = filter_var(
        is_string($_GET['editar_questao']) || is_int($_GET['editar_questao']) ? $_GET['editar_questao'] : '',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($id_edicao === false) {
        http_response_code(400);
        $erro = 'A questão informada para edição é inválida.';
    } else {
        foreach ($questoes as $questao) {
            if ($questao['id'] === $id_edicao) {
                $questao_edicao = $questao;
                break;
            }
        }
        if (!$questao_edicao) {
            http_response_code(404);
            $erro = 'A questão solicitada não existe nesta prova.';
        }
    }
}
if (empty($_SESSION['csrf_provas_admin'])) {
    $_SESSION['csrf_provas_admin'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_provas_admin'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Provas - HighTech School</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="container">
        <section class="admin-card">
            <div class="section-header">
                <h2>Gestão de provas</h2>
                <p>Configure nota mínima e tentativas, depois cadastre questões de múltipla escolha para cada curso.</p>
            </div>
            <?php if ($erro !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($mensagem !== ''): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($cursos === false): ?>
            <?php elseif (empty($cursos)): ?>
                <div class="alert alert-warning">Cadastre um curso antes de configurar uma prova.</div>
            <?php else: ?>
                <form method="get" action="gerenciar_provas.php" class="form-group" style="max-width: 600px; margin-bottom: 2rem;">
                    <label for="curso_id">Curso:</label>
                    <select name="curso_id" id="curso_id" required onchange="this.form.submit()">
                        <option value="">Selecione um curso</option>
                        <?php foreach ($cursos as $curso): ?>
                            <option value="<?php echo (int) $curso['id']; ?>" <?php echo $id_curso === (int) $curso['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($curso['nome'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php if ($curso_selecionado): ?>
                    <?php
                        $nota_atual = $prova ? $prova['nota_minima'] : 70;
                        $tentativas_atual = $prova ? $prova['max_tentativas'] : 3;
                        $prova_ativa = $prova && in_array(
                            strtolower((string) $prova['ativa']),
                            ['1', 't', 'true', 'yes'],
                            true
                        );
                    ?>
                    <form action="gerenciar_provas.php?curso_id=<?php echo $id_curso; ?>" method="post" class="admin-card" style="background: var(--bg-main);">
                        <h3><?php echo $prova ? 'Configuração da prova' : 'Criar prova do curso'; ?></h3>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="acao" value="salvar_configuracao">
                        <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nota_minima">Nota mínima para aprovação (%)</label>
                                <input type="number" name="nota_minima" id="nota_minima" min="1" max="100" step="0.01" required value="<?php echo htmlspecialchars((string) $nota_atual, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="max_tentativas">Máximo de tentativas</label>
                                <input type="number" name="max_tentativas" id="max_tentativas" min="1" max="20" required value="<?php echo (int) $tentativas_atual; ?>">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Salvar configuração</button>
                    </form>

                    <?php if ($prova): ?>
                        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin: 1.5rem 0;">
                            <span class="badge <?php echo $prova_ativa ? 'badge-success' : ''; ?>"><?php echo $prova_ativa ? 'Prova publicada' : 'Prova desativada'; ?></span>
                            <form method="post" action="gerenciar_provas.php?curso_id=<?php echo $id_curso; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="acao" value="alternar_publicacao">
                                <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">
                                <button class="btn <?php echo $prova_ativa ? 'btn-outline' : 'btn-primary'; ?>" type="submit"><?php echo $prova_ativa ? 'Desativar prova' : 'Publicar prova'; ?></button>
                            </form>
                        </div>

                        <?php
                            $valores_formulario = $questao_edicao ?: [];
                            $alternativas_formulario = $valores_formulario['alternativas'] ?? ['', '', '', ''];
                            while (count($alternativas_formulario) < 4) $alternativas_formulario[] = '';
                            $correta_formulario = 0;
                            foreach ($alternativas_formulario as $indice => $alternativa) {
                                if (is_array($alternativa)) {
                                    if (!empty($alternativa['correta'])) $correta_formulario = $indice;
                                    $alternativas_formulario[$indice] = $alternativa['texto'];
                                }
                            }
                        ?>
                        <form action="gerenciar_provas.php?curso_id=<?php echo $id_curso; ?>" method="post" class="admin-card" style="background: var(--bg-main);">
                            <h3><?php echo $questao_edicao ? 'Editar questão' : 'Adicionar questão'; ?></h3>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="acao" value="salvar_questao">
                            <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">
                            <input type="hidden" name="id_questao" value="<?php echo htmlspecialchars((string) ($valores_formulario['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-grid">
                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label for="enunciado">Enunciado *</label>
                                    <textarea name="enunciado" id="enunciado" rows="3" required><?php echo htmlspecialchars((string) ($valores_formulario['enunciado'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="ordem_questao">Ordem *</label>
                                    <input type="number" name="ordem" id="ordem_questao" min="1" required value="<?php echo htmlspecialchars((string) ($valores_formulario['ordem'] ?? $proxima_ordem), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <?php for ($indice = 0; $indice < 4; $indice++): ?>
                                    <div class="form-group">
                                        <label for="alternativa_<?php echo $indice; ?>">Alternativa <?php echo chr(65 + $indice); ?> *</label>
                                        <input type="text" name="alternativas[<?php echo $indice; ?>]" id="alternativa_<?php echo $indice; ?>" required value="<?php echo htmlspecialchars((string) ($alternativas_formulario[$indice] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                <?php endfor; ?>
                                <div class="form-group">
                                    <label for="correta">Resposta correta *</label>
                                    <select name="correta" id="correta" required>
                                        <?php for ($indice = 0; $indice < 4; $indice++): ?>
                                            <option value="<?php echo $indice; ?>" <?php echo (int) ($valores_formulario['correta'] ?? $correta_formulario) === $indice ? 'selected' : ''; ?>>
                                                Alternativa <?php echo chr(65 + $indice); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1rem;">
                                <button type="submit" class="btn btn-primary"><?php echo $questao_edicao ? 'Salvar questão' : 'Adicionar questão'; ?></button>
                                <?php if ($questao_edicao): ?>
                                    <a href="gerenciar_provas.php?curso_id=<?php echo $id_curso; ?>" class="btn btn-outline">Cancelar edição</a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <h3 style="margin: 2rem 0 1rem;">Questões cadastradas</h3>
                        <?php if ($questoes === false): ?>
                        <?php elseif (empty($questoes)): ?>
                            <div class="alert alert-warning">Cadastre pelo menos uma questão com quatro alternativas antes de liberar a prova.</div>
                        <?php else: ?>
                            <div class="grid">
                                <?php foreach ($questoes as $questao): ?>
                                    <article class="card">
                                        <div>
                                            <span class="badge">Questão <?php echo (int) $questao['ordem']; ?></span>
                                            <h4><?php echo htmlspecialchars($questao['enunciado'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                            <ol style="margin: 0.75rem 0 0 1.25rem;">
                                                <?php foreach ($questao['alternativas'] as $alternativa): ?>
                                                    <li><?php echo htmlspecialchars($alternativa['texto'], ENT_QUOTES, 'UTF-8'); ?><?php echo $alternativa['correta'] ? ' — correta' : ''; ?></li>
                                                <?php endforeach; ?>
                                            </ol>
                                        </div>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 1rem;">
                                            <a class="btn btn-outline" href="gerenciar_provas.php?curso_id=<?php echo $id_curso; ?>&editar_questao=<?php echo (int) $questao['id']; ?>">Editar</a>
                                            <form method="post" action="gerenciar_provas.php?curso_id=<?php echo $id_curso; ?>" onsubmit="return confirm('Remover esta questão da prova? Notas já registradas não serão alteradas.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="acao" value="excluir_questao">
                                                <input type="hidden" name="id_curso" value="<?php echo $id_curso; ?>">
                                                <input type="hidden" name="id_questao" value="<?php echo (int) $questao['id']; ?>">
                                                <button type="submit" class="btn btn-outline">Remover questão</button>
                                            </form>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
