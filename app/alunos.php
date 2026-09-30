<?php 
/* INICIALIZAÇÃO E CONTROLE DE ACESSO */
// Importa o arquivo de autenticação — ele verifica se o usuário está logado
require_once __DIR__ . '/../includes/auth.php';
// Garante que apenas administradores acessem esta página; redireciona caso contrário
exigirAdmin();

// Importa as funções auxiliares do sistema (listar, cadastrar, atualizar, excluir alunos)
require_once __DIR__ . '/../includes/functions.php';

/* VARIÁVEIS DE CONTROLE DA PÁGINA */
// Variável que armazenará mensagens de feedback para o usuário (sucesso, erro, aviso)
$mensagem = '';
// Variável que guarda os dados do aluno quando estamos no modo de edição; null = modo cadastro
$aluno_edicao = null;

/* BLOCO DE EXCLUSÃO DE ALUNO */
// Verifica se a URL contém ?action=delete&id=X — indica que o usuário clicou em "Excluir"
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // Chama a função que executa o DELETE no banco de dados passando a conexão e o ID do aluno
    if (excluirAluno($conexao, $_GET['id'])) {
        // Se a exclusão foi bem-sucedida, exibe mensagem verde de confirmação
        $mensagem = '<div class="alert alert-success">Aluno excluído com sucesso!</div>';
    } else {
        // Se houve erro no banco de dados, exibe mensagem vermelha de falha
        $mensagem = '<div class="alert alert-danger">Erro ao excluir aluno.</div>';
    }
}

/* BLOCO DE CARREGAMENTO PARA EDIÇÃO */
// Verifica se a URL contém ?action=edit&id=X — indica que o usuário clicou em "Editar"
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    // Busca no banco de dados os dados completos do aluno pelo ID recebido via GET
    // O resultado preenche o formulário com os dados atuais do aluno
    $aluno_edicao = buscarAlunoPorId($conexao, $_GET['id']);
}

/* BLOCO DE SALVAMENTO E ATUALIZAÇÃO (FORMULÁRIO POST) */
// Verifica se o formulário foi enviado via POST (método padrão para envio de dados)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura o ID do aluno (presente somente no modo edição; null no cadastro)
    $id = $_POST['id'] ?? null;
    // Captura o nome digitado no formulário; se não enviado, usa string vazia
    $nome = $_POST['nome'] ?? '';
    // Captura o CPF digitado (campo opcional)
    $cpf = $_POST['cpf'] ?? '';
    // Captura o e-mail digitado no formulário (campo obrigatório)
    $email = $_POST['email'] ?? '';
    // Captura a turma do aluno (campo opcional)
    $turma = $_POST['turma'] ?? '';
    // Captura a data de nascimento selecionada (campo opcional)
    $nasc = $_POST['nasc'] ?? '';
    // Captura o status ativo/inativo; por padrão assume 'true' (ativo)
    $ativo = $_POST['ativo'] ?? 'true';

    // Valida os campos obrigatórios: Nome e E-mail não podem estar vazios
    if (!empty($nome) && !empty($email)) {
        // Se $id existe, estamos editando um aluno já cadastrado
        if ($id) {
            // Chama a função de UPDATE passando todos os campos atualizados
            if (atualizarAluno($conexao, $id, $nome, $cpf, $email, $turma, $nasc, $ativo)) {
                // Atualização bem-sucedida: exibe mensagem verde
                $mensagem = '<div class="alert alert-success">Dados do aluno atualizados com sucesso!</div>';
                // Limpa o aluno em edição para o formulário voltar ao modo de cadastro
                $aluno_edicao = null;
            } else {
                // Erro ao atualizar no banco: exibe mensagem vermelha
                $mensagem = '<div class="alert alert-danger">Erro ao atualizar aluno.</div>';
            }
        } else {
            // Se $id é nulo, estamos cadastrando um aluno novo (INSERT)
            if (cadastrarAluno($conexao, $nome, $cpf, $email, $turma, $nasc, $ativo)) {
                // Cadastro bem-sucedido: exibe mensagem verde
                $mensagem = '<div class="alert alert-success">Novo aluno cadastrado com sucesso!</div>';
            } else {
                // Erro ao inserir no banco: exibe mensagem vermelha
                $mensagem = '<div class="alert alert-danger">Erro ao cadastrar aluno.</div>';
            }
        }
    } else {
        // Campos obrigatórios não preenchidos: exibe aviso amarelo ao usuário
        $mensagem = '<div class="alert alert-warning">Campos obrigatórios: Nome e E-mail.</div>';
    }
}

/* BUSCA FINAL DA LISTA DE ALUNOS */
// Recupera todos os alunos do banco para exibir na tabela abaixo do formulário
$alunos = listarAlunos($conexao);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <!-- Define que o arquivo usa codificação UTF-8 (suporte a acentos e caracteres especiais) -->
    <meta charset="UTF-8">
    <!-- Garante que a página se adapte corretamente a dispositivos móveis (responsividade) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título que aparece na aba do navegador -->
    <title>Gestão de Alunos - HighTech System</title>
    <!-- Importa o arquivo CSS principal do projeto com todos os estilos visuais -->
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <!-- Inclui o cabeçalho padrão do sistema (menu de navegação, logo, etc.) -->
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Área principal da página envolta pelo container para centralizar o conteúdo -->
    <main class="container">
        <!-- Título da página indicando que é uma área restrita ao administrador -->
        <h2>Gestão de Alunos (Área Restrita - Admin)</h2>
        <p>Gerencie o cadastro de estudantes do sistema.</p>

        <!-- Exibe a mensagem de feedback (sucesso, erro ou aviso) se existir -->
        <?php echo $mensagem; ?>

        <!-- 
             FORMULÁRIO DE CADASTRO / EDIÇÃO DE ALUNO
             O mesmo formulário serve para cadastrar e editar;
             o que muda é a presença do campo hidden "id"
        -->
        <div class="admin-card">
            <!-- Título do card muda dinamicamente: se $aluno_edicao existe, mostra "Editar", senão "Cadastrar" -->
            <h3><?php echo $aluno_edicao ? 'Editar Aluno (ID: ' . htmlspecialchars($aluno_edicao['id']) . ')' : 'Cadastrar Novo Aluno'; ?></h3>
            <br>
            <!-- O formulário envia os dados para alunos.php via POST -->
            <form action="alunos.php" method="post">
                <?php if ($aluno_edicao): ?>
                    <!-- Campo oculto que transmite o ID do aluno ao servidor no momento de salvar a edição -->
                    <!-- Sem ele, o sistema não saberia qual aluno atualizar no banco -->
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($aluno_edicao['id']); ?>">
                <?php endif; ?>

                <!-- Grid de campos do formulário para organizar em múltiplas colunas -->
                <div class="form-grid">
                    <!-- Campo: Nome Completo (obrigatório) -->
                    <div class="form-group">
                        <label for="nome">Nome Completo: *</label>
                        <!-- htmlspecialchars evita que caracteres especiais quebrem o HTML ou causem XSS -->
                        <!-- O operador ?? '' garante que o campo fique vazio no modo cadastro -->
                        <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($aluno_edicao['nome'] ?? ''); ?>" required>
                    </div>

                    <!-- Campo: CPF (opcional, mas útil para identificação única do aluno) -->
                    <div class="form-group">
                        <label for="cpf">CPF:</label>
                        <input type="text" name="cpf" id="cpf" value="<?php echo htmlspecialchars($aluno_edicao['cpf'] ?? ''); ?>">
                    </div>

                    <!-- Campo: E-mail (obrigatório — usado como contato e possível login) -->
                    <div class="form-group">
                        <label for="email">E-mail: *</label>
                        <!-- type="email" faz o navegador validar o formato do e-mail antes de enviar -->
                        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($aluno_edicao['email'] ?? ''); ?>" required>
                    </div>

                    <!-- Campo: Turma (opcional — identifica o grupo ou período do aluno) -->
                    <div class="form-group">
                        <label for="turma">Turma:</label>
                        <input type="text" name="turma" id="turma" value="<?php echo htmlspecialchars($aluno_edicao['turma'] ?? ''); ?>">
                    </div>

                    <!-- Campo: Data de Nascimento (opcional — útil para relatórios e faixa etária) -->
                    <div class="form-group">
                        <label for="nasc">Nascimento:</label>
                        <!-- type="date" exibe um seletor de data nativo do navegador -->
                        <input type="date" name="nasc" id="nasc" value="<?php echo htmlspecialchars($aluno_edicao['nascimento'] ?? ''); ?>">
                    </div>

                    <!-- Campo: Status Ativo (controla se o aluno aparece como ativo ou inativo) -->
                    <div class="form-group">
                        <label for="ativo">Status Ativo:</label>
                        <select name="ativo" id="ativo">
                            <?php
                            // Determina se o aluno está ativo verificando múltiplos formatos possíveis
                            // O PostgreSQL pode retornar booleano como: true (bool), 't' (string) ou 1 (int)
                            $isAtivo = isset($aluno_edicao['ativo']) ? ($aluno_edicao['ativo'] === true || $aluno_edicao['ativo'] === 't' || $aluno_edicao['ativo'] == 1) : true;
                            ?>
                            <!-- Se $isAtivo for true, a opção "Sim (Ativo)" vem pré-selecionada -->
                            <option value="true" <?php echo $isAtivo ? 'selected' : ''; ?>>Sim (Ativo)</option>
                            <!-- Se $isAtivo for false, a opção "Não (Inativo)" vem pré-selecionada -->
                            <option value="false" <?php echo !$isAtivo ? 'selected' : ''; ?>>Não (Inativo)</option>
                        </select>
                    </div>
                </div>

                <!-- Botão de envio: muda o texto conforme o modo (edição ou cadastro) -->
                <button type="submit" class="btn btn-primary"><?php echo $aluno_edicao ? 'Salvar Alterações' : 'Cadastrar Aluno'; ?></button>
                <?php if ($aluno_edicao): ?>
                    <!-- Botão "Cancelar" só aparece no modo edição; volta para a listagem limpa -->
                    <a href="alunos.php" class="btn btn-outline">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <!--
             TABELA DE LISTAGEM DE ALUNOS CADASTRADOS
             Exibe todos os alunos retornados pela função listarAlunos()
        -->
        <div class="admin-card">
            <h3>Lista de Alunos Cadastrados</h3>
            <?php if (empty($alunos)): ?>
                <!-- Mensagem amigável quando não há nenhum aluno no banco de dados -->
                <p>Nenhum aluno cadastrado no banco de dados.</p>
            <?php else: ?>
                <!-- Tabela estilizada com a classe CSS do projeto -->
                <table class="styled-table">
                    <thead>
                        <tr>
                            <!-- Cabeçalhos das colunas da tabela -->
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
                        <!-- Itera sobre cada aluno retornado pelo banco de dados -->
                        <?php foreach ($alunos as $aluno): ?>
                            <tr>
                                <!-- ID numérico do aluno no banco (chave primária) -->
                                <td><?php echo htmlspecialchars($aluno['id']); ?></td>
                                <!-- Nome em negrito para destaque visual -->
                                <td><strong><?php echo htmlspecialchars($aluno['nome']); ?></strong></td>
                                <!-- CPF: usa ?? '-' para exibir traço caso o CPF não tenha sido cadastrado -->
                                <td><?php echo htmlspecialchars($aluno['cpf'] ?? '-'); ?></td>
                                <!-- E-mail do aluno -->
                                <td><?php echo htmlspecialchars($aluno['email']); ?></td>
                                <!-- Turma: exibe traço se não cadastrada -->
                                <td><?php echo htmlspecialchars($aluno['turma'] ?? '-'); ?></td>
                                <!-- Data de nascimento: exibe traço se não cadastrada -->
                                <td><?php echo htmlspecialchars($aluno['nascimento'] ?? '-'); ?></td>
                                <td>
                                    <?php
                                    // Verifica o status ativo em múltiplos formatos (bool, string 't', inteiro 1)
                                    // pois diferentes bancos e drivers PHP retornam booleanos de formas distintas
                                    if ($aluno['ativo'] === true || $aluno['ativo'] === 't' || $aluno['ativo'] == 1): ?>
                                        <!-- Badge verde para aluno ativo -->
                                        <span class="badge" style="background-color: #D1FAE5; color: #065F46;">Ativo</span>
                                    <?php else: ?>
                                        <!-- Badge vermelho para aluno inativo -->
                                        <span class="badge" style="background-color: #FEE2E2; color: #991B1B;">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Link para editar: passa action=edit e o ID do aluno via GET -->
                                    <a href="alunos.php?action=edit&id=<?php echo $aluno['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Editar</a>
                                    <!-- Link para excluir: o onclick pede confirmação antes de enviar a requisição -->
                                    <!-- Isso evita exclusões acidentais por um clique sem querer -->
                                    <a href="alunos.php?action=delete&id=<?php echo $aluno['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este aluno?');" class="btn btn-danger" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Excluir</a>
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
