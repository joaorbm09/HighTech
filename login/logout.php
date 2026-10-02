<?php
// Garante que exista uma sessão para que ela possa ser encerrada corretamente.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Remove da sessão todos os dados de autenticação e de contexto do usuário.
$_SESSION = array();

// Verifica se a sessão é mantida por cookie antes de apagar o cookie do navegador.
if (ini_get("session.use_cookies")) {
    // Recupera os atributos originais para invalidar o mesmo cookie de sessão.
    $params = session_get_cookie_params();
    // Expira o cookie no passado, preservando caminho, domínio e flags de segurança.
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói os dados da sessão armazenados no servidor.
session_destroy();

// Retorna o usuário ao portal após finalizar a sessão.
header("Location: ../portal_empresa.php");
// Interrompe a execução após o redirecionamento.
exit;
?>
