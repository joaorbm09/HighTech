<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se existe um usuário logado na sessão.
 */
function usuarioLogado() {
    return isset($_SESSION['user_id']);
}

/**
 * Retorna os dados do usuário atualmente autenticado.
 */
function obterUsuarioLogado() {
    if (!usuarioLogado()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'nome' => $_SESSION['user_nome'],
        'email' => $_SESSION['user_email'],
        'perfil' => $_SESSION['user_perfil'] ?? 'aluno'
    ];
}

/**
 * Garante que o usuário está logado. Se não estiver, redireciona para a tela de Login.
 */
function exigirLogin() {
    if (!usuarioLogado()) {
        $is_app_dir = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'app');
        $login_path = $is_app_dir ? '../login/login.php' : 'login/login.php';
        header("Location: " . $login_path);
        exit;
    }
}

/**
 * Garante que o usuário logado possui perfil de Administrador.
 */
function exigirAdmin() {
    exigirLogin();
    $usuario = obterUsuarioLogado();
    if ($usuario['perfil'] !== 'admin') {
        $is_app_dir = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'app');
        $portal_path = $is_app_dir ? '../portal_empresa.php' : 'portal_empresa.php';
        header("Location: " . $portal_path . "?erro=acesso_negado");
        exit;
    }
}
?>
