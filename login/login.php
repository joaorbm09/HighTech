<?php 
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Se já estiver logado, redireciona para a aplicação de acordo com o perfil
if (usuarioLogado()) {
    $user = obterUsuarioLogado();
    if ($user && $user['perfil'] === 'admin') {
        header("Location: ../app/alunos.php");
    } else {
        header("Location: ../aplicacao.php");
    }
    exit;
}

$mensagem = '';
$email = '';

if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'cadastrado') {
    $mensagem = '<div class="alert alert-success">✅ Cadastro realizado com sucesso! Faça seu login abaixo.</div>';
}

if (!$conexao) {
    $mensagem = '<div class="alert alert-danger">⚠️ Erro de conexão com o banco de dados PostgreSQL. Verifique as configurações em database/connect.php.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $mensagem = '<div class="alert alert-warning">Por favor, preencha o E-mail e a Senha.</div>';
    } else if (!$conexao) {
        $mensagem = '<div class="alert alert-danger">Não foi possível processar o login: sem conexão com o banco de dados.</div>';
    } else {
        $usuario = autenticarUsuario($conexao, $email, $senha);
        if ($usuario) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_nome'] = $usuario['nome'];
            $_SESSION['user_email'] = $usuario['email'];
            $_SESSION['user_perfil'] = $usuario['perfil'];

            if ($usuario['perfil'] === 'admin') {
                header("Location: ../app/alunos.php");
            } else {
                header("Location: ../aplicacao.php");
            }
            exit;
        } else {
            $mensagem = '<div class="alert alert-danger">E-mail ou senha incorretos! Verifique suas credenciais.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HighTech Sistema</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <div class="admin-card" style="max-width: 450px; margin: 2rem auto;">
            <h2 style="text-align: center; margin-bottom: 0.5rem;">Acesse sua Conta 🔒</h2>
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 1.5rem;">Entre com seu e-mail e senha cadastrados</p>

            <?php echo $mensagem; ?>

            <form action="login.php" method="post">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="email">E-mail: *</label>
                    <input type="email" name="email" id="email" required placeholder="seuemail@hightech.com" value="<?php echo htmlspecialchars($email); ?>">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="senha">Senha: *</label>
                    <input type="password" name="senha" id="senha" required placeholder="Digite sua senha">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Entrar no Sistema</button>
            </form>

            <hr style="display: block; margin: 1.5rem 0; border: none; border-top: 1px solid var(--border-color);">

            <p style="text-align: center; font-size: 0.95rem;">
                Ainda não possui uma conta? <a href="cadastrar.php" style="color: var(--primary); font-weight: 600;">Cadastre-se aqui</a>
            </p>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
