<?php 
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();

require_once __DIR__ . '/../includes/functions.php';

$mensagem = '';
$curso_edicao = null;

// Exclusão
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (excluirCurso($conexao, $_GET['id'])) {
        $mensagem = '<div class="alert alert-success">Curso excluído com sucesso!</div>';
    } else {
        $mensagem = '<div class="alert alert-danger">Erro ao excluir curso.</div>';
    }
}

// Carregar para Edição
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $curso_edicao = buscarCursoPorId($conexao, $_GET['id']);
}

// Salvar / Atualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $carga_horaria = $_POST['carga_horaria'] ?? 0;
    $ativo = $_POST['ativo'] ?? 'true';

    if (!empty($nome) && !empty($categoria) && $carga_horaria > 0) {
        if ($id) {
            // Update
            if (atualizarCurso($conexao, $id, $nome, $categoria, $descricao, $carga_horaria, $ativo)) {
                $mensagem = '<div class="alert alert-success">Curso atualizado com sucesso!</div>';
                $curso_edicao = null;
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao atualizar curso.</div>';
            }
        } else {
            // Create
            if (cadastrarCurso($conexao, $nome, $categoria, $descricao, $carga_horaria, $ativo)) {
                $mensagem = '<div class="alert alert-success">Novo curso cadastrado com sucesso!</div>';
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao cadastrar curso.</div>';
            }
        }
    } else {
        $mensagem = '<div class="alert alert-warning">Preencha Nome, Categoria e Carga Horária válida.</div>';
    }
}

$cursos = listarCursos($conexao);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Cursos - HighTech System</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <h2>Gestão de Cursos (Área Restrita - Admin)</h2>
        <p>Gerencie o catálogo de cursos oferecidos pela HighTech School.</p>

        <?php echo $mensagem; ?>

        <!-- Form de Cadastro / Edição -->
        <div class="admin-card">
            <h3><?php echo $curso_edicao ? 'Editar Curso (ID: ' . htmlspecialchars($curso_edicao['id']) . ')' : 'Cadastrar Novo Curso'; ?></h3>
            <br>
            <form action="cursos.php" method="post">
                <?php if ($curso_edicao): ?>
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($curso_edicao['id']); ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome">Nome do Curso: *</label>
                        <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($curso_edicao['nome'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="categoria">Categoria: *</label>
                        <select name="categoria" id="categoria" required>
                            <?php $cat = $curso_edicao['categoria'] ?? ''; ?>
                            <option value="Desenvolvimento" <?php echo ($cat === 'Desenvolvimento') ? 'selected' : ''; ?>>Desenvolvimento</option>
                            <option value="Gestão & Ágil" <?php echo ($cat === 'Gestão & Ágil') ? 'selected' : ''; ?>>Gestão & Ágil</option>
                            <option value="Inovação & PMEs" <?php echo ($cat === 'Inovação & PMEs') ? 'selected' : ''; ?>>Inovação & PMEs</option>
                            <option value="Infraestrutura & BD" <?php echo ($cat === 'Infraestrutura & BD') ? 'selected' : ''; ?>>Infraestrutura & BD</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="carga_horaria">Carga Horária (Horas): *</label>
                        <input type="number" name="carga_horaria" id="carga_horaria" value="<?php echo htmlspecialchars($curso_edicao['carga_horaria'] ?? 40); ?>" required min="1">
                    </div>

                    <div class="form-group">
                        <label for="ativo">Status:</label>
                        <select name="ativo" id="ativo">
                            <?php $isAtivo = isset($curso_edicao['ativo']) ? ($curso_edicao['ativo'] === true || $curso_edicao['ativo'] === 't' || $curso_edicao['ativo'] == 1) : true; ?>
                            <option value="true" <?php echo $isAtivo ? 'selected' : ''; ?>>Ativo</option>
                            <option value="false" <?php echo !$isAtivo ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="descricao">Descrição Detalhada do Curso:</label>
                        <textarea name="descricao" id="descricao" rows="3"><?php echo htmlspecialchars($curso_edicao['descricao'] ?? ''); ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><?php echo $curso_edicao ? 'Salvar Alterações' : 'Cadastrar Curso'; ?></button>
                <?php if ($curso_edicao): ?>
                    <a href="cursos.php" class="btn btn-outline">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabela de Listagem -->
        <div class="admin-card">
            <h3>Catálogo de Cursos no Banco de Dados</h3>
            <?php if (empty($cursos)): ?>
                <p>Nenhum curso cadastrado.</p>
            <?php else: ?>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Curso</th>
                            <th>Categoria</th>
                            <th>Carga Horária</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cursos as $curso): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($curso['id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($curso['nome']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($curso['descricao']); ?></small>
                                </td>
                                <td><span class="badge"><?php echo htmlspecialchars($curso['categoria']); ?></span></td>
                                <td><?php echo htmlspecialchars($curso['carga_horaria']); ?>h</td>
                                <td>
                                    <?php if ($curso['ativo'] === true || $curso['ativo'] === 't' || $curso['ativo'] == 1): ?>
                                        <span class="badge" style="background-color: #D1FAE5; color: #065F46;">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: #FEE2E2; color: #991B1B;">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="cursos.php?action=edit&id=<?php echo $curso['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Editar</a>
                                    <a href="cursos.php?action=delete&id=<?php echo $curso['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este curso?');" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Excluir</a>
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
