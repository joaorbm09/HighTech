<?php
// Inicia a sessão apenas quando ainda não existir uma sessão ativa.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Token comum aos formulários que ainda não possuem um token próprio.
function csrfToken() {
    if (empty($_SESSION['csrf_global'])) {
        $_SESSION['csrf_global'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_global'];
}

// Rejeita estruturas inesperadas antes de tratar campos escalares de formulários.
function validarCamposTexto($campos) {
    foreach ($campos as $campo) {
        if (isset($_POST[$campo]) && !is_string($_POST[$campo])) {
            http_response_code(400);
            exit('Dados inválidos. Atualize a página e tente novamente.');
        }
    }
}

function exigirCsrf($campo = 'csrf_token', $chave = 'csrf_global') {
    $token = $_POST[$campo] ?? null;
    $esperado = $_SESSION[$chave] ?? null;
    if (!is_string($token) || !is_string($esperado) || !hash_equals($esperado, $token)) {
        http_response_code(403);
        exit('Solicitação inválida ou expirada. Atualize a página e tente novamente.');
    }
}

/**
 * Verifica se existe um usuário logado na sessão.
 */
function usuarioLogado() {
    // A presença do identificador do usuário indica que a autenticação foi concluída.
    return isset($_SESSION['user_id']);
}

/**
 * Retorna os dados do usuário atualmente autenticado.
 */
function obterUsuarioLogado() {
    // Sem um usuário autenticado, não há dados de perfil para devolver.
    if (!usuarioLogado()) {
        return null;
    }
    // Monta um array com os dados mantidos na sessão para consumo pelas páginas.
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
    // Usuários sem sessão válida devem entrar pela tela de login.
    if (!usuarioLogado()) {
        // Detecta se a página atual fica em app/ para escolher o caminho relativo correto.
        $is_app_dir = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'app');
        // Define o destino do redirecionamento conforme a localização do script.
        $login_path = $is_app_dir ? '../login/login.php' : 'login/login.php';
        // Envia o navegador à página de autenticação.
        header("Location: " . $login_path);
        // Encerra a requisição protegida após o redirecionamento.
        exit;
    }
}

/**
 * Garante que o usuário logado possui perfil de Administrador.
 */
function exigirAdmin() {
    // Primeiro exige uma sessão autenticada antes de verificar o tipo de perfil.
    exigirLogin();
    // Recupera o perfil atual para decidir se a página administrativa pode ser exibida.
    $usuario = obterUsuarioLogado();
    // Usuários que não são administradores retornam ao portal com um aviso de acesso negado.
    if ($usuario['perfil'] !== 'admin') {
        // Detecta se a página administrativa está dentro de app/ para montar o link correto.
        $is_app_dir = (basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'app');
        // Define o caminho do portal a partir da pasta atual.
        $portal_path = $is_app_dir ? '../portal_empresa.php' : 'portal_empresa.php';
        // Redireciona e sinaliza o motivo para a página de destino.
        header("Location: " . $portal_path . "?erro=acesso_negado");
        // Impede que o código administrativo continue após negar o acesso.
        exit;
    }
}
?>
