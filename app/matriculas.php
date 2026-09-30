<?php 
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();

require_once __DIR__ . '/../includes/functions.php';

$mensagem = '';

// Exclusão / Cancelamento de Matrícula
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (excluirMatricula($conexao, $_GET['id'])) {
        $mensagem = '<div class="alert alert-success">Matrícula cancelada com sucesso!</div>';
    } else {
        $mensagem = '<div class="alert alert-danger">Erro ao cancelar matrícula.</div>';
    }
}

// Criar Nova Matrícula (Associar Aluno + Curso)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_aluno = $_POST['id_aluno'] ?? null;
    $id_curso = $_POST['id_curso'] ?? null;
    $status = $_POST['status'] ?? 'Ativa';

    if ($id_aluno && $id_curso) {
        if (matricularAluno($conexao, $id_aluno, $id_curso, $status)) {
            $mensagem = '<div class="alert alert-success">Aluno matriculado com sucesso no curso selecionado!</div>';
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao realizar matrícula.</div>';
        }
    } else {
        $mensagem = '<div class="alert alert-warning">Selecione o Aluno e o Curso para realizar a matrícula.</div>';
    }
}

$alunos = listarAlunos($conexao);
$cursos = listarCursos($conexao);
$matriculas = listarMatriculas($conexao);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Matrículas (N:N) - HighTech System</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <main class="container">
        <h2>Gestão de Matrículas (Área Restrita - Admin)</h2>
        <p>Vincule Alunos aos Cursos da escola através da tabela intermediária de Matrículas.</p>

        <?php echo $mensagem; ?>

        <!-- Form de Nova Matrícula -->
        <div class="admin-card">
            <h3>Realizar Nova Matrícula</h3>
            <br>
            <form action="matriculas.php" method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="id_aluno">Selecione o Aluno: *</label>
                        <select name="id_aluno" id="id_aluno" required>
                            <option value="">-- Escolha um Aluno --</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>">
                                    <?php echo htmlspecialchars($aluno['nome']); ?> (<?php echo htmlspecialchars($aluno['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_curso">Selecione o Curso: *</label>
                        <select name="id_curso" id="id_curso" required>
                            <option value="">-- Escolha um Curso --</option>
                            <?php foreach ($cursos as $curso): ?>
                                <option value="<?php echo $curso['id']; ?>">
                                    <?php echo htmlspecialchars($curso['nome']); ?> - <?php echo htmlspecialchars($curso['categoria']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status da Matrícula:</label>
                        <select name="status" id="status">
                            <option value="Ativa">Ativa</option>
                            <option value="Concluída">Concluída</option>
                            <option value="Trancada">Trancada</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Confirmar Matrícula</button>
            </form>
        </div>

        <!-- Tabela de Listagem de Matrículas (SQL JOIN) -->
        <div class="admin-card">
            <h3>Relatório Geral de Matrículas (SQL INNER JOIN)</h3>
            <?php if (empty($matriculas)): ?>
                <p>Nenhuma matrícula registrada até o momento.</p>
            <?php else: ?>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>ID Matrícula</th>
                            <th>Aluno</th>
                            <th>E-mail do Aluno</th>
                            <th>Curso</th>
                            <th>Data de Matrícula</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matriculas as $mat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($mat['id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($mat['aluno_nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($mat['aluno_email']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($mat['curso_nome']); ?></strong><br>
                                    <small class="badge"><?php echo htmlspecialchars($mat['curso_categoria']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($mat['data_matricula']); ?></td>
                                <td>
                                    <?php if ($mat['status'] === 'Ativa'): ?>
                                        <span class="badge" style="background-color: #D1FAE5; color: #065F46;">Ativa</span>
                                    <?php elseif ($mat['status'] === 'Concluída'): ?>
                                        <span class="badge" style="background-color: #DBEAFE; color: #1E40AF;">Concluída</span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: #FEF3C7; color: #92400E;"><?php echo htmlspecialchars($mat['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="matriculas.php?action=delete&id=<?php echo $mat['id']; ?>" onclick="return confirm('Tem certeza que deseja cancelar esta matrícula?');" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Cancelar</a>
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
