<?php 
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$usuario_logado = obterUsuarioLogado();
$busca = trim($_GET['busca'] ?? '');

$talentos = listarTalentosPublicos($conexao);

// Filtro simples por busca (nome, título ou habilidades)
if (!empty($busca)) {
    $termo = mb_strtolower($busca, 'UTF-8');
    $talentos = array_filter($talentos, function($t) use ($termo) {
        $nome = mb_strtolower($t['aluno_nome'] ?? '', 'UTF-8');
        $titulo = mb_strtolower($t['titulo_profissional'] ?? '', 'UTF-8');
        $hab = mb_strtolower($t['habilidades'] ?? '', 'UTF-8');
        return (strpos($nome, $termo) !== false || strpos($titulo, $termo) !== false || strpos($hab, $termo) !== false);
    });
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco de Talentos HighTech - Escola & Mercado</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- HERO SECTION TALENTOS -->
    <div class="hero">
        <div class="container">
            <span class="badge">Hub de Contratação Tech</span>
            <h2>Banco de Talentos HighTech</h2>
            <p>Conheça os profissionais formados e em capacitação pela HighTech School. Encontre desenvolvedores, analistas de dados e gestores de TI preparados para o mercado.</p>
            <div class="hero-actions">
                <a href="#vitrine" class="btn btn-primary">Explorar Perfis 🌟</a>
                <a href="vagas.php" class="btn btn-outline">Ver Mural de Vagas 💼</a>
                <a href="portal_empresa.php#diagnostico" class="btn btn-outline">Contratar via HighTech 🏢</a>
            </div>
        </div>
    </div>

    <main class="container">
        <!-- BARRA DE PESQUISA & FILTROS -->
        <div class="admin-card" style="margin-bottom: 2rem; padding: 1.5rem;">
            <form action="talentos.php" method="get" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 260px;">
                    <label for="busca">Buscar por Especialidade, Habilidade ou Nome:</label>
                    <input type="text" name="busca" id="busca" placeholder="Ex: PHP, PostgreSQL, Desenvolvedor Júnior, Scrum..." value="<?php echo htmlspecialchars($busca); ?>">
                </div>
                <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Pesquisar Talentos 🔍</button>
                    <?php if (!empty($busca)): ?>
                        <a href="talentos.php" class="btn btn-outline">Limpar Filtro</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Profissionais Disponíveis (<?php echo count($talentos); ?>)</h2>
            <span style="font-size: 0.9rem; color: var(--text-muted);">Atualizados em tempo real</span>
        </div>

        <!-- GRID DE TALENTOS -->
        <div id="vitrine" class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
            <?php if (empty($talentos)): ?>
                <div class="alert alert-warning" style="grid-column: 1 / -1;">
                    Nenhum profissional encontrado com os critérios de busca informados.
                    <a href="talentos.php" style="color: var(--primary); font-weight: bold; text-decoration: underline;">Ver todos os talentos</a>.
                </div>
            <?php else: ?>
                <?php foreach ($talentos as $talento): ?>
                    <article class="card" style="border-top: 4px solid var(--primary);">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <div>
                                    <h3 style="margin-bottom: 0.2rem;"><?php echo htmlspecialchars($talento['aluno_nome']); ?></h3>
                                    <span class="badge" style="background: #F1F5F9; color: #475569;">Turma: <?php echo htmlspecialchars($talento['turma']); ?></span>
                                </div>
                                <span class="badge badge-success">Disponível</span>
                            </div>

                            <h4 style="color: var(--primary); font-size: 1rem; margin: 0.5rem 0 0.75rem; font-weight: 700;">
                                <?php echo htmlspecialchars($talento['titulo_profissional'] ?: 'Estudante HighTech School'); ?>
                            </h4>

                            <?php if (!empty($talento['bio'])): ?>
                                <p style="font-size: 0.92rem; color: var(--text-body); margin-bottom: 1rem;">
                                    <?php echo nl2br(htmlspecialchars($talento['bio'])); ?>
                                </p>
                            <?php endif; ?>

                            <!-- Tags de Habilidades -->
                            <?php if (!empty($talento['habilidades'])): ?>
                                <div style="margin-bottom: 1rem;">
                                    <strong style="display: block; font-size: 0.82rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem;">Competências Técnicas:</strong>
                                    <?php 
                                    $skills = explode(',', $talento['habilidades']);
                                    foreach ($skills as $skill):
                                        $s = trim($skill);
                                        if (!empty($s)):
                                    ?>
                                        <span class="pill-tag"><?php echo htmlspecialchars($s); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Links de Contato e Portfólio -->
                        <div style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <?php if (!empty($talento['linkedin'])): ?>
                                    <a href="<?php echo htmlspecialchars($talento['linkedin']); ?>" target="_blank" class="btn btn-outline" style="flex: 1; font-size: 0.85rem; padding: 0.4rem 0.6rem;">
                                        LinkedIn ↗
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($talento['github'])): ?>
                                    <a href="<?php echo htmlspecialchars($talento['github']); ?>" target="_blank" class="btn btn-outline" style="flex: 1; font-size: 0.85rem; padding: 0.4rem 0.6rem;">
                                        GitHub ↗
                                    </a>
                                <?php endif; ?>

                                <a href="mailto:<?php echo htmlspecialchars($talento['aluno_email']); ?>?subject=Contato HighTech: Oportunidade Profissional" class="btn btn-primary" style="flex: 1; font-size: 0.85rem; padding: 0.4rem 0.6rem;">
                                    Entrar em Contato ✉️
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- BANNER DE CONVITE PARA ALUNOS -->
        <div class="banner-highlight" style="margin-top: 3rem;">
            <div>
                <span class="badge" style="background: rgba(255,255,255,0.2); color: #FFF; margin-bottom: 0.5rem;">É Aluno HighTech?</span>
                <h3>Destaque seu perfil profissional para o mercado</h3>
                <p>Complete suas informações, tecnologias que domina e links do GitHub no seu painel para ser descoberto por empresas de tecnologia parceiras.</p>
            </div>
            <div>
                <?php if ($usuario_logado): ?>
                    <a href="app/meu_painel.php#perfil-profissional" class="btn btn-primary" style="background: #06B6D4; color: #0F172A;">
                        Editar Meu Perfil de Talento 
                    </a>
                <?php else: ?>
                    <a href="login/login.php" class="btn btn-primary" style="background: #06B6D4; color: #0F172A;">
                        Entrar para Criar Meu Perfil 
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
