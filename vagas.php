<?php 
/* INICIALIZAÇÃO — Carregamento de dependências obrigatórias */

// Importa o sistema de autenticação: gerencia sessão e permissões de acesso do usuário
require_once __DIR__ . '/includes/auth.php';

// Importa as funções auxiliares do sistema, incluindo listarVagas() usada mais abaixo
require_once __DIR__ . '/includes/functions.php';

/* CONTEXTO DO USUÁRIO E FILTROS — Identifica quem acessa e qual filtro foi solicitado */

// Recupera os dados do usuário logado na sessão (pode ser null se não estiver autenticado)
$usuario_logado = obterUsuarioLogado();

// Lê o filtro de modalidade enviado pela URL (ex: ?modalidade=Remoto)
// O operador ?? '' garante valor vazio como padrão, sem erro se o parâmetro não existir
$modalidade_filtro = $_GET['modalidade'] ?? '';

/* CARREGAMENTO DE DADOS — Busca todas as vagas ativas do banco de dados */

// O segundo argumento 'true' indica que devem ser listadas apenas vagas ativas/publicadas
$todas_vagas = listarVagas($conexao, true);

/* FILTRO POR MODALIDADE — Aplica o filtro de trabalho remoto, híbrido ou presencial */

// Só filtra se o usuário tiver clicado em algum botão de modalidade
if (!empty($modalidade_filtro)) {
    // array_filter percorre o array e mantém apenas os elementos onde a função retorna true
    $todas_vagas = array_filter($todas_vagas, function($v) use ($modalidade_filtro) {
        // strcasecmp compara as strings ignorando maiúsculas/minúsculas (case-insensitive)
        // Retorna 0 quando as strings são iguais — garante que 'remoto' bate com 'Remoto'
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
    <!-- Importa o arquivo de estilos CSS principal compartilhado por todo o sistema -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <!-- Inclui o cabeçalho e menu de navegação global do sistema -->
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- HERO SECTION VAGAS — Bloco de destaque no topo da página com chamada para ação -->
    <div class="hero">
        <div class="container">
            <!-- Badge informativo que resume o propósito desta página -->
            <span class="badge">Oportunidades no Mercado de TI</span>
            <!-- Título principal da página do mural de vagas -->
            <h2>Mural de Vagas &amp; Estágios de Tecnologia</h2>
            <!-- Descrição breve sobre a proposta de valor: conectar alunos com empresas parceiras -->
            <p>Conectamos alunos e formandos da HighTech School com empresas inovadoras que buscam novos talentos em desenvolvimento, dados e gestão.</p>
            <!-- Grupo de botões de ação rápida: ancoragem interna, banco de talentos e portal de empresas -->
            <div class="hero-actions">
                <!-- Ancora diretamente na listagem de vagas abaixo, melhorando a experiência -->
                <a href="#vagas-lista" class="btn btn-primary">Ver Todas as Vagas </a>
                <!-- Link para a página de banco de talentos da HighTech -->
                <a href="talentos.php" class="btn btn-outline">Banco de Talentos </a>
                <!-- Link para o portal de empresas que desejam contratar — ancora no formulário de diagnóstico -->
                <a href="portal_empresa.php#diagnostico" class="btn btn-outline">Sua Empresa quer Contratar? </a>
            </div>
        </div>
    </div>

    <main class="container">
        <!-- FILTROS DE MODALIDADE — Barra com botões para filtrar vagas por tipo de trabalho -->
        <!-- Usa flexbox para organizar o título e os botões em linha, com quebra em telas pequenas -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
            <div>
                <!-- Exibe dinamicamente a quantidade de vagas encontradas (após filtro se houver) -->
                <h2>Oportunidades Abertas (<?php echo count($todas_vagas); ?>)</h2>
                <p>Confira as posições abertas para trabalhar com tecnologia em empresas parceiras.</p>
            </div>

            <!-- Grupo de botões de filtro — cada um recarrega a página com um parâmetro GET diferente -->
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <!-- Botão "Todas": destaca com btn-primary quando nenhum filtro está ativo -->
                <a href="vagas.php" class="btn <?php echo empty($modalidade_filtro) ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;">Todas</a>

                <!-- Botão "Remotas": destaca quando o filtro ativo é exatamente 'Remoto' -->
                <a href="vagas.php?modalidade=Remoto" class="btn <?php echo ($modalidade_filtro === 'Remoto') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;">Remotas </a>

                <!-- Botão "Híbridas": destaca quando o filtro ativo é exatamente 'Híbrido' -->
                <a href="vagas.php?modalidade=Híbrido" class="btn <?php echo ($modalidade_filtro === 'Híbrido') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;">Híbridas </a>

                <!-- Botão "Presenciais": destaca quando o filtro ativo é exatamente 'Presencial' -->
                <a href="vagas.php?modalidade=Presencial" class="btn <?php echo ($modalidade_filtro === 'Presencial') ? 'btn-primary' : 'btn-outline'; ?>" style="font-size: 0.85rem; padding: 0.4rem 0.9rem;">Presenciais </a>
            </div>
        </div>

        <!-- LISTAGEM DE VAGAS — Grid responsivo com os cards de cada oportunidade -->
        <!-- id="vagas-lista" permite que o botão "Ver Todas as Vagas" do hero role a página até aqui -->
        <div id="vagas-lista" class="grid" style="grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));">
            <?php if (empty($todas_vagas)): ?>
                <!-- Mensagem de estado vazio quando nenhuma vaga corresponde ao filtro -->
                <!-- grid-column: 1 / -1 faz o elemento ocupar todas as colunas do grid -->
                <div class="alert alert-warning" style="grid-column: 1 / -1;">
                    Nenhuma vaga encontrada com o filtro selecionado no momento. 
                    <a href="vagas.php" style="color: var(--primary); font-weight: bold; text-decoration: underline;">Ver todas as vagas</a>.
                </div>
            <?php else: ?>
                <!-- Itera sobre cada vaga (já filtrada) e renderiza um card de oportunidade -->
                <?php foreach ($todas_vagas as $vaga): ?>
                    <!-- Cada artigo é um card visual de uma vaga de emprego ou estágio -->
                    <article class="card">
                        <div>
                            <!-- Cabeçalho do card: badges de tipo e modalidade + data de publicação -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <div>
                                    <!-- Badge com tipo de contrato (CLT, PJ, Estágio etc.) em tom azul/índigo -->
                                    <span class="badge" style="background: #E0E7FF; color: #4338CA;"><?php echo htmlspecialchars($vaga['tipo_contrato']); ?></span>
                                    <!-- Badge verde indicando a modalidade (Remoto, Híbrido, Presencial) -->
                                    <span class="badge badge-success"><?php echo htmlspecialchars($vaga['modalidade']); ?></span>
                                </div>
                                <!-- Data de publicação formatada no padrão brasileiro dd/mm/aaaa -->
                                <!-- strtotime() converte a string do banco para timestamp Unix, date() formata para exibição -->
                                <small style="color: var(--text-muted); font-size: 0.8rem;"><?php echo date('d/m/Y', strtotime($vaga['criado_em'])); ?></small>
                            </div>

                            <!-- Título da vaga em destaque, protegido contra XSS com htmlspecialchars -->
                            <h3 style="margin-bottom: 0.25rem; font-size: 1.25rem;"><?php echo htmlspecialchars($vaga['titulo']); ?></h3>

                            <!-- Nome da empresa e localização separados por bullet (•) -->
                            <!-- O &bull; é uma entidade HTML para o símbolo • usado como separador visual -->
                            <h4 style="color: var(--primary); font-size: 0.95rem; margin-bottom: 0.75rem; font-weight: 600;">
                                 <?php echo htmlspecialchars($vaga['empresa']); ?> &bull;  <?php echo htmlspecialchars($vaga['localizacao']); ?>
                            </h4>

                            <!-- Salário: exibido apenas se cadastrado, em verde para chamar atenção positiva -->
                            <?php if (!empty($vaga['salario'])): ?>
                                <p style="font-size: 0.9rem; font-weight: 700; color: var(--success); margin-bottom: 0.75rem;">
                                     <?php echo htmlspecialchars($vaga['salario']); ?>
                                </p>
                            <?php endif; ?>

                            <!-- Descrição detalhada da vaga; nl2br() transforma quebras de linha em <br> HTML -->
                            <p style="font-size: 0.92rem; color: var(--text-body); margin-bottom: 1rem;">
                                <?php echo nl2br(htmlspecialchars($vaga['descricao'])); ?>
                            </p>

                            <!-- Requisitos: exibidos dentro de um box destacado apenas se preenchidos -->
                            <?php if (!empty($vaga['requisitos'])): ?>
                                <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.75rem; margin-bottom: 1.25rem; font-size: 0.88rem;">
                                    <strong>Requisitos &amp; Habilidades:</strong>
                                    <!-- Exibe os requisitos como texto corrido, protegido de XSS -->
                                    <p style="margin: 0.25rem 0 0; color: var(--text-body);"><?php echo htmlspecialchars($vaga['requisitos']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- BOTÃO DE CANDIDATURA — Inteligente: abre e-mail ou link externo automaticamente -->
                        <div>
                            <?php 
                            // Protege o contato contra XSS antes de montar o link
                            $contato = htmlspecialchars($vaga['contato_candidatura'] ?? '');

                            // Verifica se o contato é um e-mail válido usando o filtro nativo do PHP
                            $is_email = filter_var($vaga['contato_candidatura'] ?? '', FILTER_VALIDATE_EMAIL);

                            // Se for e-mail: monta um link mailto: com assunto pré-preenchido para facilitar o candidato
                            // Se for URL: usa o link diretamente (formulário externo, LinkedIn etc.)
                            $href = $is_email ? 'mailto:' . $contato . '?subject=Candidatura: ' . rawurlencode($vaga['titulo']) : $contato;
                            ?>
                            <!-- target="_blank" abre em nova aba apenas para links externos (não para e-mail) -->
                            <a href="<?php echo $href; ?>" <?php echo $is_email ? '' : 'target="_blank"'; ?> class="btn btn-primary" style="width: 100%;">
                                Candidatar-se para esta Vaga ✉️
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- BANNER B2B PARA EMPRESAS — Convite para que empresas publiquem vagas no portal -->
        <div class="banner-highlight" style="margin-top: 3rem;">
            <div>
                <!-- Badge estilizado sobre fundo colorido do banner -->
                <span class="badge" style="background: rgba(255,255,255,0.2); color: #FFF; margin-bottom: 0.5rem;">Para Empresas de TI</span>
                <h3>Deseja anunciar vagas para a comunidade HighTech?</h3>
                <!-- Argumento de valor: reforça a qualidade técnica dos alunos formados -->
                <p>Nossos alunos são treinados com foco em resolução de problemas reais, lógica de programação e arquiteturas modernas de banco de dados.</p>
            </div>
            <div>
                <!-- Botão que leva ao portal de empresas, ancorando no formulário de diagnóstico -->
                <a href="portal_empresa.php#diagnostico" class="btn btn-primary" style="background: #10B981; color: #FFF;">
                    Cadastrar Vaga / Falar com Recrutamento 
                </a>
            </div>
        </div>
    </main>

    <!-- Inclui o rodapé compartilhado com links e informações institucionais -->
    <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
