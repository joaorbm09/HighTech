<?php
// Restringe a gestão de contas ao perfil administrador.
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();
require_once __DIR__ . '/../includes/functions.php';

$mensagem = '';
$usuarioEdicao = null;
$usuarios = [];

if (empty($_SESSION['csrf_usuarios'])) {
    $_SESSION['csrf_usuarios'] = bin2hex(random_bytes(32));
}

// Processa edição, criação e exclusão; a transação mantém conta e aluno sincronizados.
try {
    if (!$conexao) {
        throw new RuntimeException('Não foi possível acessar o banco de dados.');
    }

    $idEdicao = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
    if ($idEdicao) {
        $stmt = $conexao->prepare(
            'SELECT id, nome, email, perfil FROM usuarios WHERE id = :id'
        );
        $stmt->execute(['id' => $idEdicao]);
        $usuarioEdicao = $stmt->fetch();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Confere o token antes de aceitar qualquer alteração enviada pelo navegador.
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['csrf_usuarios'], $token)) {
            $mensagem = 'A sessão expirou. Atualize a página e tente novamente.';
        } else {
            $acao = $_POST['acao'] ?? '';
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

            if ($acao === 'excluir' && $id) {
                // Protege a própria conta e garante que sempre reste um administrador.
                $stmt = $conexao->prepare(
                    'SELECT id, perfil FROM usuarios WHERE id = :id'
                );
                $stmt->execute(['id' => $id]);
                $alvo = $stmt->fetch();
                $admins = (int) $conexao->query(
                    "SELECT COUNT(*) FROM usuarios WHERE perfil = 'admin'"
                )->fetchColumn();

                if (!$alvo) {
                    $mensagem = 'A conta não foi encontrada.';
                } elseif ((int) $id === (int) $_SESSION['user_id']) {
                    $mensagem = 'Você não pode excluir sua própria conta.';
                } elseif ($alvo['perfil'] === 'admin' && $admins <= 1) {
                    $mensagem = 'É necessário manter pelo menos um administrador.';
                } else {
                    $stmt = $conexao->prepare('DELETE FROM usuarios WHERE id = :id');
                    $stmt->execute(['id' => $id]);
                    $mensagem = 'Conta excluída. Os dados acadêmicos do aluno foram mantidos.';
                }
            } elseif ($acao === 'salvar') {
                // Valida os campos da conta antes de consultar possíveis duplicidades.
                $id = $id ?: null;
                $nome = trim($_POST['nome'] ?? '');
                $email = strtolower(trim($_POST['email'] ?? ''));
                $perfil = $_POST['perfil'] ?? '';
                $senha = $_POST['senha'] ?? '';

                if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $mensagem = 'Informe um nome e um e-mail válidos.';
                } elseif (!in_array($perfil, ['aluno', 'admin'], true)) {
                    $mensagem = 'Selecione um perfil válido.';
                } elseif ((!$id || $senha !== '') && strlen($senha) < 8) {
                    $mensagem = 'A senha deve ter pelo menos 8 caracteres.';
                } else {
                    $stmt = $conexao->prepare(
                        'SELECT id FROM usuarios WHERE LOWER(email) = LOWER(:email)'
                    );
                    $stmt->execute(['email' => $email]);
                    $existente = $stmt->fetch();

                    $alvo = false;
                    if ($id) {
                        $stmt = $conexao->prepare(
                            'SELECT id, nome, email, perfil FROM usuarios WHERE id = :id'
                        );
                        $stmt->execute(['id' => $id]);
                        $alvo = $stmt->fetch();
                    }

                    $admins = (int) $conexao->query(
                        "SELECT COUNT(*) FROM usuarios WHERE perfil = 'admin'"
                    )->fetchColumn();

                    if ($existente && (!$id || (int) $existente['id'] !== (int) $id)) {
                        $mensagem = 'Já existe uma conta com esse e-mail.';
                    } elseif ($id && !$alvo) {
                        $mensagem = 'A conta não foi encontrada.';
                    } elseif ($id && (int) $id === (int) $_SESSION['user_id'] && $perfil !== 'admin') {
                        $mensagem = 'Outro administrador precisa alterar o seu perfil.';
                    } elseif ($id && $alvo['perfil'] === 'admin' && $perfil !== 'admin' && $admins <= 1) {
                        $mensagem = 'É necessário manter pelo menos um administrador.';
                    } else {
                        // Salva a conta e seu cadastro acadêmico como uma única operação.
                        $emailAntigo = $alvo['email'] ?? $email;
                        $stmt = $conexao->prepare(
                            'SELECT id FROM alunos WHERE LOWER(email) = LOWER(:email)'
                        );
                        $stmt->execute(['email' => $emailAntigo]);
                        $aluno = $stmt->fetch();

                        try {
                            $conexao->beginTransaction();
                            if ($id) {
                                $sql = $senha === ''
                                    ? 'UPDATE usuarios SET nome = :nome, email = :email, perfil = :perfil WHERE id = :id'
                                    : 'UPDATE usuarios SET nome = :nome, email = :email, perfil = :perfil, senha = :senha WHERE id = :id';
                                $stmt = $conexao->prepare($sql);
                                $dados = [
                                    'nome' => $nome,
                                    'email' => $email,
                                    'perfil' => $perfil,
                                    'id' => $id
                                ];
                                if ($senha !== '') {
                                    $dados['senha'] = password_hash($senha, PASSWORD_DEFAULT);
                                }
                                $salvo = $stmt->execute($dados);
                            } else {
                                $stmt = $conexao->prepare(
                                    'INSERT INTO usuarios (nome, email, senha, perfil)
                                     VALUES (:nome, :email, :senha, :perfil)'
                                );
                                $salvo = $stmt->execute([
                                    'nome' => $nome,
                                    'email' => $email,
                                    'senha' => password_hash($senha, PASSWORD_DEFAULT),
                                    'perfil' => $perfil
                                ]);
                            }

                            if ($salvo && ($aluno || $perfil === 'aluno')) {
                                $sql = $aluno
                                    ? 'UPDATE alunos SET nome = :nome, email = :email WHERE id = :id'
                                    : "INSERT INTO alunos (nome, email, turma) VALUES (:nome, :email, 'HT-2026')";
                                $stmt = $conexao->prepare($sql);
                                $dados = ['nome' => $nome, 'email' => $email];
                                if ($aluno) {
                                    $dados['id'] = $aluno['id'];
                                }
                                $salvo = $stmt->execute($dados);
                            }

                            if (!$salvo) {
                                throw new RuntimeException('Não foi possível salvar a conta.');
                            }

                            $conexao->commit();
                            $mensagem = $id ? 'Conta atualizada.' : 'Conta criada.';
                            if ($id && (int) $id === (int) $_SESSION['user_id']) {
                                $_SESSION['user_nome'] = $nome;
                                $_SESSION['user_email'] = $email;
                            }
                            $usuarioEdicao = null;
                        } catch (PDOException $e) {
                            if ($conexao->inTransaction()) {
                                $conexao->rollBack();
                            }
                            error_log('Erro ao salvar conta administrativa: ' . $e->getMessage());
                            $mensagem = 'Não foi possível salvar a conta. Confira os dados e tente novamente.';
                        } catch (RuntimeException $e) {
                            if ($conexao->inTransaction()) {
                                $conexao->rollBack();
                            }
                            $mensagem = 'Não foi possível salvar a conta. Confira os dados e tente novamente.';
                        }
                    }
                }
            }
        }
    }

    // Atualiza a tabela apresentada na página depois de processar o formulário.
    $usuarios = $conexao->query(
        'SELECT id, nome, email, perfil, criado_em FROM usuarios ORDER BY nome'
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Erro ao carregar usuários: ' . $e->getMessage());
    $mensagem = 'Não foi possível carregar as contas.';
} catch (RuntimeException $e) {
    $mensagem = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários | HighTech School</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Formulário para criar/editar contas e tabela com os usuários existentes. -->
    <main class="container">
        <h1>Usuários</h1>
        <p>Cadastre contas e escolha se terão acesso de aluno ou administrador.</p>

        <?php if ($mensagem !== ''): ?>
            <div class="alert"><?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <section class="admin-card">
            <h2><?php echo $usuarioEdicao ? 'Editar conta' : 'Criar conta'; ?></h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_usuarios'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="acao" value="salvar">
                <?php if ($usuarioEdicao): ?>
                    <input type="hidden" name="id" value="<?php echo (int) $usuarioEdicao['id']; ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input id="nome" name="nome" required value="<?php echo htmlspecialchars($usuarioEdicao['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($usuarioEdicao['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="perfil">Perfil</label>
                        <select id="perfil" name="perfil" required>
                            <option value="aluno" <?php echo ($usuarioEdicao['perfil'] ?? 'aluno') === 'aluno' ? 'selected' : ''; ?>>Aluno</option>
                            <option value="admin" <?php echo ($usuarioEdicao['perfil'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="senha"><?php echo $usuarioEdicao ? 'Nova senha (opcional)' : 'Senha inicial'; ?></label>
                        <input type="password" id="senha" name="senha" minlength="8" <?php echo $usuarioEdicao ? '' : 'required'; ?>>
                    </div>
                </div>
                <button type="submit"><?php echo $usuarioEdicao ? 'Salvar alterações' : 'Criar conta'; ?></button>
                <?php if ($usuarioEdicao): ?>
                    <a class="btn btn-outline" href="usuarios.php">Cancelar</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="admin-card">
            <h2>Contas cadastradas</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $conta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($conta['nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($conta['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($conta['perfil']), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <a class="btn btn-outline" href="usuarios.php?editar=<?php echo (int) $conta['id']; ?>">Editar</a>
                                    <?php if ((int) $conta['id'] !== (int) $_SESSION['user_id']): ?>
                                        <form method="post" style="display:inline" onsubmit="return confirm('Excluir esta conta? Os dados acadêmicos serão mantidos.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_usuarios'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?php echo (int) $conta['id']; ?>">
                                            <button class="btn-danger" type="submit">Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
