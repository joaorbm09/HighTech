<?php 
/* INCLUSÃO DE DEPENDÊNCIAS */
// Importa o arquivo de autenticação que contém a função obterUsuarioLogado()
// e toda a lógica de controle de sessão do sistema
require_once __DIR__ . '/auth.php';

/* DETECÇÃO DO DIRETÓRIO ATUAL */
// Verifica se o script está sendo executado dentro da pasta 'app'
// Isso é necessário para montar corretamente os caminhos de links e assets
$is_app_dir = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'app');

// Verifica se o script está sendo executado dentro da pasta 'login'
// O operador ?? '' garante que, se SCRIPT_NAME não existir, use string vazia (evita erro)
$is_login_dir = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'login');

/* DEFINIÇÃO DA URL BASE */
// Se a página estiver dentro de 'app/' ou 'login/', precisa subir um nível com '../'
// para que os links e imagens apontem corretamente para a raiz do projeto
if ($is_app_dir || $is_login_dir) {
    $base_url = '../'; // Sobe um nível na hierarquia de pastas
} else {
    $base_url = ''; // Já está na raiz, não precisa prefixo
}

// Pega apenas o nome do arquivo atual (ex: 'portal_empresa.php')
// Usado para destacar o link da página ativa no menu de navegação
$current_page = basename($_SERVER['SCRIPT_NAME'] ?? '');

// Chama a função de auth.php que retorna os dados do usuário logado (ou false/null se não logado)
$usuario_logado = obterUsuarioLogado();
?>

<!-- ============================================================ -->
<!-- FOLHA DE ESTILOS GLOBAL                                      -->
<!-- O href usa $base_url para garantir o caminho correto         -->
<!-- independente de qual subpasta a página está sendo executada  -->
<!-- ============================================================ -->
<link rel="stylesheet" href="<?php echo $base_url; ?>assets/style.css">

<!-- CABEÇALHO VISUAL DO SITE -->
<!-- Exibe o logo, nome e subtítulo da empresa no topo de todas as páginas -->
<header>
    <div class="container header-content">
        <!-- Logo da empresa — src usa $base_url para funcionar em qualquer subpasta -->
        <img src="<?php echo $base_url; ?>assets/logo_highTech.png" alt="Logo HighTech" class="logo">

        <!-- Nome principal do sistema exibido no cabeçalho -->
        <h1>HighTech Inovações & Gestão Escolar</h1>

        <!-- Subtítulo informativo sobre as tecnologias utilizadas no projeto -->
        <p>Soluções Corporativas Integradas com Banco de Dados PostgreSQL</p>
    </div>
</header>

<!-- BARRA DE NAVEGAÇÃO PRINCIPAL -->
<!-- Presente em todas as páginas — os links mudam conforme o perfil do usuário -->
<nav>
    <div class="nav-container">

        <!-- Link para o Portal da Empresa -->
        <!-- A classe 'active' é adicionada dinamicamente se o usuário estiver nessa página -->
        <!-- index.php também recebe 'active' pois normalmente redireciona para portal_empresa.php -->
        <a href="<?php echo $base_url; ?>portal_empresa.php" class="<?php echo ($current_page === 'portal_empresa.php' || $current_page === 'index.php') ? 'active' : ''; ?>"> Portal Empresa</a>

        <!-- Link para a área HighTech School (aplicação principal de gestão escolar) -->
        <a href="<?php echo $base_url; ?>aplicacao.php" class="<?php echo ($current_page === 'aplicacao.php') ? 'active' : ''; ?>"> HighTech School</a>

        <?php if ($usuario_logado && $usuario_logado['perfil'] === 'admin'): ?>
            <!-- MENU EXCLUSIVO PARA ADMINISTRADORES -->
            <!-- Esses links só aparecem se o usuário estiver logado E tiver perfil 'admin' -->
            <!-- Isso impede que usuários comuns acessem as páginas de gestão pelo menu -->

            <!-- Link para gerenciamento de alunos cadastrados no sistema -->
            <a href="<?php echo $base_url; ?>app/alunos.php" class="<?php echo ($current_page === 'alunos.php') ? 'active' : ''; ?>"> Gestão Alunos</a>

            <!-- Link para gerenciamento dos cursos oferecidos -->
            <a href="<?php echo $base_url; ?>app/cursos.php" class="<?php echo ($current_page === 'cursos.php') ? 'active' : ''; ?>"> Gestão Cursos</a>

            <!-- Link para gerenciamento das matrículas dos alunos nos cursos -->
            <a href="<?php echo $base_url; ?>app/matriculas.php" class="<?php echo ($current_page === 'matriculas.php') ? 'active' : ''; ?>"> Matrículas</a>
        <?php endif; ?>

        <?php if ($usuario_logado): ?>
            <!-- ÁREA DO USUÁRIO LOGADO -->
            <!-- Exibe o nome e o perfil do usuário autenticado no canto direito da nav -->
            <span style="color: var(--primary); font-weight: 700; margin-left: auto; font-size: 0.9rem;">
                 Olá, <?php echo htmlspecialchars($usuario_logado['nome']); ?> 
                <!-- htmlspecialchars() protege contra XSS, convertendo caracteres especiais em entidades HTML -->

                <!-- ucfirst() coloca a primeira letra do perfil em maiúscula (ex: 'admin' → 'Admin') -->
                <small style="background: #E2E8F0; padding: 2px 6px; border-radius: 4px; font-weight: normal;">(<?php echo ucfirst($usuario_logado['perfil']); ?>)</small>
            </span>

            <!-- Botão de logout — redireciona para o script que encerra a sessão do usuário -->
            <a href="<?php echo $base_url; ?>login/logout.php" style="color: var(--danger);">Sair </a>

        <?php else: ?>
            <!-- ÁREA PARA USUÁRIOS NÃO LOGADOS -->
            <!-- Se não há sessão ativa, exibe os botões de Entrar e Cadastrar-se -->

            <!-- Botão principal para ir à página de login -->
            <a href="<?php echo $base_url; ?>login/login.php" class="btn-cta" style="margin-left: auto;">Entrar </a>

            <!-- Botão secundário para criar uma nova conta no sistema -->
            <a href="<?php echo $base_url; ?>login/cadastrar.php" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">Cadastrar-se</a>
        <?php endif; ?>

    </div>
</nav>
