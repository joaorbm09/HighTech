<?php 
// Cadastro público: usuários já autenticados seguem direto para o painel.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
if (usuarioLogado()) {
    header("Location: ../app/meu_painel.php");
    exit;
}

$mensagem = '';
$nome = '';
$email = '';
// O token CSRF protege o formulário de cadastro contra envios externos.
if (empty($_SESSION['csrf_cadastro'])) {
    $_SESSION['csrf_cadastro'] = bin2hex(random_bytes(32));
}
if (!$conexao) {
    $mensagem = '<div class="alert alert-danger">Sem conexão com o banco de dados PostgreSQL.</div>';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lê os campos enviados e valida sessão, preenchimento e força básica da senha.
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!is_string($csrf_token) || !hash_equals($_SESSION['csrf_cadastro'], $csrf_token)) {
        $mensagem = '<div class="alert alert-danger">Falha na validação de segurança. Atualize a página e tente novamente.</div>';
    }
    if (empty($nome) || empty($email) || empty($senha)) {
        $mensagem = '<div class="alert alert-warning">Por favor, preencha todos os campos obrigatórios.</div>';
    } else if (!$conexao) {
        $mensagem = '<div class="alert alert-danger">Não foi possível processar o cadastro: banco de dados offline.</div>';
    } else if ($senha !== $confirmar_senha) {
        $mensagem = '<div class="alert alert-warning">As senhas digitadas não coincidem!</div>';
    } else if (strlen($senha) < 6) {
        $mensagem = '<div class="alert alert-warning">A senha deve conter no mínimo 6 caracteres.</div>';
    } else if (buscarUsuarioPorEmail($conexao, $email)) {
        $mensagem = '<div class="alert alert-danger">Este e-mail já está cadastrado no sistema.</div>';
    } else {
        // Cria a conta de acesso e o cadastro acadêmico vinculado ao mesmo e-mail.
        if (cadastrarUsuario($conexao, $nome, $email, $senha, 'aluno')) {
            cadastrarAluno($conexao, $nome, null, $email, 'HT-2026', null, true);
            
            header("Location: login.php?sucesso=cadastrado");
            exit;
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao criar conta. Tente novamente.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | HighTech School</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Formulário HTML para criar uma conta de aluno. -->
<main class="container">
        <div class="admin-card" style="max-width: 500px; margin: 2rem auto;">
            <h2 style="text-align: center; margin-bottom: 0.5rem;">Criar conta</h2>
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 1.5rem;">Cadastre-se para acessar a HighTech School</p>

<?php echo $mensagem; ?>

<form action="cadastrar.php" method="post">
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="nome">Nome Completo: *</label>
                    <input type="text" name="nome" id="nome" required placeholder="Seu nome completo" value="<?php echo htmlspecialchars($nome); ?>">
                </div>

<div class="form-group" style="margin-bottom: 1rem;">
                    <label for="email">E-mail: *</label>
                    <input type="email" name="email" id="email" required placeholder="seu@email.com" value="<?php echo htmlspecialchars($email); ?>">
                </div>

<div class="form-group" style="margin-bottom: 1rem;">
                    <label for="senha">Senha: *</label>
                    <input type="password" name="senha" id="senha" required placeholder="Mínimo 6 caracteres">
                </div>

<div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="confirmar_senha">Confirmar Senha: *</label>
                    <input type="password" name="confirmar_senha" id="confirmar_senha" required placeholder="Digite a senha novamente">
                </div>

<button type="submit" class="btn btn-primary" style="width: 100%;">Finalizar Cadastro</button>
            </form>

<hr style="display: block; margin: 1.5rem 0; border: none; border-top: 1px solid var(--border-color);">

            <p style="text-align: center; font-size: 0.95rem;">
                Já tem uma conta? <a href="login.php" style="color: var(--primary); font-weight: 600;">Faça Login aqui</a>
            </p>
        </div>
    </main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
