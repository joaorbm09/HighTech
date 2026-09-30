<?php 
require_once __DIR__ . '/auth.php';

$is_app_dir = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'app');
$is_login_dir = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'login');

if ($is_app_dir || $is_login_dir) {
    $base_url = '../';
} else {
    $base_url = '';
}

$current_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
$usuario_logado = obterUsuarioLogado();
?>
<header>
    <div class="container header-content">
        <img src="<?php echo $base_url; ?>assets/logo_highTech.png" alt="Logo HighTech" class="logo">
        <h1>HighTech Inovações & Gestão Escolar</h1>
        <p>Soluções Corporativas Integradas com Banco de Dados PostgreSQL</p>
    </div>
</header>

<nav>
    <div class="nav-container">
        <a href="<?php echo $base_url; ?>portal_empresa.php" class="<?php echo ($current_page === 'portal_empresa.php' || $current_page === 'index.php') ? 'active' : ''; ?>"> Portal Empresa</a>
        <a href="<?php echo $base_url; ?>aplicacao.php" class="<?php echo ($current_page === 'aplicacao.php') ? 'active' : ''; ?>"> HighTech School</a>

        <?php if ($usuario_logado && $usuario_logado['perfil'] === 'admin'): ?>
            <a href="<?php echo $base_url; ?>app/alunos.php" class="<?php echo ($current_page === 'alunos.php') ? 'active' : ''; ?>"> Gestão Alunos</a>
            <a href="<?php echo $base_url; ?>app/cursos.php" class="<?php echo ($current_page === 'cursos.php') ? 'active' : ''; ?>"> Gestão Cursos</a>
            <a href="<?php echo $base_url; ?>app/matriculas.php" class="<?php echo ($current_page === 'matriculas.php') ? 'active' : ''; ?>"> Matrículas</a>
        <?php endif; ?>

        <?php if ($usuario_logado): ?>
            <span style="color: var(--primary); font-weight: 700; margin-left: auto; font-size: 0.9rem;">
                 Olá, <?php echo htmlspecialchars($usuario_logado['nome']); ?> 
                <small style="background: #E2E8F0; padding: 2px 6px; border-radius: 4px; font-weight: normal;">(<?php echo ucfirst($usuario_logado['perfil']); ?>)</small>
            </span>
            <a href="<?php echo $base_url; ?>login/logout.php" style="color: var(--danger);">Sair </a>
        <?php else: ?>
            <a href="<?php echo $base_url; ?>login/login.php" class="btn-cta" style="margin-left: auto;">Entrar </a>
            <a href="<?php echo $base_url; ?>login/cadastrar.php" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">Cadastrar-se</a>
        <?php endif; ?>
    </div>
</nav>
