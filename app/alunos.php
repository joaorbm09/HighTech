<?php 
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();

require_once __DIR__ . '/../includes/functions.php';

$mensagem = '';
$aluno_edicao = null;

// Exclusão
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (excluirAluno($conexao, $_GET['id'])) {
        $mensagem = '<div class="alert alert-success">Aluno excluído com sucesso!</div>';
    } else {
        $mensagem = '<div class="alert alert-danger">Erro ao excluir aluno.</div>';
    }
}

// Carregar para Edição
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $aluno_edicao = buscarAlunoPorId($conexao, $_GET['id']);
}

// Salvar / Atualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $email = $_POST['email'] ?? '';
    $turma = $_POST['turma'] ?? '';
    $nasc = $_POST['nasc'] ?? '';
    $ativo = $_POST['ativo'] ?? 'true';

    if (!empty($nome) && !empty($email)) {
        if ($id) {
            // Update
            if (atualizarAluno($conexao, $id, $nome, $cpf, $email, $turma, $nasc, $ativo)) {
                $mensagem = '<div class="alert alert-success">Dados do aluno atualizados com sucesso!</div>';
                $aluno_edicao = null;
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao atualizar aluno.</div>';
            }
        } else {
            // Create
            if (cadastrarAluno($conexao, $nome, $cpf, $email, $turma, $nasc, $ativo)) {
                $mensagem = '<div class="alert alert-success">Novo aluno cadastrado com sucesso!</div>';
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao cadastrar aluno.</div>';
            }
        }
    } else {
        $mensagem = '<div class="alert alert-warning">Campos obrigatórios: Nome e E-mail.</div>';
    }
}

$alunos = listarAlunos($conexao);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Alunos - HighTech System</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <h2>Gestão de Alunos (Área Restrita - Admin)</h2>
        <p>Gerencie o cadastro de estudantes do sistema.</p>

        <?php echo $mensagem; ?>

        <!-- Form de Cadastro / Edição -->
        <div class="admin-card">
            <h3><?php echo $aluno_edicao ? 'Editar Aluno (ID: ' . htmlspecialchars($aluno_edicao['id']) . ')' : 'Cadastrar Novo Aluno'; ?></h3>
            <br>
            <form action="alunos.php" method="post">
                <?php if ($aluno_edicao): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($aluno_edicao['id']); ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome">Nome Completo: *</label>
                        <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($aluno_edicao['nome'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="cpf">CPF:</label>
                        <input type="text" name="cpf" id="cpf" value="<?php echo htmlspecialchars($aluno_edicao['cpf'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail: *</label>
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($aluno_edicao['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="turma">Turma:</label>
                        <input type="text" name="turma" id="turma" value="<?php echo htmlspecialchars($aluno_edicao['turma'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="nasc">Nascimento:</label>
                        <input type="date" name="nasc" id="nasc" value="<?php echo htmlspecialchars($aluno_edicao['nascimento'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="ativo">Status Ativo:</label>
                        <select name="ativo" id="ativo">
                            <?php $isAtivo = isset($aluno_edicao['ativo']) ? ($aluno_edicao['ativo'] === true || $aluno_edicao['ativo'] === 't' || $aluno_edicao['ativo'] == 1) : true; ?>
                            <option value="true" <?php echo $isAtivo ? 'selected' : ''; ?>>Sim (Ativo)</option>
                            <option value="false" <?php echo !$isAtivo ? 'selected' : ''; ?>>Não (Inativo)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><?php echo $aluno_edicao ? 'Salvar Alterações' : 'Cadastrar Aluno'; ?></button>
                <?php if ($aluno_edicao): ?>
                    <a href="alunos.php" class="btn btn-outline">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabela de Listagem -->
        <div class="admin-card">
            <h3>Lista de Alunos Cadastrados</h3>
            <?php if (empty($alunos)): ?>
                <p>Nenhum aluno cadastrado no banco de dados.</p>
            <?php else: ?>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>E-mail</th>
                            <th>Turma</th>
                            <th>Nascimento</th>
                            <th>Ativo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alunos as $aluno): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($aluno['id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($aluno['cpf'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($aluno['email']); ?></td>
                                <td><?php echo htmlspecialchars($aluno['turma'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($aluno['nascimento'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($aluno['ativo'] === true || $aluno['ativo'] === 't' || $aluno['ativo'] == 1): ?>
                                        <span class="badge" style="background-color: #D1FAE5; color: #065F46;">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: #FEE2E2; color: #991B1B;">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="alunos.php?action=edit&id=<?php echo $aluno['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Editar</a>
                                    <a href="alunos.php?action=delete&id=<?php echo $aluno['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este aluno?');" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
