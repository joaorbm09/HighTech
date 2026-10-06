<?php 

// A tela de cadastro acadêmico é exclusiva para administradores.
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();
require_once __DIR__ . '/../includes/functions.php';

$mensagem = '';
$aluno_edicao = null;
// Cria um token para validar os formulários que alteram dados de alunos.
if (empty($_SESSION['csrf_alunos'])) {
    $_SESSION['csrf_alunos'] = bin2hex(random_bytes(32));
}

// Remove o aluno selecionado depois de conferir o token do formulário.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_alunos'], (string)$token)) {
        $mensagem = '<div class="alert alert-danger">Falha na validação de segurança (token expirado). Tente novamente.</div>';
    } else {
        $id_excluir = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        $stmt = $conexao ? $conexao->prepare('DELETE FROM alunos WHERE id = :id') : false;
        if ($id_excluir && $stmt && $stmt->execute(['id' => $id_excluir]) && $stmt->rowCount() > 0) {
            $mensagem = '<div class="alert alert-success">Aluno excluído com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao excluir aluno. Verifique restrições no banco de dados.</div>';
        }
    }
}

// Carrega os dados do aluno selecionado para edição.
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($conexao && $id) {
        $stmt = $conexao->prepare('SELECT * FROM alunos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $aluno_edicao = $stmt->fetch();
    }
}

// Valida os dados recebidos e insere ou atualiza o cadastro acadêmico.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') !== 'excluir') {
    $id = $_POST['id'] ?? null;
    $nome = $_POST['nome'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $email = $_POST['email'] ?? '';
    $turma = $_POST['turma'] ?? '';
    $ativo = $_POST['ativo'] ?? 'true';
    $nasc_raw = trim($_POST['nasc'] ?? '');
    $nasc_erro = '';
    $nasc = validarDataNascimento($nasc_raw, $nasc_erro);
    if ($nasc === false) {
        $mensagem = '<div class="alert alert-warning">' . htmlspecialchars($nasc_erro) . '</div>';
    } else if (!$conexao) {
        $mensagem = '<div class="alert alert-danger">Não foi possível acessar o banco de dados.</div>';
    } else if (!empty($nome) && !empty($email)) {
        if ($id) {
            $stmt = $conexao->prepare(
                'UPDATE alunos
                 SET nome = :nome, cpf = :cpf, email = :email, turma = :turma,
                     nascimento = :nascimento, ativo = :ativo
                 WHERE id = :id'
            );
            $salvo = $stmt->execute([
                'nome' => trim($nome),
                'cpf' => trim($cpf) !== '' ? trim($cpf) : null,
                'email' => strtolower(trim($email)),
                'turma' => trim($turma),
                'nascimento' => $nasc,
                'ativo' => $ativo === 'true',
                'id' => (int) $id
            ]);
            if ($salvo) {
                $mensagem = '<div class="alert alert-success">Dados do aluno atualizados com sucesso!</div>';
                $aluno_edicao = null;
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao atualizar aluno.</div>';
            }
        } else {
            $stmt = $conexao->prepare(
                'INSERT INTO alunos (nome, cpf, email, turma, nascimento, ativo)
                 VALUES (:nome, :cpf, :email, :turma, :nascimento, :ativo)'
            );
            $salvo = $stmt->execute([
                'nome' => trim($nome),
                'cpf' => trim($cpf) !== '' ? trim($cpf) : null,
                'email' => strtolower(trim($email)),
                'turma' => trim($turma),
                'nascimento' => $nasc,
                'ativo' => $ativo === 'true'
            ]);
            if ($salvo) {
                $mensagem = '<div class="alert alert-success">Novo aluno cadastrado com sucesso!</div>';
            } else {
                $mensagem = '<div class="alert alert-danger">Erro ao cadastrar aluno.</div>';
            }
        }
    } else {
        $mensagem = '<div class="alert alert-warning">Campos obrigatórios: Nome e E-mail.</div>';
    }
}

// Prepara a lista de alunos e os limites de data usados pelo formulário.
$alunos = $conexao
    ? $conexao->query('SELECT * FROM alunos ORDER BY id ASC')->fetchAll()
    : [];
$data_max_nasc = date('Y-m-d', strtotime('-14 years'));
$data_min_nasc = date('Y-m-d', strtotime('-100 years'));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    
    <meta charset="UTF-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Alunos | HighTech School</title>
    
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Formulário de aluno e lista administrativa dos cadastros. -->
<main class="container">
        
        <h2>Gestão de Alunos (Área Restrita - Admin)</h2>
        <p>Gerencie o cadastro de estudantes do sistema.</p>

<?php echo $mensagem; ?>

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
                        
                        <input type="date" name="nasc" id="nasc" min="<?php echo $data_min_nasc; ?>" max="<?php echo $data_max_nasc; ?>" value="<?php echo htmlspecialchars($aluno_edicao['nascimento'] ?? ''); ?>">
                        <small style="color: var(--text-muted); font-size: 0.8rem;">Idade mínima: 14 anos.</small>
                    </div>

<div class="form-group">
                        <label for="ativo">Status Ativo:</label>
                        <select name="ativo" id="ativo">
                            <?php
                            $isAtivo = isset($aluno_edicao['ativo']) ? ($aluno_edicao['ativo'] === true || $aluno_edicao['ativo'] === 't' || $aluno_edicao['ativo'] == 1) : true;
                            ?>
                            
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
                                    <?php
                                    if ($aluno['ativo'] === true || $aluno['ativo'] === 't' || $aluno['ativo'] == 1): ?>
                                        
                                        <span class="badge" style="background-color: #D1FAE5; color: #065F46;">Ativo</span>
                                    <?php else: ?>
                                        
                                        <span class="badge" style="background-color: #FEE2E2; color: #991B1B;">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    
                                    <a href="alunos.php?action=edit&id=<?php echo $aluno['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Editar</a>
                                    
                                    <form action="alunos.php" method="post" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este aluno?');">
                                        <input type="hidden" name="acao" value="excluir">
                                        <input type="hidden" name="id" value="<?php echo (int) $aluno['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_alunos'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; cursor: pointer;">Excluir</button>
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
