<?php 
/* INICIALIZAÇÃO — Carregamento de dependências obrigatórias */

// Importa o sistema de autenticação: verifica sessão, controla login e permissões de acesso
require_once __DIR__ . '/includes/auth.php';

// Importa as funções reutilizáveis do sistema (buscar cursos, cadastrar aluno, matricular etc.)
require_once __DIR__ . '/includes/functions.php';

/* CONTEXTO DO USUÁRIO — Identificação de quem está acessando a página */

// Recupera os dados do usuário da sessão ativa (retorna null se não estiver logado)
$usuario_logado = obterUsuarioLogado();

// Variável que armazena mensagens de feedback para o usuário (sucesso, erro ou aviso)
$mensagem = '';

/* VERIFICAÇÃO DE REDIRECIONAMENTO — Detecta se o usuário foi bloqueado por falta de permissão */

// Se a URL contiver ?erro=acesso_negado, exibe aviso explicando que é necessário ser Administrador
if (isset($_GET['erro']) && $_GET['erro'] === 'acesso_negado') {
    $mensagem = '<div class="alert alert-warning">Acesso negado: Você precisa ter perfil de Administrador para acessar os painéis de gestão.</div>';
}

/* PROCESSAMENTO DO FORMULÁRIO — Trata a inscrição enviada pelo aluno via POST */

// Só executa se a requisição for POST e o campo oculto 'inscrever' tiver sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscrever'])) {

    // Captura os dados do formulário; o operador ?? '' garante que nunca haverá erro se o campo estiver ausente
    $nome    = $_POST['nome']     ?? '';
    $cpf     = $_POST['cpf']      ?? '';
    $email   = $_POST['email']    ?? '';
    $id_curso = $_POST['id_curso'] ?? '';

    // Define a turma padrão — todos inscritos por este formulário entram na turma HT-2026
    $turma = 'HT-2026';

    // Validação da data de nascimento: opcional, mas se informada exige entre 14 e 100 anos
    $nasc_input = trim($_POST['nasc'] ?? '');
    $nasc_erro = '';
    $nasc = validarDataNascimento($nasc_input, $nasc_erro);

    /* VALIDAÇÃO BÁSICA — Garante que os campos obrigatórios foram preenchidos antes de prosseguir */

    if ($nasc === false) {
        $mensagem = '<div class="alert alert-warning">⚠️ ' . htmlspecialchars($nasc_erro) . '</div>';
    } else if (!$conexao) {
        $mensagem = '<div class="alert alert-danger">⚠️ Sem conexão com o banco de dados. Tente novamente mais tarde.</div>';
    } else if (!empty($nome) && !empty($email) && !empty($id_curso)) {

        // Normaliza e busca o aluno existente pelo e-mail
        $aluno_db = buscarAlunoPorEmail($conexao, $email);
        $id_aluno = null;

        if ($aluno_db) {
            // Aluno já existe: reutiliza o ID dele para a matrícula
            $id_aluno = $aluno_db['id'];
        } else {
            // Aluno novo: cadastra na tabela alunos
            if (cadastrarAluno($conexao, $nome, $cpf, $email, $turma, $nasc, true)) {
                $novo_aluno = buscarAlunoPorEmail($conexao, $email);
                $id_aluno = $novo_aluno['id'] ?? null;
            }
        }

        /* MATRÍCULA — Associa o aluno ao curso selecionado evitando duplicidade ativa */
        if ($id_aluno) {
            $resultado_matricula = matricularAlunoEmCursoAtivo($conexao, $id_aluno, $id_curso);

            if ($resultado_matricula === 'created') {
                $tem_usuario = buscarUsuarioPorEmail($conexao, $email);
                if ($tem_usuario) {
                    $mensagem = '<div class="alert alert-success">🎓 <strong>Inscrição realizada com sucesso!</strong> Faça seu <a href="login/login.php" style="font-weight: 700; text-decoration: underline;">Login aqui</a> para acessar suas aulas.</div>';
                } else {
                    $mensagem = '<div class="alert alert-success">🎓 <strong>Inscrição realizada com sucesso!</strong> Como este é seu primeiro acesso, <a href="login/cadastrar.php" style="font-weight: 700; text-decoration: underline;">crie sua senha aqui</a> para entrar no portal e assistir às aulas.</div>';
                }
            } elseif ($resultado_matricula === 'already_enrolled') {
                $mensagem = '<div class="alert alert-warning">Você já possui uma matrícula ativa neste curso. Acesse seu <a href="app/meu_painel.php" style="font-weight: 700; text-decoration: underline;">Painel do Aluno</a> para ver as aulas.</div>';
            } elseif ($resultado_matricula === 'course_unavailable') {
                $mensagem = '<div class="alert alert-warning">Este curso não está disponível para novas matrículas no momento.</div>';
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao realizar inscrição no curso. Tente novamente mais tarde.</div>';
            }
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao registrar os dados do aluno. Tente novamente.</div>';
        }
    } else {
        // Campos obrigatórios faltando: orienta o usuário a preencher tudo
        $mensagem = '<div class="alert alert-warning">Por favor, preencha todos os campos obrigatórios.</div>';
    }
}

/* CARREGAMENTO DE DADOS — Busca todos os cursos ativos para exibir na página */

// Chama a função que retorna a lista de cursos cadastrados no banco (usada nos cards e no select)
$cursos = listarCursos($conexao);

// Limites etários: idade entre 14 e 100 anos, impedindo datas futuras e anos irreais
$data_max_nasc = date('Y-m-d', strtotime('-14 years'));
$data_min_nasc = date('Y-m-d', strtotime('-100 years'));
?>

<!-- Início da estrutura HTML da página pública da escola. -->
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Define a codificação UTF-8 para suportar acentos e caracteres especiais -->
    <meta charset="UTF-8">
    <!-- Garante que a página seja responsiva em telas de celular e tablet -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título exibido na aba do navegador -->
    <title>HighTech School - Escola de Tecnologia</title>
    <!-- Arquivo CSS principal com todos os estilos visuais do sistema -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <!-- Inclui o cabeçalho e menu de navegação compartilhado por todas as páginas -->
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- HERO SECTION ESCOLA — Bloco de destaque no topo da página com chamada principal -->
    <div class="hero">
        <div class="container">
            <!-- Badge decorativo que resume a proposta de valor da escola -->
            <span class="badge">Formação Prática &amp; Mercado</span>
            <!-- Título principal da escola com foco em resultado profissional -->
            <h2>Construa sua Carreira em Tecnologia e Gestão</h2>
            <!-- Subtítulo descrevendo as tecnologias ensinadas e o formato dos cursos -->
            <p>Cursos presenciais e online com foco em aplicação real, programação PHP, PostgreSQL e metodologias ágeis.</p>
        </div>
    </div>

    <!-- CONTEÚDO PRINCIPAL — Área onde ficam os cards de cursos e o formulário de inscrição -->
    <main class="container">
        <!-- Exibe a mensagem de feedback (sucesso, erro ou aviso) gerada pelo PHP acima -->
        <?php echo $mensagem; ?>

        <!-- Apresenta a proposta educacional da escola antes do catálogo de cursos. -->
        <!-- Apresentacao da escola -->
        <section id="sobre">
            <div class="section-header">
                <h2>Sobre a Escola</h2>
                <p>Na HighTech School você aprende sobre tecnologia, banco de dados relacional e gestão de forma prática e conectada com o mercado.</p>
            </div>
        </section>

        <!-- Lista dinâmica de cursos consultados no PostgreSQL. -->
        <!-- Lista Dinâmica de Cursos vinda do Banco PostgreSQL -->
        <section id="cursos">
            <div class="section-header">
                <h2>Cursos Oferecidos (Dados em Tempo Real do Banco de Dados)</h2>
                <p>Escolha sua trilha de formação e inscreva-se diretamente.</p>
            </div>

            <!-- Grid responsivo que organiza os cards de cursos lado a lado -->
            <div class="grid">
                <?php if (empty($cursos)): ?>
                    <!-- Mensagem exibida quando não há cursos cadastrados no banco -->
                    <p>Nenhum curso cadastrado no momento.</p>
                <?php else: ?>
                    <!-- Itera sobre cada curso retornado pelo banco e renderiza um card -->
                    <?php foreach ($cursos as $curso): ?>
                        <!-- Cada artigo representa um card visual de um curso -->
                        <article class="card">
                            <div>
                                <!-- htmlspecialchars() converte caracteres especiais para evitar XSS -->
                                <span class="badge"><?php echo htmlspecialchars($curso['categoria']); ?></span>
                                <!-- Nome do curso em destaque como título do card -->
                                <h3><?php echo htmlspecialchars($curso['nome']); ?></h3>
                                <!-- Descrição resumida do conteúdo do curso -->
                                <p><?php echo htmlspecialchars($curso['descricao']); ?></p>
                                <!-- Carga horária total do curso em horas -->
                                <p><strong>Carga Horária:</strong> <?php echo htmlspecialchars($curso['carga_horaria']); ?> horas</p>
                            </div>
                            <!-- Botão que rola a página até o formulário de inscrição (#inscricao) -->
                            <a href="#inscricao" class="btn btn-primary">Inscrever-se neste Curso</a>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Formulário de inscrição: os dados são enviados ao processamento PHP no início do arquivo. -->
        <!-- Formulário de Inscrição Direta -->
        <!-- Esta seção tem id="inscricao" para que os botões dos cards possam ancorar até aqui -->
        <section id="inscricao" class="admin-card">
            <h2>Faça sua Inscrição Online </h2>
            <p style="margin-bottom: 1.5rem;">Preencha seus dados abaixo para se cadastrar como aluno e se matricular em um dos nossos cursos.</p>

            <!-- O formulário envia os dados via POST para aplicacao.php (esta mesma página) -->
            <form action="aplicacao.php" method="post">
                <!-- Campo oculto que sinaliza ao PHP que este envio é uma inscrição -->
                <input type="hidden" name="inscrever" value="1">
                <!-- Layout em grade com duas colunas para organizar os campos do formulário -->
                <div class="form-grid">
                    <!-- Coleta o nome da pessoa que será associada ao cadastro acadêmico. -->
                    <div class="form-group">
                        <label for="nome">Nome Completo: *</label>
                        <!-- Se o usuário estiver logado, pré-preenche o nome automaticamente para facilitar -->
                        <input type="text" name="nome" id="nome" required value="<?php echo htmlspecialchars($usuario_logado['nome'] ?? ''); ?>" placeholder="Digite seu nome">
                    </div>

                    <!-- CPF opcional para complementar a identificação do aluno. -->
                    <div class="form-group">
                        <label for="cpf">CPF:</label>
                        <!-- CPF é opcional — não possui 'required' pois alguns usuários podem não tê-lo -->
                        <input type="text" name="cpf" id="cpf" placeholder="000.000.000-00">
                    </div>

                    <!-- E-mail usado para localizar ou criar o registro de aluno. -->
                    <div class="form-group">
                        <label for="email">E-mail: *</label>
                        <!-- Se logado, o e-mail também é pré-preenchido com o dado da sessão -->
                        <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($usuario_logado['email'] ?? ''); ?>" placeholder="seu@email.com">
                    </div>

                    <!-- Data de nascimento opcional enviada junto com os dados da inscrição (14 a 100 anos). -->
                    <div class="form-group">
                        <label for="nasc">Data de Nascimento:</label>
                        <!-- Campo com restrição de idade mínima de 14 anos e máxima de 100 anos -->
                        <input type="date" name="nasc" id="nasc" min="<?php echo $data_min_nasc; ?>" max="<?php echo $data_max_nasc; ?>" value="<?php echo htmlspecialchars($_POST['nasc'] ?? ''); ?>">
                        <small style="color: var(--text-muted); font-size: 0.8rem;">Idade mínima: 14 anos.</small>
                    </div>

                    <!-- O seletor de curso ocupa as duas colunas e envia o ID escolhido ao servidor. -->
                    <!-- Este campo ocupa as duas colunas da grade (span 2) por ser mais importante -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="id_curso">Selecione o Curso desejado: *</label>
                        <!-- Select populado dinamicamente com os cursos vindos do banco de dados -->
                        <select name="id_curso" id="id_curso" required>
                            <!-- Opção padrão sem valor para forçar o usuário a escolher um curso -->
                            <option value="">-- Escolha um Curso --</option>
                            <!-- Reutiliza o array $cursos já carregado — evita nova consulta ao banco -->
                            <?php foreach ($cursos as $curso): ?>
                                <!-- O value é o ID numérico do curso, que será enviado ao PHP para matrícula -->
                                <option value="<?php echo $curso['id']; ?>">
                                    <!-- Exibe nome e categoria para facilitar a escolha do aluno -->
                                    <?php echo htmlspecialchars($curso['nome']); ?> (<?php echo htmlspecialchars($curso['categoria']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Botão de envio do formulário — aciona o processamento POST no topo do arquivo -->
                <!-- Envia os dados para validação e tentativa de matrícula no curso selecionado. -->
                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Confirmar Minha Inscrição</button>
            </form>
        </section>
    </main>

    <!-- Inclui o rodapé compartilhado por todas as páginas do sistema -->
    <!-- Inclui o rodapé institucional compartilhado pelas páginas. -->
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
