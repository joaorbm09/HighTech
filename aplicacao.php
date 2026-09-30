<?php 
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$usuario_logado = obterUsuarioLogado();
$mensagem = '';

if (isset($_GET['erro']) && $_GET['erro'] === 'acesso_negado') {
    $mensagem = '<div class="alert alert-warning">Acesso negado: Você precisa ter perfil de Administrador para acessar os painéis de gestão.</div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscrever'])) {
    $nome = $_POST['nome'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    $email = $_POST['email'] ?? '';
    $id_curso = $_POST['id_curso'] ?? '';
    $turma = 'HT-2026';
    $nasc = $_POST['nasc'] ?? date('Y-m-d');

    if (!empty($nome) && !empty($email) && !empty($id_curso)) {
        // Verifica se aluno já existe ou cadastra novo
        $aluno_existente = buscarUsuarioPorEmail($conexao, $email);
        
        $id_aluno = null;
        $stmt_check = $conexao->prepare("SELECT id FROM alunos WHERE email = :email");
        $stmt_check->bindParam(":email", $email);
        $stmt_check->execute();
        $aluno_db = $stmt_check->fetch();

        if ($aluno_db) {
            $id_aluno = $aluno_db['id'];
        } else {
            if (cadastrarAluno($conexao, $nome, $cpf, $email, $turma, $nasc, true)) {
                $id_aluno = $conexao->lastInsertId('alunos_id_seq');
            }
        }

        if ($id_aluno && matricularAluno($conexao, $id_aluno, $id_curso, 'Ativa')) {
            $mensagem = '<div class="alert alert-success">Inscrição realizada com sucesso! Seja bem-vindo à HighTech School.</div>';
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao realizar inscrição no curso.</div>';
        }
    } else {
        $mensagem = '<div class="alert alert-warning">Por favor, preencha todos os campos obrigatórios.</div>';
    }
}

$cursos = listarCursos($conexao);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HighTech School - Escola de Tecnologia</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- HERO SECTION ESCOLA -->
    <div class="hero">
        <div class="container">
            <span class="badge">Formação Prática & Mercado</span>
            <h2>Construa sua Carreira em Tecnologia e Gestão</h2>
            <p>Cursos presenciais e online com foco em aplicação real, programação PHP, PostgreSQL e metodologias ágeis.</p>
        </div>
    </div>

    <!-- CONTEÚDO PRINCIPAL -->
    <main class="container">
        <?php echo $mensagem; ?>

        <!-- Apresentacao da escola -->
        <section id="sobre">
            <div class="section-header">
                <h2>Sobre a Escola</h2>
                <p>Na HighTech School você aprende sobre tecnologia, banco de dados relacional e gestão de forma prática e conectada com o mercado.</p>
            </div>
        </section>

        <!-- Lista Dinâmica de Cursos vinda do Banco PostgreSQL -->
        <section id="cursos">
            <div class="section-header">
                <h2>Cursos Oferecidos (Dados em Tempo Real do Banco de Dados)</h2>
                <p>Escolha sua trilha de formação e inscreva-se diretamente.</p>
            </div>

            <div class="grid">
                <?php if (empty($cursos)): ?>
                    <p>Nenhum curso cadastrado no momento.</p>
                <?php else: ?>
                    <?php foreach ($cursos as $curso): ?>
                        <article class="card">
                            <div>
                                <span class="badge"><?php echo htmlspecialchars($curso['categoria']); ?></span>
                                <h3><?php echo htmlspecialchars($curso['nome']); ?></h3>
                                <p><?php echo htmlspecialchars($curso['descricao']); ?></p>
                                <p><strong>Carga Horária:</strong> <?php echo htmlspecialchars($curso['carga_horaria']); ?> horas</p>
                            </div>
                            <a href="#inscricao" class="btn btn-primary">Inscrever-se neste Curso</a>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Formulário de Inscrição Direta -->
        <section id="inscricao" class="admin-card">
            <h2>Faça sua Inscrição Online </h2>
            <p style="margin-bottom: 1.5rem;">Preencha seus dados abaixo para se cadastrar como aluno e se matricular em um dos nossos cursos.</p>

            <form action="aplicacao.php" method="post">
                <input type="hidden" name="inscrever" value="1">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nome">Nome Completo: *</label>
                        <input type="text" name="nome" id="nome" required value="<?php echo htmlspecialchars($usuario_logado['nome'] ?? ''); ?>" placeholder="Digite seu nome">
                    </div>

                    <div class="form-group">
                        <label for="cpf">CPF:</label>
                        <input type="text" name="cpf" id="cpf" placeholder="000.000.000-00">
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail: *</label>
                        <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($usuario_logado['email'] ?? ''); ?>" placeholder="seu@email.com">
                    </div>

                    <div class="form-group">
                        <label for="nasc">Data de Nascimento:</label>
                        <input type="date" name="nasc" id="nasc">
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="id_curso">Selecione o Curso desejado: *</label>
                        <select name="id_curso" id="id_curso" required>
                            <option value="">-- Escolha um Curso --</option>
                            <?php foreach ($cursos as $curso): ?>
                                <option value="<?php echo $curso['id']; ?>">
                                    <?php echo htmlspecialchars($curso['nome']); ?> (<?php echo htmlspecialchars($curso['categoria']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Confirmar Minha Inscrição</button>
            </form>
        </section>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
