<?php 
/* INICIALIZAÇÃO E CONTROLE DE ACESSO */
// Importa o arquivo de autenticação — ele verifica se o usuário está logado na sessão
require_once __DIR__ . '/../includes/auth.php';
// Garante que apenas administradores acessem esta página; redireciona caso contrário
exigirAdmin();

// Importa as funções auxiliares do sistema (matricular, listar, excluir matrículas)
require_once __DIR__ . '/../includes/functions.php';

/* VARIÁVEIS DE CONTROLE DA PÁGINA */
// Variável que armazenará mensagens de feedback para o usuário (sucesso, erro ou aviso)
$mensagem = '';

// Inicializa o token CSRF de sessão para proteger operações da tela de matrículas
if (empty($_SESSION['csrf_matriculas'])) {
    $_SESSION['csrf_matriculas'] = bin2hex(random_bytes(32));
}

/* BLOCO DE CANCELAMENTO / EXCLUSÃO DE MATRÍCULA (SEGURO VIA POST COM CSRF) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cancelar') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_matriculas'], (string)$token)) {
        $mensagem = '<div class="alert alert-danger">Falha na validação de segurança (token expirado). Tente novamente.</div>';
    } else {
        $id_cancelar = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        if ($id_cancelar && excluirMatricula($conexao, $id_cancelar)) {
            $mensagem = '<div class="alert alert-success">Matrícula cancelada com sucesso!</div>';
        } else {
            $mensagem = '<div class="alert alert-danger">Erro ao cancelar matrícula.</div>';
        }
    }
}

/* BLOCO DE CRIAÇÃO DE NOVA MATRÍCULA (FORMULÁRIO POST) */
// Verifica se o formulário foi enviado via POST (ação de confirmar nova matrícula)
// A matrícula é a tabela intermediária que implementa o relacionamento N:N entre alunos e cursos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura o ID do aluno selecionado no <select> do formulário
    $id_aluno = $_POST['id_aluno'] ?? null;
    // Captura o ID do curso selecionado no <select> do formulário
    $id_curso = $_POST['id_curso'] ?? null;
    // Captura o status inicial da matrícula; padrão é 'Ativa'
    $status = $_POST['status'] ?? 'Ativa';

    // Valida que os dois campos essenciais foram preenchidos (aluno e curso são obrigatórios)
    if ($id_aluno && $id_curso) {
        // Chama a função que insere um novo registro na tabela de matrículas (INSERT)
        // Essa função cria o vínculo entre o aluno e o curso no banco de dados
        if (matricularAluno($conexao, $id_aluno, $id_curso, $status)) {
            // Matrícula realizada com sucesso: exibe mensagem verde
            $mensagem = '<div class="alert alert-success">Aluno matriculado com sucesso no curso selecionado!</div>';
        } else {
            // Erro ao inserir (ex: matrícula duplicada ou violação de constraint): exibe mensagem vermelha
            $mensagem = '<div class="alert alert-danger">Erro ao realizar matrícula.</div>';
        }
    } else {
        // O usuário não selecionou aluno ou curso: exibe aviso amarelo
        $mensagem = '<div class="alert alert-warning">Selecione o Aluno e o Curso para realizar a matrícula.</div>';
    }
}

/* BUSCA DOS DADOS PARA PREENCHER OS SELECTS E A TABELA */
// Busca todos os alunos cadastrados para popular o <select> de alunos no formulário
$alunos = listarAlunos($conexao);
// Busca todos os cursos cadastrados para popular o <select> de cursos no formulário
$cursos = listarCursos($conexao);
// Busca todas as matrículas do banco usando SQL INNER JOIN (alunos + cursos + matrículas)
// O JOIN retorna os dados combinados: nome do aluno, nome do curso, data e status em uma única consulta
$matriculas = listarMatriculas($conexao);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Define que o arquivo usa codificação UTF-8 (suporte a acentos e caracteres especiais) -->
    <meta charset="UTF-8">
    <!-- Garante que a página se adapte corretamente a dispositivos móveis (responsividade) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título que aparece na aba do navegador; (N:N) indica a relação muitos-para-muitos -->
    <title>Gestão de Matrículas (N:N) - HighTech System</title>
    <!-- Importa o arquivo CSS principal do projeto com todos os estilos visuais -->
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <!-- Inclui o cabeçalho padrão do sistema (menu de navegação, logo, etc.) -->
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Área principal da página envolta pelo container para centralizar o conteúdo -->
    <main class="container">
        <!-- Título da página indicando que é uma área restrita ao administrador -->
        <h2>Gestão de Matrículas (Área Restrita - Admin)</h2>
        <!-- Subtítulo explicativo: deixa claro que a matrícula é a tabela intermediária do relacionamento N:N -->
        <p>Vincule Alunos aos Cursos da escola através da tabela intermediária de Matrículas.</p>

        <!-- Exibe a mensagem de feedback (sucesso, erro ou aviso) se existir -->
        <?php echo $mensagem; ?>

        <!-- 
             FORMULÁRIO DE NOVA MATRÍCULA
             Diferente de alunos e cursos, matrículas não têm edição —
             apenas criação e cancelamento, pois são vínculos pontuais
        -->
        <div class="admin-card">
            <h3>Realizar Nova Matrícula</h3>
            <br>
            <!-- O formulário envia os dados para matriculas.php via POST -->
            <form action="matriculas.php" method="post">
                <!-- Grid de campos para organizar o formulário em colunas -->
                <div class="form-grid">
                    <!-- Campo: Seleção do Aluno (obrigatório) -->
                    <div class="form-group">
                        <label for="id_aluno">Selecione o Aluno: *</label>
                        <!-- Select dinâmico preenchido com todos os alunos do banco de dados -->
                        <select name="id_aluno" id="id_aluno" required>
                            <!-- Opção padrão vazia para forçar o usuário a selecionar um aluno -->
                            <option value="">-- Escolha um Aluno --</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <!-- O value guarda o ID do aluno (chave estrangeira que será gravada na matrícula) -->
                                <!-- O texto visível mostra nome + e-mail para facilitar a identificação -->
                                <option value="<?php echo $aluno['id']; ?>">
                                    <?php echo htmlspecialchars($aluno['nome']); ?> (<?php echo htmlspecialchars($aluno['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Campo: Seleção do Curso (obrigatório) -->
                    <div class="form-group">
                        <label for="id_curso">Selecione o Curso: *</label>
                        <!-- Select dinâmico preenchido com todos os cursos do banco de dados -->
                        <select name="id_curso" id="id_curso" required>
                            <!-- Opção padrão vazia para forçar o usuário a selecionar um curso -->
                            <option value="">-- Escolha um Curso --</option>
                            <?php foreach ($cursos as $curso): ?>
                                <!-- O value guarda o ID do curso (chave estrangeira que será gravada na matrícula) -->
                                <!-- O texto visível mostra nome do curso + sua categoria para facilitar a escolha -->
                                <option value="<?php echo $curso['id']; ?>">
                                    <?php echo htmlspecialchars($curso['nome']); ?> - <?php echo htmlspecialchars($curso['categoria']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Campo: Status inicial da Matrícula (define o estado atual do vínculo aluno-curso) -->
                    <div class="form-group">
                        <label for="status">Status da Matrícula:</label>
                        <select name="status" id="status">
                            <!-- Ativa: aluno está cursando atualmente -->
                            <option value="Ativa">Ativa</option>
                            <!-- Concluída: aluno finalizou o curso com sucesso -->
                            <option value="Concluída">Concluída</option>
                            <!-- Trancada: matrícula pausada temporariamente -->
                            <option value="Trancada">Trancada</option>
                        </select>
                    </div>
                </div>

                <!-- Botão que envia o formulário e cria a matrícula no banco de dados -->
                <button type="submit" class="btn btn-primary">Confirmar Matrícula</button>
            </form>
        </div>

        <!--
             TABELA DE RELATÓRIO GERAL DE MATRÍCULAS
             Os dados vêm de um SQL INNER JOIN entre as tabelas:
             matriculas + alunos + cursos — por isso temos nome do
             aluno e nome do curso em uma única consulta unificada
        -->
        <div class="admin-card">
            <!-- O título menciona INNER JOIN pois é um conceito SQL importante desta tela -->
            <h3>Relatório Geral de Matrículas (SQL INNER JOIN)</h3>
            <?php if (empty($matriculas)): ?>
                <!-- Mensagem amigável quando não há nenhuma matrícula registrada -->
                <p>Nenhuma matrícula registrada até o momento.</p>
            <?php else: ?>
                <!-- Tabela estilizada com a classe CSS do projeto -->
                <table class="styled-table">
                    <thead>
                        <tr>
                            <!-- Cabeçalhos das colunas — combinam dados das três tabelas do JOIN -->
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
                        <!-- Itera sobre cada matrícula retornada pelo JOIN do banco de dados -->
                        <?php foreach ($matriculas as $mat): ?>
                            <tr>
                                <!-- ID da própria matrícula na tabela intermediária -->
                                <td><?php echo htmlspecialchars($mat['id']); ?></td>
                                <!-- Nome do aluno vindo da tabela alunos via JOIN (campo aluno_nome) -->
                                <td><strong><?php echo htmlspecialchars($mat['aluno_nome']); ?></strong></td>
                                <!-- E-mail do aluno vindo da tabela alunos via JOIN (campo aluno_email) -->
                                <td><?php echo htmlspecialchars($mat['aluno_email']); ?></td>
                                <td>
                                    <!-- Nome do curso em negrito vindo da tabela cursos via JOIN (campo curso_nome) -->
                                    <strong><?php echo htmlspecialchars($mat['curso_nome']); ?></strong><br>
                                    <!-- Categoria do curso exibida como badge pequeno abaixo do nome -->
                                    <small class="badge"><?php echo htmlspecialchars($mat['curso_categoria']); ?></small>
                                </td>
                                <!-- Data em que a matrícula foi criada no sistema -->
                                <td><?php echo htmlspecialchars($mat['data_matricula']); ?></td>
                                <td>
                                    <?php if ($mat['status'] === 'Ativa'): ?>
                                        <!-- Badge verde para matrícula ativa — aluno em andamento no curso -->
                                        <span class="badge" style="background-color: #D1FAE5; color: #065F46;">Ativa</span>
                                    <?php elseif ($mat['status'] === 'Concluída'): ?>
                                        <!-- Badge azul para matrícula concluída — aluno finalizou o curso -->
                                        <span class="badge" style="background-color: #DBEAFE; color: #1E40AF;">Concluída</span>
                                    <?php else: ?>
                                        <!-- Badge amarelo para qualquer outro status (ex: Trancada) -->
                                        <!-- Exibe o texto do status dinamicamente para suportar status futuros -->
                                        <span class="badge" style="background-color: #FEF3C7; color: #92400E;"><?php echo htmlspecialchars($mat['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Formulário POST seguro para cancelar matrícula com proteção CSRF -->
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

    <!-- Inclui o rodapé padrão do sistema (informações de copyright, links, etc.) -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
