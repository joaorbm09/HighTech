<?php
// Inicia a sessão uma única vez para guardar a identificação do usuário entre páginas.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o identificador do usuário foi salvo na sessão.
function usuarioLogado() {
    return isset($_SESSION['user_id']);
}

// Devolve os dados básicos do usuário atual ou null quando ninguém entrou.
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

// Envia visitantes sem login à tela de entrada e interrompe a página atual.
function exigirLogin() {
    if (!usuarioLogado()) {
        header('Location: ../login/login.php');
        exit;
    }
}

// Exige login e, além disso, confere se o perfil atual é de administrador.
function exigirAdmin() {
    exigirLogin();

    if (($_SESSION['user_perfil'] ?? '') !== 'admin') {
        header('Location: ../aplicacao.php?erro=acesso_negado');
        exit;
    }
}
