<?php
require_once __DIR__ . '/auth.php';

// Ajusta os links conforme a página esteja na raiz, em app/ ou em login/.
$baseUrl = in_array(basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')), ['app', 'login'], true)
    ? '../'
    : '';
$usuario = obterUsuarioLogado();
?>
<!-- Cabeçalho e navegação compartilhados por todas as telas. -->
<link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/style.css">
<header class="site-header">
    <div class="container">
        <a class="brand" href="<?php echo $baseUrl; ?>aplicacao.php">HighTech School</a>
    </div>
</header>
<nav class="site-nav">
    <div class="container nav-links">
        <!-- As opções administrativas só aparecem para usuários com perfil admin. -->
        <a href="<?php echo $baseUrl; ?>aplicacao.php">Cursos</a>
        <?php if ($usuario && $usuario['perfil'] === 'admin'): ?>
            <a href="<?php echo $baseUrl; ?>app/usuarios.php">Usuários</a>
            <a href="<?php echo $baseUrl; ?>app/alunos.php">Alunos</a>
            <a href="<?php echo $baseUrl; ?>app/cursos.php">Cursos</a>
            <a href="<?php echo $baseUrl; ?>app/matriculas.php">Matrículas</a>
            <a href="<?php echo $baseUrl; ?>app/gerenciar_aulas.php">Aulas</a>
            <a href="<?php echo $baseUrl; ?>app/gerenciar_provas.php">Provas</a>
        <?php elseif ($usuario): ?>
            <a href="<?php echo $baseUrl; ?>app/meu_painel.php">Meu painel</a>
        <?php endif; ?>
        <?php if ($usuario): ?>
            <span class="nav-user"><?php echo htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
            <a href="<?php echo $baseUrl; ?>login/logout.php">Sair</a>
        <?php else: ?>
            <a href="<?php echo $baseUrl; ?>login/login.php">Entrar</a>
            <a href="<?php echo $baseUrl; ?>login/cadastrar.php">Cadastrar</a>
        <?php endif; ?>
    </div>
</nav>
