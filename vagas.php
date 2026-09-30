<?php 
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$usuario_logado = obterUsuarioLogado();
$modalidade_filtro = $_GET['modalidade'] ?? '';

$todas_vagas = listarVagas($conexao, true);

// Filtro simples por modalidade se solicitado
if (!empty($modalidade_filtro)) {
    $todas_vagas = array_filter($todas_vagas, function($v) use ($modalidade_filtro) {
        return strcasecmp($v['modalidade'], $modalidade_filtro) === 0;
    });
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mural de Vagas Tech - HighTech</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- HERO SECTION VAGAS -->
    <div class="hero">
        <div class="container">
            <span class="badge">Oportunidades no Mercado de TI</span>
            <h2>Mural de Vagas & Estágios de Tecnologia</h2>
            <p>Conectamos alunos e formandos da HighTech School com empresas inovadoras que buscam novos talentos em desenvolvimento, dados e gestão.</p>
            <div class="hero-actions">
                <a href="#vagas-lista" class="btn btn-primary">Ver Todas as Vagas </a>
                <a href="talentos.php" class="btn btn-outline">Banco de Talentos </a>
                <a href="portal_empresa.php#diagnostico" class="btn btn-outline">Sua Empresa quer Contratar? </a>
            </div>
        </div>
    </div>

    <main class="container">
        <!-- FILTROS DE MODALIDADE -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
            <div>
                <h2>Oportunidades Abertas (<?php echo count($todas_vagas); ?>)</h2>
                <p>Confira as posições abertas para trabalhar com tecnologia em empresas parceiras.</p>
            </div>

            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="vagas.php" class="btn <?php echo empty($modalidade_filtro) ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;">Todas</a>
                <a href="vagas.php?modalidade=Remoto" class="btn <?php echo ($modalidade_filtro === 'Remoto') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;">Remotas </a>
                <a href="vagas.php?modalidade=Híbrido" class="btn <?php echo ($modalidade_filtro === 'Híbrido') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;">Híbridas </a>
                <a href="vagas.php?modalidade=Presencial" class="btn <?php echo ($modalidade_filtro === 'Presencial') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;">Presenciais </a>
            </div>
        </div>

        <!-- LISTAGEM DE VAGAS -->
        <div id="vagas-lista" class="grid" style="grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));">
            <?php if (empty($todas_vagas)): ?>
                <div class="alert alert-warning" style="grid-column: 1 / -1;">
                    Nenhuma vaga encontrada com o filtro selecionado no momento. 
                    <a href="vagas.php" style="color: var(--primary); font-weight: bold; text-decoration: underline;">Ver todas as vagas</a>.
                </div>
            <?php else: ?>
                <?php foreach ($todas_vagas as $vaga): ?>
                    <article class="card">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <div>
                                    <span class="badge" style="background: #E0E7FF; color: #4338CA;"><?php echo htmlspecialchars($vaga['tipo_contrato']); ?></span>
                                    <span class="badge badge-success"><?php echo htmlspecialchars($vaga['modalidade']); ?></span>
                                </div>
                                <small style="color: var(--text-muted); font-size: 0.8rem;"><?php echo date('d/m/Y', strtotime($vaga['criado_em'])); ?></small>
                            </div>

                            <h3 style="margin-bottom: 0.25rem; font-size: 1.25rem;"><?php echo htmlspecialchars($vaga['titulo']); ?></h3>
                            <h4 style="color: var(--primary); font-size: 0.95rem; margin-bottom: 0.75rem; font-weight: 600;">
                                 <?php echo htmlspecialchars($vaga['empresa']); ?> &bull;  <?php echo htmlspecialchars($vaga['localizacao']); ?>
                            </h4>

                            <?php if (!empty($vaga['salario'])): ?>
                                <p style="font-size: 0.9rem; font-weight: 700; color: var(--success); margin-bottom: 0.75rem;">
                                     <?php echo htmlspecialchars($vaga['salario']); ?>
                                </p>
                            <?php endif; ?>

                            <p style="font-size: 0.92rem; color: var(--text-body); margin-bottom: 1rem;">
                                <?php echo nl2br(htmlspecialchars($vaga['descricao'])); ?>
                            </p>

                            <?php if (!empty($vaga['requisitos'])): ?>
                                <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.75rem; margin-bottom: 1.25rem; font-size: 0.88rem;">
                                    <strong>Requisitos & Habilidades:</strong>
                                    <p style="margin: 0.25rem 0 0; color: var(--text-body);"><?php echo htmlspecialchars($vaga['requisitos']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <?php 
                            $contato = htmlspecialchars($vaga['contato_candidatura'] ?? '');
                            $is_email = filter_var($vaga['contato_candidatura'] ?? '', FILTER_VALIDATE_EMAIL);
                            $href = $is_email ? 'mailto:' . $contato . '?subject=Candidatura: ' . rawurlencode($vaga['titulo']) : $contato;
                            ?>
                            <a href="<?php echo $href; ?>" <?php echo $is_email ? '' : 'target="_blank"'; ?> class="btn btn-primary" style="width: 100%;">
                                Candidatar-se para esta Vaga ✉️
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- BANNER B2B PARA EMPRESAS -->
        <div class="banner-highlight" style="margin-top: 3rem;">
            <div>
                <span class="badge" style="background: rgba(255,255,255,0.2); color: #FFF; margin-bottom: 0.5rem;">Para Empresas de TI</span>
                <h3>Deseja anunciar vagas para a comunidade HighTech?</h3>
                <p>Nossos alunos são treinados com foco em resolução de problemas reais, lógica de programação e arquiteturas modernas de banco de dados.</p>
            </div>
            <div>
                <a href="portal_empresa.php#diagnostico" class="btn btn-primary" style="background: #10B981; color: #FFF;">
                    Cadastrar Vaga / Falar com Recrutamento 
                </a>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
