<?php 

// A administração de matrículas só pode ser acessada por administradores.
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();
require_once __DIR__ . '/../includes/functions.php';

$mensagem = '';
// O token evita que outro site envie alterações usando a sessão do administrador.
if (empty($_SESSION['csrf_matriculas'])) {
    $_SESSION['csrf_matriculas'] = bin2hex(random_bytes(32));
}

// Trata o cancelamento de uma matrícula solicitado pela tabela.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cancelar') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_matriculas'], (string)$token)) {
        $mensagem = '<div class="alert alert-danger">Falha na validação de segurança (token expirado). Tente novamente.</div>';
    } else {
        $id_cancelar = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        $stmt = $conexao ? $conexao->prepare('DELETE FROM matriculas WHERE id = :id') : false;
        if ($id_cancelar && $stmt && $stmt->execute(['id' => $id_cancelar]) && $stmt->rowCount() > 0) {
            $mensagem = '<div class="alert alert-success">Matrícula cancelada com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao cancelar matrícula.</div>';
        }
    }
}

// Registra uma nova matrícula criada pelo formulário administrativo.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') !== 'cancelar') {
    $id_aluno = $_POST['id_aluno'] ?? null;
    $id_curso = $_POST['id_curso'] ?? null;
    $status = $_POST['status'] ?? 'Ativa';
    if ($id_aluno && $id_curso && $conexao) {
        try {
            $stmt = $conexao->prepare(
                'INSERT INTO matriculas (id_aluno, id_curso, status)
                 VALUES (:id_aluno, :id_curso, :status)'
            );
            $salvo = $stmt->execute([
                'id_aluno' => (int) $id_aluno,
                'id_curso' => (int) $id_curso,
                'status' => $status
            ]);
        } catch (PDOException $e) {
            error_log('Erro ao criar matrícula: ' . $e->getMessage());
            $salvo = false;
        }
        if ($salvo) {
            $mensagem = '<div class="alert alert-success">Aluno matriculado com sucesso no curso selecionado!</div>';
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao realizar matrícula.</div>';
        }
    } else {
        $mensagem = '<div class="alert alert-warning">Selecione o Aluno e o Curso para realizar a matrícula.</div>';
    }
}

// Carrega as opções dos formulários e os dados do relatório de matrículas.
$alunos = $conexao
    ? $conexao->query('SELECT id, nome, email FROM alunos ORDER BY nome')->fetchAll()
    : [];
$cursos = $conexao
    ? $conexao->query('SELECT id, nome FROM cursos ORDER BY nome')->fetchAll()
    : [];
$matriculas = $conexao
    ? $conexao->query(
        'SELECT m.id, m.data_matricula, m.status,
                a.nome AS aluno_nome, a.email AS aluno_email, a.turma,
                c.nome AS curso_nome, c.categoria AS curso_categoria
         FROM matriculas m
         JOIN alunos a ON a.id = m.id_aluno
         JOIN cursos c ON c.id = m.id_curso
         ORDER BY m.id DESC'
    )->fetchAll()
    : [];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    
    <meta charset="UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Matrículas | HighTech School</title>
    
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Formulário de matrícula e relatório que relaciona alunos e cursos. -->
<main class="container">
        
        <h2>Gestão de Matrículas (Área Restrita - Admin)</h2>
        
        <p>Vincule Alunos aos Cursos da escola através da tabela intermediária de Matrículas.</p>

<?php echo $mensagem; ?>

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
                                    
                                    <form action="matriculas.php" method="post" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja cancelar esta matrícula?');">
                                        <input type="hidden" name="acao" value="cancelar">
                                        <input type="hidden" name="id" value="<?php echo (int) $mat['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_matriculas'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; cursor: pointer;">Cancelar</button>
                                    </form>
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
