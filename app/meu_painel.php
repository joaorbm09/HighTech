<?php 
require_once __DIR__ . '/../includes/auth.php';
exigirLogin();

require_once __DIR__ . '/../includes/functions.php';

$usuario = obterUsuarioLogado();
$mensagem = '';

// Localiza o aluno correspondente ao e-mail do usuário logado
$aluno = buscarAlunoPorEmail($conexao, $usuario['email']);

// Se o aluno ainda não existir na tabela alunos, cria automaticamente
if (!$aluno && $conexao) {
    cadastrarAluno($conexao, $usuario['nome'], null, $usuario['email'], 'HT-2026', date('Y-m-d'), true);
    $aluno = buscarAlunoPorEmail($conexao, $usuario['email']);
}

$id_aluno = $aluno['id'] ?? null;

// Salvar / Atualizar Perfil de Talento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_perfil']) && $id_aluno) {
    $titulo_profissional = trim($_POST['titulo_profissional'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $github = trim($_POST['github'] ?? '');
    $habilidades = trim($_POST['habilidades'] ?? '');
    $disponivel = isset($_POST['disponivel_mercado']) ? true : false;

    if (salvarPerfilTalento($conexao, $id_aluno, $titulo_profissional, $bio, $linkedin, $github, $habilidades, $disponivel)) {
        $mensagem = '<div class="alert alert-success">✅ <strong>Perfil atualizado com sucesso!</strong> Suas informações já estão sincronizadas com o Banco de Talentos.</div>';
    } else {
        $mensagem = '<div class="alert alert-danger">❌ Erro ao atualizar perfil profissional.</div>';
    }
}

// Inscrição Rápida em Curso Adicional
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matricular_rapido']) && $id_aluno) {
    $id_curso_novo = $_POST['id_curso'] ?? null;
    if ($id_curso_novo) {
        if (matricularAluno($conexao, $id_aluno, $id_curso_novo, 'Ativa')) {
            $mensagem = '<div class="alert alert-success">🎓 <strong>Parabéns!</strong> Você foi matriculado com sucesso no novo curso.</div>';
        } else {
            $mensagem = '<div class="alert alert-danger">❌ Erro ao realizar matrícula.</div>';
        }
    }
}

// Dados do Aluno
$meus_cursos = $id_aluno ? listarCursosDoAluno($conexao, $id_aluno) : [];
$perfil_talento = $id_aluno ? obterPerfilTalentoPorAluno($conexao, $id_aluno) : false;
$todos_cursos = listarCursos($conexao);

// Total de Horas Acumuladas
$horas_totais = 0;
foreach ($meus_cursos as $c) {
    $horas_totais += (int) $c['carga_horaria'];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Painel do Aluno - HighTech School</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <!-- CABEÇALHO DO PAINEL -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
            <div>
                <h2>Área do Aluno 🎓</h2>
                <p>Bem-vindo(a), <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>! Acompanhe seus cursos, certificados e seu perfil para oportunidades de trabalho.</p>
            </div>
            <div>
                <a href="../aplicacao.php#cursos" class="btn btn-primary">+ Explorar Novos Cursos</a>
            </div>
        </div>

        <?php echo $mensagem; ?>

        <!-- RESUMO / KPIS DO ALUNO -->
        <div class="stat-grid">
            <div class="stat-card stat-cyan">
                <span class="stat-number"><?php echo count($meus_cursos); ?></span>
                <span class="stat-label">Cursos Matriculados</span>
            </div>
            <div class="stat-card stat-success">
                <span class="stat-number"><?php echo $horas_totais; ?>h</span>
                <span class="stat-label">Carga Horária Total</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo htmlspecialchars($aluno['turma'] ?? 'HT-2026'); ?></span>
                <span class="stat-label">Turma Acadêmica</span>
            </div>
            <div class="stat-card stat-warning">
                <span class="stat-number"><?php echo (!empty($perfil_talento['disponivel_mercado'])) ? 'Ativo' : 'Oculto'; ?></span>
                <span class="stat-label">Status no Banco de Talentos</span>
            </div>
        </div>

        <!-- SEÇÃO: MEUS CURSOS -->
        <section id="meus-cursos" class="admin-card">
            <div class="section-header">
                <h2>Meus Cursos em Andamento</h2>
                <p>Veja as formações em que você está matriculado na HighTech School.</p>
            </div>

            <?php if (empty($meus_cursos)): ?>
                <div class="alert alert-warning">
                    Você ainda não está matriculado em nenhum curso. 
                    <a href="../aplicacao.php#cursos" style="color: var(--primary); font-weight: bold; text-decoration: underline;">Clique aqui para escolher um curso</a>.
                </div>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($meus_cursos as $curso): ?>
                        <article class="card">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span class="badge"><?php echo htmlspecialchars($curso['categoria']); ?></span>
                                    <span class="badge badge-success"><?php echo htmlspecialchars($curso['matricula_status']); ?></span>
                                </div>
                                <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($curso['curso_nome']); ?></h3>
                                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.75rem;"><?php echo htmlspecialchars($curso['descricao']); ?></p>
                                
                                <div style="margin: 0.75rem 0; font-size: 0.88rem;">
                                    <p><strong>Carga Horária:</strong> <?php echo htmlspecialchars($curso['carga_horaria']); ?> horas</p>
                                    <p><strong>Data de Inscrição:</strong> <?php echo date('d/m/Y', strtotime($curso['data_matricula'])); ?></p>
                                </div>

                                <!-- Barra de Progresso Simulado -->
                                <div style="margin-top: 1rem;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 600; margin-bottom: 0.3rem;">
                                        <span>Progresso da Trilha</span>
                                        <span style="color: var(--primary);">75% Concluído</span>
                                    </div>
                                    <div style="background: #E2E8F0; height: 8px; border-radius: 4px; overflow: hidden;">
                                        <div style="background: var(--primary); width: 75%; height: 100%;"></div>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem;">
                                <a href="../vagas.php" class="btn btn-outline" style="flex: 1; font-size: 0.85rem;">Ver Vagas da Área</a>
                                <span class="btn btn-primary" style="flex: 1; font-size: 0.85rem; background: var(--success); cursor: default;">Aulas Liberadas ✓</span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- SEÇÃO: PERFIL PROFISSIONAL / BANCO DE TALENTOS -->
        <section id="perfil-profissional" class="admin-card">
            <div class="section-header">
                <h2>Meu Perfil no Banco de Talentos 🌟</h2>
                <p>Preencha suas informações para que empresas parceiras que contratam da HighTech School possam encontrar você.</p>
            </div>

            <form action="meu_painel.php#perfil-profissional" method="post">
                <input type="hidden" name="salvar_perfil" value="1">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="titulo_profissional">Seu Título / Especialidade:</label>
                        <input type="text" name="titulo_profissional" id="titulo_profissional" 
                               placeholder="Ex: Desenvolvedor Back-End Júnior (PHP / PostgreSQL)"
                               value="<?php echo htmlspecialchars($perfil_talento['titulo_profissional'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="habilidades">Principais Habilidades (separadas por vírgula):</label>
                        <input type="text" name="habilidades" id="habilidades" 
                               placeholder="Ex: PHP, PostgreSQL, Git, HTML, CSS, Scrum"
                               value="<?php echo htmlspecialchars($perfil_talento['habilidades'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="linkedin">Link do seu Perfil LinkedIn:</label>
                        <input type="url" name="linkedin" id="linkedin" 
                               placeholder="https://linkedin.com/in/seuperfil"
                               value="<?php echo htmlspecialchars($perfil_talento['linkedin'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="github">Link do seu GitHub / Portfólio:</label>
                        <input type="url" name="github" id="github" 
                               placeholder="https://github.com/seuperfil"
                               value="<?php echo htmlspecialchars($perfil_talento['github'] ?? ''); ?>">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="bio">Mini Biografia / Resumo Profissional:</label>
                        <textarea name="bio" id="bio" rows="3" placeholder="Fale um pouco sobre seus objetivos profissionais, cursos que está fazendo e tecnologias de interesse..."><?php echo htmlspecialchars($perfil_talento['bio'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="disponivel_mercado" value="1" 
                                <?php echo (!empty($perfil_talento['disponivel_mercado']) || !isset($perfil_talento['disponivel_mercado'])) ? 'checked' : ''; ?>>
                            <span><strong>Quero que meu perfil seja exibido no Banco de Talentos para empresas parceiras</strong></span>
                        </label>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary">Salvar Perfil Profissional 💾</button>
                    <a href="../talentos.php" target="_blank" class="btn btn-outline">Visualizar Vitrine de Talentos ↗</a>
                </div>
            </form>
        </section>

        <!-- SEÇÃO: MATRICULAR-SE EM MAIS CURSOS -->
        <section id="mais-cursos" class="admin-card">
            <div class="section-header">
                <h2>Matrícula Express ⚡</h2>
                <p>Deseja ampliar suas habilidades? Selecione outro curso da escola para se matricular instantaneamente com sua conta:</p>
            </div>

            <form action="meu_painel.php#meus-cursos" method="post" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                <input type="hidden" name="matricular_rapido" value="1">
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label for="id_curso">Selecione o Curso:</label>
                    <select name="id_curso" id="id_curso" required>
                        <option value="">-- Escolha um curso disponível --</option>
                        <?php foreach ($todos_cursos as $c): ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo htmlspecialchars($c['nome']); ?> (<?php echo htmlspecialchars($c['categoria']); ?> - <?php echo $c['carga_horaria']; ?>h)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Confirmar Matrícula Imediata</button>
            </form>
        </section>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>
