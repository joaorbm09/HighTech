<?php 
// Carrega as verificações de sessão e as funções usadas no cadastro.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Usuários autenticados seguem para a aplicação sem criar outra conta.
if (usuarioLogado()) {
    header("Location: ../aplicacao.php");
    exit;
}

$mensagem = '';
$nome = '';
$email = '';

// Detecta indisponibilidade do banco antes que o formulário seja enviado.
if (!$conexao) {
    $mensagem = '<div class="alert alert-danger">⚠️ Sem conexão com o banco de dados PostgreSQL.</div>';
}

// Processa os dados somente quando o formulário de cadastro é enviado.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lê os campos de texto e remove espaços externos para validar dados consistentes.
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    // Senhas não recebem trim para não alterar caracteres digitados pelo usuário.
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Verifica os campos obrigatórios antes de fazer qualquer consulta ou gravação.
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
        // Cria a conta com o perfil padrão de aluno, depois associa o cadastro acadêmico.
        if (cadastrarUsuario($conexao, $nome, $email, $senha, 'aluno')) {
            // Insere também na tabela de alunos se não existir
            cadastrarAluno($conexao, $nome, null, $email, 'HT-2026', date('Y-m-d'), true);
            
            header("Location: login.php?sucesso=cadastrado");
            exit;
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao criar conta. Tente novamente.</div>';
        }
    }
}
?>

<!-- Estrutura visual do formulário de criação de conta. -->
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Define codificação, comportamento responsivo, título e folha de estilos da página. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - HighTech Sistema</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <!-- Reutiliza a navegação comum do sistema. -->
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Centraliza o formulário e reapresenta nome/e-mail em caso de validação malsucedida. -->
    <main class="container">
        <div class="admin-card" style="max-width: 500px; margin: 2rem auto;">
            <h2 style="text-align: center; margin-bottom: 0.5rem;">Crie sua Conta 📝</h2>
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 1.5rem;">Cadastre-se para acessar a HighTech School</p>

            <!-- Exibe a mensagem de sucesso ou o motivo pelo qual o cadastro não foi concluído. -->
            <?php echo $mensagem; ?>

            <!-- Envia os dados sensíveis por POST para processamento neste mesmo arquivo. -->
            <form action="cadastrar.php" method="post">
                <!-- Campo obrigatório para identificar o novo aluno. -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="nome">Nome Completo: *</label>
                    <input type="text" name="nome" id="nome" required placeholder="Seu nome completo" value="<?php echo htmlspecialchars($nome); ?>">
                </div>

                <!-- E-mail usado como identificador de acesso e contato. -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="email">E-mail: *</label>
                    <input type="email" name="email" id="email" required placeholder="seu@email.com" value="<?php echo htmlspecialchars($email); ?>">
                </div>

                <!-- Senha de acesso; o servidor valida tamanho e confirmação antes de gravá-la. -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="senha">Senha: *</label>
                    <input type="password" name="senha" id="senha" required placeholder="Mínimo 6 caracteres">
                </div>

                <!-- Confirmação para detectar erros de digitação antes de criar a conta. -->
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="confirmar_senha">Confirmar Senha: *</label>
                    <input type="password" name="confirmar_senha" id="confirmar_senha" required placeholder="Digite a senha novamente">
                </div>

                <!-- Envia o formulário para executar a validação e persistência no banco. -->
                <button type="submit" class="btn btn-primary" style="width: 100%;">Finalizar Cadastro</button>
            </form>

            <!-- Separa visualmente o cadastro do link para quem já tem uma conta. -->
            <hr style="display: block; margin: 1.5rem 0; border: none; border-top: 1px solid var(--border-color);">

            <p style="text-align: center; font-size: 0.95rem;">
                Já tem uma conta? <a href="login.php" style="color: var(--primary); font-weight: 600;">Faça Login aqui</a>
            </p>
        </div>
    </main>

    <!-- Reutiliza o rodapé comum das páginas do sistema. -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
