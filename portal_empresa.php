<?php 
// Inclusão de Dependências (Arquivos Auxiliares)
require_once __DIR__ . '/includes/auth.php'; //auth.php: Carrega as regras de autenticação e sessão.
require_once __DIR__ . '/includes/functions.php'; // functions.php: Carrega as funções do sistema, incluindo a função de conexão com o banco de dados ($conexao) e a função que salva os dados

// Armazena o alerta que será exibido após o envio do formulário corporativo.
$mensagem = '';

// Processamento do Formulário Corporativo (B2B)
//O código verifica se a página foi acessada via método POST e se o botão acionado possui o nome solicitar_diagnostico
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_diagnostico'])) {
    // O código captura cada campo digitado pela empresa
    $nome_empresa = trim($_POST['nome_empresa'] ?? '');
    $cnpj = trim($_POST['cnpj'] ?? '');
    $responsavel = trim($_POST['responsavel'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $tamanho_equipe = trim($_POST['tamanho_equipe'] ?? '');
    // Remove espaços em branco desnecessários no início e no final do texto
    $servico_interesse = trim($_POST['servico_interesse'] ?? '');
    // vita erros do PHP caso algum campo não seja enviado, definindo um valor padrão vazio.
    $msg = trim($_POST['mensagem'] ?? '');

    // validação de campos obrigatorios
    // esta garantindo que os campos obrigatórios n estejam vazios pra serem salvos no banco de dados
    if (!empty($nome_empresa) && !empty($responsavel) && !empty($email) && !empty($servico_interesse)) {
        // chamando a função, passando a conexãp do banco e dados coletados
        if (cadastrarSolicitacaoEmpresa($conexao, $nome_empresa, $cnpj, $responsavel, $email, $telefone, $tamanho_equipe, $servico_interesse, $msg)) {
            // se der true ele retornara uma mensagem de sucesso estilizada com a classe Bootstrap alert-success.
            $mensagem = '<div class="alert alert-success">✅ <strong>Solicitação enviada com sucesso!</strong> Nossa equipe de especialistas entrará em contato para agendar o diagnóstico corporativo.</div>';
        } else {
            // se der false ele retornara uma mensagem de erro (alert-danger)
            $mensagem = '<div class="alert alert-danger">❌ Ocorreu um erro ao registrar sua solicitação. Por favor, tente novamente.</div>';
        }
    } else {
        // se por acaso algum campo obrigatório não for preenchido vai retorna um alerta de aviso (alert-warning) 
        $mensagem = '<div class="alert alert-warning">⚠️ Por favor, preencha todos os campos obrigatórios (Empresa, Responsável, E-mail e Serviço de Interesse).</div>';
    }
}
?>
<!-- Estrutura HTML da página pública do portal corporativo. -->
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Define codificação, responsividade, título da aba e estilos compartilhados. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HighTech - Inovações e Negócios</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <!-- Inclui o cabeçalho e menu reutilizados em todo o sistema. -->
    <?php include __DIR__ . '/includes/header.php' ;?>
    <!-- Hero section -->

    <!-- Define a área visual principal do topo da página -->
    <div class="hero">
        <!-- Garante que o conteúdo textual e os botões não fiquem espalhados pelas bordas de telas muito largas -->
        <div class="container">
            <!-- serve para criar um emblema, etiqueta ou marcador visual pequeno no site  -->
            <span class="badge">Tecnologia & Inovação</span>
            <h2>Soluções Corporativas & Capacitação de alto nivel</h2>
            <p>Impulsionamos empresas e profissionais através de transformação digital, automação e formação especializada integrada ao banco de dados.</p>
        </div>
    </div>

    <!-- CONTEUDO PRINCIPAL -->
    <!-- definindo a sessao que sera modificada no css utilizando o container quando classificamos algo -->
    <main class="container">
        <!-- verifica, se existe algum aviso de erro de acesso negado no site -->
        <?php if (isset($_GET['erro']) && $_GET['erro'] == 'acesso_negado'): ?>
            <!-- criando uma caixa de aviso -->
            <div class="alert alert-warning" style="margin-top: 1.5rem;">
                Acesso negado: você precisa ter perfil de Administrador para acessar os painéis de gestão.
            </div>
            <?php endif; ?>

        <!-- NUMEROS E DIFERENCIAIS -->
         <!-- Funciona como a "caixa mãe" que segura todos os cartões. -->
         <!-- Resume dados e diferenciais do projeto em cartões estatísticos. -->
         <div class="stat-grid">
            <div class="stat-card">
                <!-- Exibe o destaque em texto grande e negrito (ex: "4", "100%", "PostgreSQL"). -->
                <span class="stat-number">4</span>
                <!-- Exibe a legenda explicativa logo abaixo do destaque. -->
                <span class="stat-label">Foco em prática e mercado real</span>
            </div>
            <!-- A classe stat-success aplica uma cor verde (ou azul/positiva) ao número ou à borda do cartão para indicar sucesso ou qualidade. -->
            <div class="stat-car stat-succes">
                <span class="stat-number">100%</span>
                <span class="stat-label">Foco em prática e mercado Real</span>
            </div>
            <!-- A classe stat-cyan dá um tom ciano/azul ao cartão para destacar a tecnologia informada ("PostgreSQL"). -->
            <div class="stat-card stat-cyan">
                <span class="stat-number">PostgreSQL</span>
                <span class="stat-label">Arquitetura de Dados Relacional</span>
            </div>
            <!-- A classe stat-warning aplica um tom amarelado ou alaranjado para chamar atenção ao portal de talentos da empresa. -->
            <div class="stat-card stat-warning">
                <span class="stat-number">Talent hub</span>
                <span class="stat-label">Ponte direta alunos e emrpesas</span>
            </div>
         </div>

         <!-- SEÇÃO: PILARES DE SERVIÇOS PAR EMPRESAS -->
        <!-- Apresenta os serviços oferecidos a empresas em cartões independentes. -->
        <section id="servicos">
            <!-- cabeçalho a seção que define o título e a introdução da seção de serviços para instruir o visitante sobre o tipo de apoio digital que a empresa oferece -->
            <div class="section-header">
                <h2>SErvios e apoio para empresas de tecnologias</h2>
                <p>Desenvolvemos soluções sob medida para apoirar empresas em diferentes estágios de maturidade digital:</p>
            </div>
            <!-- GRADE DE SERVIÇOS -->
            <div class="grid">
                <!-- aqui contem 4 cards onde cada um é dedicado a uma área  estratégica -->
                <article class="card">
                    <!-- Consultoria em Banco de Dados e TI -->
                    <div>
                        <span class="badge">Infra e dados</span>
                        <h3>consultoria em banco de dados e TI</h3>
                        <p>Otimização de arquitetura, modelagem de dados relacional em PostgreSQL, alta performance, integridade referencial e segurança de informações. </p>
                        <ul style="margin: 1rem 0 1rem 1.25rem; font-size: 0.95rem; color: var(--text-body);">
                            <li>Modelagem relacional e queries otimizadas</li>
                            <li>Auditoria de desempeho e concorrencia</li>
                            <li>Segurança e integridade de registros</li>
                        </ul>
                    </div>
                    <a href="#diagnostico" class="btn btn-outline">Solicitar consultoria</a>
                </article>

                <!-- Fábrica de Software e Automação -->
                <article class="card">
                    <div>
                        <span class="badge">Engenharia</span>
                        <h3>Fábrica de software e automação</h3>
                        <p>Contrução de aplicações web dinâmicas, migração de rotinas manuais ou planilhas para sistemas modernos em PHP com relatórios automatizados. </p>
                        <ul style="margin: 1rem 0 1rem 1.25rem; font-size: 0.95rem; color: var(--text-body);">
                            <li>Desevolvimento web corporativo sob demanda</li>
                            <li>Integração de APIs e automações internas</li>
                            <li>Painés de controle e dashboards de gestão </li>
                        </ul>
                    </div>
                    <a href="#diagnostico" class="btn btn-outline">Pedir orçamento</a>
                </article>

                <!-- reinamento In-Company -->
                <article class="card">
                    <div>
                        <span class="badge">Capacitação</span>
                        <h3>Treinamento In-Company</h3>
                        <p>Capacitação customizada para o seu time de tecnologia. Leve a metodologia prática da HighTech School para dentro da sua equipe técnica.</p>
                        <ul style="margin: 1rem 0 1rem 1.25rem; font-size: 0.95rem; color: var(--text-body);">
                            <li>Programação web moderna e boas práticas</li>
                            <li>GEstão ágil de projetos(scrum e Kanban)</li>
                            <li>Transformação digital de depatamentos</li>
                        </ul>
                    </div>
                    <a href="#diagnostico" class="btn btn-outline">Capacitar minha equipe</a>
                </article>

                <!-- Talent Hunting e Estágios Tech -->
                <article class="card">
                    <div>
                        <span class="badge" style="background: #FDF2F8; color: #DB2777;">Recrutamento</span>
                        <h3>Taent Hunting e Estágios Tech</h3>
                        <p>Encontre os melhores alunos e recém-formados da HighTech school prontos para acelerar o desenvolvimento da sua euipe técnica</p>
                        <ul style="margin: 1rem 0 1rem 1.25rem; font-size: 0.95rem; color: var(--text-body);">
                            <li>Acesso priotritário ao Bacno de talentos</li>
                            <li>divulgação de vagas exclusivas para estudantes</li>
                            <li>Alunos treinados em projetos reais de banco e código</li>
                        </ul>
                    </div>
                    <a href="vagas.php" class="btn btn-primary">Var mural de vagas</a>
                </article>
            </div>
        </section>

        <!-- BANNER DE INTEGRAÇÃO COM A ESCOLA -->
        <!-- Banner direciona visitantes para os cursos e a vitrine de talentos. -->
        <div class="banner-highlight">
            <div>
                <span class="badge" style="background: rgba(255,255,255,0.2); color: #FFF; margin-bottom: 0.5rem;">Sinergia Completa</span>
                <h3>Conheça a HighTech school</h3>
                <p>Nossa escola técnica capacita estudantes e profissionais com metodologias de mercado. Cursos completos com projetos práticos e emissão de certificado</p>
            </div>
            <div style="display: flex; gap: 0.75rem;flex-wrap: wrap;">
                <a href="aplicacao.php" class="btn btn-primary" style="background: #06B6D4; color: #0F172A;">Ver Cursos da Escola </a>
                <a href="talentos.php" class="btn btn-outline" style="background: transparent; color: #FFF; border-color: rgba(255,255,255,0.4);">Buscar Talentos </a>
            </div>
        </div>
        
        <!-- SEÇÃO: FORMULÁRIO DE DIAGNÓSTICO E PROPOSTA B2B -->
        <!-- Formulário B2B envia as informações para o processamento PHP no início do arquivo. -->
        <section id="diagnostico" class="admin-card">
            <div class="section-header">
                <h2>Solicite um Diagnóstico Tecnológico Gratuito</h2>
                <p>Conte-nos um pouco sobre a sua empresa e o desafio que deseja solucionar. Nossos especialistas entrarão em contato para estruturar a melhor proposta.</p>
            </div>

            <!-- POST evita colocar dados de contato e mensagem na URL. -->
            <form action="portal_empresa.php#diagnostico" method="post">
                <input type="hidden" name="solicitar_diagnostico" value="1">

                <!-- Agrupa os campos do contato e do serviço solicitado em um layout responsivo. -->
                <div class="form-grid">
                    <!-- Identifica a organização que está solicitando o diagnóstico. -->
                    <div class="form-group">
                        <label for="nome_empresa">Nome da Empresa / Organização: *</label>
                        <input type="text" name="nome_empresa" id="nome_empresa" required placeholder="Ex: InovaTech Soluções">
                    </div>

                    <!-- CNPJ é opcional e serve como dado complementar da organização. -->
                    <div class="form-group">
                        <label for="cnpj">CNPJ (Opcional):</label>
                        <input type="text" name="cnpj" id="cnpj" placeholder="00.000.000/0001-00">
                    </div>

                    <!-- Informa quem deve receber o retorno da equipe HighTech. -->
                    <div class="form-group">
                        <label for="responsavel">Nome do Responsável / Cargo: *</label>
                        <input type="text" name="responsavel" id="responsavel" required placeholder="Ex: Ana Souza - Gerente de TI">
                    </div>

                    <!-- E-mail corporativo é obrigatório para permitir o contato posterior. -->
                    <div class="form-group">
                        <label for="email">E-mail Corporativo: *</label>
                        <input type="email" name="email" id="email" required placeholder="contato@empresa.com">
                    </div>

                    <!-- Telefone é um canal alternativo de contato, por isso não é obrigatório. -->
                    <div class="form-group">
                        <label for="telefone">Telefone / WhatsApp:</label>
                        <input type="tel" name="telefone" id="telefone" placeholder="(11) 98765-4321">
                    </div>

                    <!-- Faixas predefinidas registram o porte da equipe da organização. -->
                    <div class="form-group">
                        <label for="tamanho_equipe">Tamanho da Empresa:</label>
                        <select name="tamanho_equipe" id="tamanho_equipe">
                            <option value="1 a 10 colaboradores">1 a 10 colaboradores (Startup / Pequena)</option>
                            <option value="11 a 50 colaboradores">11 a 50 colaboradores (Média)</option>
                            <option value="51 a 200 colaboradores">51 a 200 colaboradores</option>
                            <option value="Mais de 200 colaboradores">Mais de 200 colaboradores (Enterprise)</option>
                        </select>
                    </div>

                    <!-- Serviço de interesse é obrigatório para orientar o diagnóstico solicitado. -->
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="servico_interesse">Qual serviço sua empresa precisa? *</label>
                        <select name="servico_interesse" id="servico_interesse" required>
                            <option value="">-- Selecione o serviço de interesse --</option>
                            <option value="Consultoria em TI & Banco de Dados">Consultoria em TI & Banco de Dados (PostgreSQL / Infra)</option>
                            <option value="Fábrica de Software & Automação">Fábrica de Software & Automação de Processos Web</option>
                            <option value="Treinamento Corporativo de Equipe">Treinamento Corporativo de Equipe (In-Company)</option>
                            <option value="Contratação de Talentos / Recrutamento">Contratação de Talentos / Recrutamento da Escola</option>
                            <option value="Diagnóstico Tecnológico Completo">Diagnóstico Tecnológico Completo</option>
                        </select>
                    </div>

                    <!-- Campo livre permite descrever cenário, necessidade ou objetivo do projeto. -->
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="mensagem">Descreva o cenário atual ou objetivo do seu projeto:</label>
                        <textarea name="mensagem" id="mensagem" rows="4" placeholder="Ex: Precisamos automatizar nossos relatórios diários de vendas e migrar os dados legados para PostgreSQL..."></textarea>
                    </div>
                </div>

                <!-- Envia os campos para validação e gravação da solicitação no banco. -->
                <button type="submit" class="btn btn-primary" style="padding: 0.8rem 1.8rem; font-size: 1rem;">
                    Enviar Solicitação de Diagnóstico 📤
                </button>
            </form>
        </section>
    </main>

    <!-- Inclui o rodapé institucional compartilhado pelas páginas do sistema. -->
    <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>