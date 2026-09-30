<?php 
/* INICIALIZAÇÃO — Carregamento de dependências obrigatórias */

// Importa o sistema de autenticação: controla sessão, login e permissões de acesso
require_once __DIR__ . '/includes/auth.php';

// Importa as funções do sistema, incluindo listarTalentosPublicos() usada logo abaixo
require_once __DIR__ . '/includes/functions.php';

/* CONTEXTO DO USUÁRIO E BUSCA — Identifica o usuário e coleta o termo pesquisado */

// Recupera os dados do usuário da sessão ativa (null se não estiver logado)
$usuario_logado = obterUsuarioLogado();

// Lê o termo de busca digitado pelo usuário no campo de pesquisa (enviado via GET)
// trim() remove espaços extras nas bordas do texto antes de processar
$busca = trim($_GET['busca'] ?? '');

/* CARREGAMENTO DE DADOS — Busca todos os perfis públicos de talentos do banco */

// Retorna apenas os alunos que optaram por tornar seu perfil visível (disponíveis para contratação)
$talentos = listarTalentosPublicos($conexao);

/* FILTRO POR BUSCA — Filtra os talentos pelo termo digitado no campo de pesquisa */

// Filtro simples por busca (nome, título ou habilidades)
// Só executa se o campo de busca não estiver vazio
if (!empty($busca)) {

    // Converte o termo de busca para minúsculas com suporte a UTF-8 (para comparar corretamente acentos)
    $termo = mb_strtolower($busca, 'UTF-8');

    // array_filter percorre o array mantendo apenas os talentos que correspondem ao critério
    $talentos = array_filter($talentos, function($t) use ($termo) {

        // Normaliza cada campo do talento para minúsculas antes de comparar — garante busca sem distinção de caso
        $nome  = mb_strtolower($t['aluno_nome']          ?? '', 'UTF-8');
        $titulo = mb_strtolower($t['titulo_profissional'] ?? '', 'UTF-8');
        $hab   = mb_strtolower($t['habilidades']          ?? '', 'UTF-8');

        // Retorna true se o termo aparecer em qualquer um dos três campos — busca ampla e amigável
        // strpos() retorna false quando não encontra, então !== false confirma que encontrou
        return (strpos($nome, $termo) !== false || strpos($titulo, $termo) !== false || strpos($hab, $termo) !== false);
    });
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Define codificação UTF-8 para suportar caracteres especiais e acentuação em português -->
    <meta charset="UTF-8">
    <!-- Garante responsividade da página em dispositivos móveis e tablets -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título da aba do navegador com foco no banco de talentos da HighTech -->
    <title>Banco de Talentos HighTech - Escola &amp; Mercado</title>
    <!-- Arquivo CSS principal com todos os estilos visuais do projeto -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <!-- Inclui o cabeçalho e menu de navegação compartilhado por todas as páginas -->
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- HERO SECTION TALENTOS — Bloco de destaque no topo com apresentação da vitrine de profissionais -->
    <div class="hero">
        <div class="container">
            <!-- Badge que posiciona esta página como um hub de contratação de profissionais tech -->
            <span class="badge">Hub de Contratação Tech</span>
            <!-- Título principal do banco de talentos -->
            <h2>Banco de Talentos HighTech</h2>
            <!-- Descrição do público disponível: desenvolvedores, analistas e gestores de TI -->
            <p>Conheça os profissionais formados e em capacitação pela HighTech School. Encontre desenvolvedores, analistas de dados e gestores de TI preparados para o mercado.</p>
            <!-- Botões de navegação rápida: vitrine de perfis, mural de vagas e portal para empresas -->
            <div class="hero-actions">
                <!-- Ancora até a vitrine de cards de talentos abaixo na mesma página -->
                <a href="#vitrine" class="btn btn-primary">Explorar Perfis 🌟</a>
                <!-- Link para a página de vagas abertas -->
                <a href="vagas.php" class="btn btn-outline">Ver Mural de Vagas 💼</a>
                <!-- Link para o portal de empresas com ancora no formulário de diagnóstico -->
                <a href="portal_empresa.php#diagnostico" class="btn btn-outline">Contratar via HighTech 🏢</a>
            </div>
        </div>
    </div>

    <main class="container">
        <!-- BARRA DE PESQUISA & FILTROS — Formulário de busca por nome, especialidade ou habilidade -->
        <!-- Usa admin-card como fundo diferenciado para destacar visualmente a área de filtro -->
        <div class="admin-card" style="margin-bottom: 2rem; padding: 1.5rem;">
            <!-- Método GET para que os filtros apareçam na URL e possam ser compartilhados ou recarregados -->
            <form action="talentos.php" method="get" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <!-- Campo de busca flexível: aceita tecnologias, cargos ou nomes -->
                <div class="form-group" style="flex: 1; min-width: 260px;">
                    <label for="busca">Buscar por Especialidade, Habilidade ou Nome:</label>
                    <!-- value pré-preenche o campo com o termo atual para o usuário não perder o contexto -->
                    <input type="text" name="busca" id="busca" placeholder="Ex: PHP, PostgreSQL, Desenvolvedor Júnior, Scrum..." value="<?php echo htmlspecialchars($busca); ?>">
                </div>
                <!-- Área dos botões alinhada verticalmente ao final do campo de busca -->
                <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
                    <!-- Botão de envio: dispara a busca ao clicar -->
                    <button type="submit" class="btn btn-primary">Pesquisar Talentos 🔍</button>
                    <?php if (!empty($busca)): ?>
                        <!-- Botão "Limpar Filtro" só aparece quando há um termo ativo — remove o parâmetro GET -->
                        <a href="talentos.php" class="btn btn-outline">Limpar Filtro</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Cabeçalho da seção: exibe quantos profissionais foram encontrados após o filtro -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <!-- count() retorna o número de elementos no array, mesmo após o array_filter -->
            <h2>Profissionais Disponíveis (<?php echo count($talentos); ?>)</h2>
            <!-- Indicação de que os dados vêm do banco em tempo real, sem cache estático -->
            <span style="font-size: 0.9rem; color: var(--text-muted);">Atualizados em tempo real</span>
        </div>

        <!-- GRID DE TALENTOS — Vitrine responsiva com os cards de cada profissional disponível -->
        <!-- id="vitrine" permite que o botão "Explorar Perfis" do hero role até aqui -->
        <div id="vitrine" class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
            <?php if (empty($talentos)): ?>
                <!-- Estado vazio: nenhum talento encontrado com os critérios informados -->
                <!-- grid-column: 1 / -1 faz o alerta ocupar toda a largura do grid -->
                <div class="alert alert-warning" style="grid-column: 1 / -1;">
                    Nenhum profissional encontrado com os critérios de busca informados.
                    <a href="talentos.php" style="color: var(--primary); font-weight: bold; text-decoration: underline;">Ver todos os talentos</a>.
                </div>
            <?php else: ?>
                <!-- Itera sobre cada talento (já filtrado) e renderiza seu card de perfil -->
                <?php foreach ($talentos as $talento): ?>
                    <!-- Borda superior colorida na cor primária diferencia visualmente os cards de talentos -->
                    <article class="card" style="border-top: 4px solid var(--primary);">
                        <div>
                            <!-- Cabeçalho do card: nome, turma e indicador de disponibilidade -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                                <div>
                                    <!-- Nome completo do aluno como título principal do card -->
                                    <h3 style="margin-bottom: 0.2rem;"><?php echo htmlspecialchars($talento['aluno_nome']); ?></h3>
                                    <!-- Turma do aluno serve como referência temporal da formação -->
                                    <span class="badge" style="background: #F1F5F9; color: #475569;">Turma: <?php echo htmlspecialchars($talento['turma']); ?></span>
                                </div>
                                <!-- Badge verde fixo "Disponível" sinaliza que o aluno está aberto a oportunidades -->
                                <span class="badge badge-success">Disponível</span>
                            </div>

                            <!-- Título profissional do aluno (cargo/especialização); fallback para texto padrão se vazio -->
                            <!-- O operador ?: retorna o primeiro valor se verdadeiro, caso contrário o segundo -->
                            <h4 style="color: var(--primary); font-size: 1rem; margin: 0.5rem 0 0.75rem; font-weight: 700;">
                                <?php echo htmlspecialchars($talento['titulo_profissional'] ?: 'Estudante HighTech School'); ?>
                            </h4>

                            <!-- Bio/Resumo: exibida apenas se o aluno tiver preenchido esse campo no painel -->
                            <?php if (!empty($talento['bio'])): ?>
                                <p style="font-size: 0.92rem; color: var(--text-body); margin-bottom: 1rem;">
                                    <!-- nl2br() converte quebras de linha do banco em tags <br> para o HTML -->
                                    <?php echo nl2br(htmlspecialchars($talento['bio'])); ?>
                                </p>
                            <?php endif; ?>

                            <!-- Tags de Habilidades — exibe as competências técnicas como pílulas coloridas -->
                            <?php if (!empty($talento['habilidades'])): ?>
                                <div style="margin-bottom: 1rem;">
                                    <!-- Rótulo da seção em caixa alta para diferenciação visual -->
                                    <strong style="display: block; font-size: 0.82rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem;">Competências Técnicas:</strong>
                                    <?php 
                                    // explode() divide a string de habilidades em um array usando vírgula como separador
                                    // Ex: "PHP, PostgreSQL, Scrum" vira ['PHP', ' PostgreSQL', ' Scrum']
                                    $skills = explode(',', $talento['habilidades']);
                                    foreach ($skills as $skill):
                                        // trim() remove espaços extras deixados pela vírgula (ex: " PostgreSQL" → "PostgreSQL")
                                        $s = trim($skill);
                                        // Garante que a habilidade não é uma string vazia antes de renderizar
                                        if (!empty($s)):
                                    ?>
                                        <!-- Cada habilidade é renderizada como uma pílula estilizada (pill-tag) -->
                                        <span class="pill-tag"><?php echo htmlspecialchars($s); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Links de Contato e Portfólio — separados do conteúdo principal por uma linha -->
                        <div style="margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                            <!-- Botões lado a lado com flex-wrap para reorganizar em telas menores -->
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <?php if (!empty($talento['linkedin'])): ?>
                                    <!-- Link do LinkedIn: abre em nova aba para não tirar o usuário da página -->
                                    <a href="<?php echo htmlspecialchars($talento['linkedin']); ?>" target="_blank" class="btn btn-outline" style="flex: 1; font-size: 0.85rem; padding: 0.4rem 0.6rem;">
                                        LinkedIn ↗
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($talento['github'])): ?>
                                    <!-- Link do GitHub: útil para empresas avaliarem o portfólio de código do candidato -->
                                    <a href="<?php echo htmlspecialchars($talento['github']); ?>" target="_blank" class="btn btn-outline" style="flex: 1; font-size: 0.85rem; padding: 0.4rem 0.6rem;">
                                        GitHub ↗
                                    </a>
                                <?php endif; ?>

                                <!-- Link de contato direto via e-mail com assunto pré-preenchido para facilitar o recrutador -->
                                <a href="mailto:<?php echo htmlspecialchars($talento['aluno_email']); ?>?subject=Contato HighTech: Oportunidade Profissional" class="btn btn-primary" style="flex: 1; font-size: 0.85rem; padding: 0.4rem 0.6rem;">
                                    Entrar em Contato ✉️
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- BANNER DE CONVITE PARA ALUNOS — Incentiva alunos a completarem seu perfil no painel -->
        <div class="banner-highlight" style="margin-top: 3rem;">
            <div>
                <!-- Badge diferenciado no banner para identificar que a mensagem é dirigida a alunos -->
                <span class="badge" style="background: rgba(255,255,255,0.2); color: #FFF; margin-bottom: 0.5rem;">É Aluno HighTech?</span>
                <h3>Destaque seu perfil profissional para o mercado</h3>
                <!-- Instrução prática: o que o aluno precisa fazer para aparecer nesta vitrine -->
                <p>Complete suas informações, tecnologias que domina e links do GitHub no seu painel para ser descoberto por empresas de tecnologia parceiras.</p>
            </div>
            <div>
                <?php if ($usuario_logado): ?>
                    <!-- Aluno logado: botão leva diretamente para a seção de perfil profissional no painel -->
                    <!-- A âncora #perfil-profissional leva o usuário ao formulário correto dentro do painel -->
                    <a href="app/meu_painel.php#perfil-profissional" class="btn btn-primary" style="background: #06B6D4; color: #0F172A;">
                        Editar Meu Perfil de Talento 
                    </a>
                <?php else: ?>
                    <!-- Aluno não logado: redireciona para login antes de permitir editar o perfil -->
                    <a href="login/login.php" class="btn btn-primary" style="background: #06B6D4; color: #0F172A;">
                        Entrar para Criar Meu Perfil 
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Inclui o rodapé compartilhado com informações institucionais e links do sistema -->
    <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
