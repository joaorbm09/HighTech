<?php 
/* INICIALIZAÇÃO E CONTROLE DE ACESSO */
// Importa o arquivo de autenticação — ele verifica se o usuário está logado na sessão
require_once __DIR__ . '/../includes/auth.php';
// Garante que apenas administradores acessem esta página; redireciona caso contrário
exigirAdmin();

// Importa as funções auxiliares do sistema (listar, cadastrar, atualizar, excluir cursos)
require_once __DIR__ . '/../includes/functions.php';

/* VARIÁVEIS DE CONTROLE DA PÁGINA */
// Variável que armazenará mensagens de feedback para o usuário (sucesso, erro ou aviso)
$mensagem = '';
// Variável que guarda os dados do curso quando estamos no modo edição; null = modo cadastro
$curso_edicao = null;

/* BLOCO DE EXCLUSÃO DE CURSO */
// Verifica se a URL contém ?action=delete&id=X — indica que o usuário clicou em "Excluir"
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // Chama a função que executa o DELETE no banco de dados passando a conexão e o ID do curso
    if (excluirCurso($conexao, $_GET['id'])) {
        // Se a exclusão foi bem-sucedida, exibe mensagem verde de confirmação
        $mensagem = '<div class="alert alert-success">Curso excluído com sucesso!</div>';
    } else {
        // Se houve erro (ex: curso vinculado a matrículas), exibe mensagem vermelha de falha
        $mensagem = '<div class="alert alert-danger">Erro ao excluir curso.</div>';
    }
}

/* BLOCO DE CARREGAMENTO PARA EDIÇÃO */
// Verifica se a URL contém ?action=edit&id=X — indica que o usuário clicou em "Editar"
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    // Busca no banco de dados os dados completos do curso pelo ID recebido via GET
    // O resultado será usado para pré-preencher o formulário com os dados atuais
    $curso_edicao = buscarCursoPorId($conexao, $_GET['id']);
}

/* BLOCO DE SALVAMENTO E ATUALIZAÇÃO (FORMULÁRIO POST) */
// Verifica se o formulário foi enviado via POST (método padrão e seguro para envio de dados)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura o ID do curso (presente somente no modo edição; null no modo cadastro)
    $id = $_POST['id'] ?? null;
    // Captura o nome do curso digitado no formulário; se não enviado, usa string vazia
    $nome = $_POST['nome'] ?? '';
    // Captura a categoria selecionada no <select>
    $categoria = $_POST['categoria'] ?? '';
    // Captura a descrição detalhada do curso (campo opcional)
    $descricao = $_POST['descricao'] ?? '';
    // Captura a carga horária; padrão 0 garante que a validação falhe se não informado
    $carga_horaria = $_POST['carga_horaria'] ?? 0;
    // Captura o status ativo/inativo; por padrão assume 'true' (ativo)
    $ativo = $_POST['ativo'] ?? 'true';

    // Valida os campos obrigatórios: Nome, Categoria e Carga Horária maior que zero
    if (!empty($nome) && !empty($categoria) && $carga_horaria > 0) {
        // Se $id existe, estamos editando um curso já cadastrado
        if ($id) {
            // Chama a função de UPDATE passando todos os campos com os novos valores
            if (atualizarCurso($conexao, $id, $nome, $categoria, $descricao, $carga_horaria, $ativo)) {
                // Atualização bem-sucedida: exibe mensagem verde
                $mensagem = '<div class="alert alert-success">Curso atualizado com sucesso!</div>';
                // Limpa o curso em edição para o formulário voltar ao modo de cadastro
                $curso_edicao = null;
            } else {
                // Erro ao atualizar no banco: exibe mensagem vermelha
                $mensagem = '<div class="alert alert-danger">Erro ao atualizar curso.</div>';
            }
        } else {
            // Se $id é nulo, estamos cadastrando um curso novo (INSERT)
            if (cadastrarCurso($conexao, $nome, $categoria, $descricao, $carga_horaria, $ativo)) {
                // Cadastro bem-sucedido: exibe mensagem verde
                $mensagem = '<div class="alert alert-success">Novo curso cadastrado com sucesso!</div>';
            } else {
                // Erro ao inserir no banco: exibe mensagem vermelha
                $mensagem = '<div class="alert alert-danger">Erro ao cadastrar curso.</div>';
            }
        }
    } else {
        // Campos obrigatórios não preenchidos corretamente: exibe aviso amarelo
        $mensagem = '<div class="alert alert-warning">Preencha Nome, Categoria e Carga Horária válida.</div>';
    }
}

/* BUSCA FINAL DA LISTA DE CURSOS */
// Recupera todos os cursos do banco para exibir na tabela abaixo do formulário
$cursos = listarCursos($conexao);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Cursos - HighTech System</title>
    <!-- Importa o arquivo CSS principal do projeto com todos os estilos visuais -->
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <!-- Inclui o cabeçalho padrão do sistema (menu de navegação, logo, etc.) -->
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Área principal da página envolta pelo container para centralizar o conteúdo -->
    <main class="container">
        <!-- Título da página indicando que é uma área restrita ao administrador -->
        <h2>Gestão de Cursos (Área Restrita - Admin)</h2>
        <p>Gerencie o catálogo de cursos oferecidos pela HighTech School.</p>

        <!-- Exibe a mensagem de feedback (sucesso, erro ou aviso) se existir -->
        <?php echo $mensagem; ?>

        <!--
            FORMULÁRIO DE CADASTRO / EDIÇÃO DE CURSO
             O mesmo formulário serve para cadastrar e editar;
             o que muda é a presença do campo hidden "id" 
        -->
        <div class="admin-card">
            <!-- Título do card muda dinamicamente: se $curso_edicao existe, mostra "Editar", senão "Cadastrar" -->
            <h3><?php echo $curso_edicao ? 'Editar Curso (ID: ' . htmlspecialchars($curso_edicao['id']) . ')' : 'Cadastrar Novo Curso'; ?></h3>
            <br>
            <!-- O formulário envia os dados para cursos.php via POST -->
            <form action="cursos.php" method="post">
                <?php if ($curso_edicao): ?>
                    <!-- Campo oculto que transmite o ID do curso ao servidor no momento de salvar a edição -->
                    <!-- Sem ele, o sistema não saberia qual curso atualizar no banco -->
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($curso_edicao['id']); ?>">
                <?php endif; ?>

                <!-- Grid de campos do formulário para organizar em múltiplas colunas -->
                <div class="form-grid">
                    <!-- Campo: Nome do Curso (obrigatório — identifica o curso no sistema) -->
                    <div class="form-group">
                        <label for="nome">Nome do Curso: *</label>
                        <!-- htmlspecialchars evita que caracteres especiais quebrem o HTML ou causem XSS -->
                        <input type="text" name="nome" id="nome" value="<?php echo htmlspecialchars($curso_edicao['nome'] ?? ''); ?>" required>
                    </div>

                    <!-- Campo: Categoria do Curso (obrigatório — agrupa cursos por área de conhecimento) -->
                    <div class="form-group">
                        <label for="categoria">Categoria: *</label>
                        <!-- Select com as categorias disponíveis no catálogo da HighTech -->
                        <select name="categoria" id="categoria" required>
                            <?php
                            // Captura a categoria atual para pré-selecionar a opção correta no modo edição
                            $cat = $curso_edicao['categoria'] ?? '';
                            ?>
                            <!-- Cada opção compara a categoria salva no banco com o valor da opção -->
                            <!-- Se baterem, adiciona 'selected' para marcar como selecionada -->
                            <option value="Desenvolvimento" <?php echo ($cat === 'Desenvolvimento') ? 'selected' : ''; ?>>Desenvolvimento</option>
                            <option value="Gestão & Ágil" <?php echo ($cat === 'Gestão & Ágil') ? 'selected' : ''; ?>>Gestão & Ágil</option>
                            <option value="Inovação & PMEs" <?php echo ($cat === 'Inovação & PMEs') ? 'selected' : ''; ?>>Inovação & PMEs</option>
                            <option value="Infraestrutura & BD" <?php echo ($cat === 'Infraestrutura & BD') ? 'selected' : ''; ?>>Infraestrutura & BD</option>
                        </select>
                    </div>

                    <!-- Campo: Carga Horária (obrigatório — define a duração do curso em horas) -->
                    <div class="form-group">
                        <label for="carga_horaria">Carga Horária (Horas): *</label>
                        <!-- type="number" e min="1" garantem que apenas valores positivos sejam aceitos -->
                        <input type="number" name="carga_horaria" id="carga_horaria" value="<?php echo htmlspecialchars($curso_edicao['carga_horaria'] ?? 40); ?>" required min="1">
                    </div>

                    <!-- Campo: Status do Curso (controla se o curso aparece como ativo ou inativo) -->
                    <div class="form-group">
                        <label for="ativo">Status:</label>
                        <select name="ativo" id="ativo">
                            <?php
                            // Determina se o curso está ativo verificando múltiplos formatos possíveis
                            // O PostgreSQL pode retornar booleano como: true (bool), 't' (string) ou 1 (int)
                            $isAtivo = isset($curso_edicao['ativo']) ? ($curso_edicao['ativo'] === true || $curso_edicao['ativo'] === 't' || $curso_edicao['ativo'] == 1) : true;
                            ?>
                            <!-- Se $isAtivo for true, a opção "Ativo" vem pré-selecionada -->
                            <option value="true" <?php echo $isAtivo ? 'selected' : ''; ?>>Ativo</option>
                            <!-- Se $isAtivo for false, a opção "Inativo" vem pré-selecionada -->
                            <option value="false" <?php echo !$isAtivo ? 'selected' : ''; ?>>Inativo</option>
                        </select>
                    </div>

                    <!-- Campo: Descrição Detalhada (ocupa 2 colunas do grid para ter mais espaço) -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="descricao">Descrição Detalhada do Curso:</label>
                        <!-- textarea permite texto longo com múltiplas linhas para descrever o curso -->
                        <textarea name="descricao" id="descricao" rows="3"><?php echo htmlspecialchars($curso_edicao['descricao'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Botão de envio: muda o texto conforme o modo (edição ou cadastro) -->
                <button type="submit" class="btn btn-primary"><?php echo $curso_edicao ? 'Salvar Alterações' : 'Cadastrar Curso'; ?></button>
                <?php if ($curso_edicao): ?>
                    <!-- Botão "Cancelar" só aparece no modo edição; volta para a listagem limpa -->
                    <a href="cursos.php" class="btn btn-outline">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- 
             TABELA DO CATÁLOGO DE CURSOS CADASTRADOS
             Exibe todos os cursos retornados pela função listarCursos()
         -->
        <div class="admin-card">
            <h3>Catálogo de Cursos no Banco de Dados</h3>
            <?php if (empty($cursos)): ?>
                <!-- Mensagem amigável quando não há nenhum curso cadastrado no banco -->
                <p>Nenhum curso cadastrado.</p>
            <?php else: ?>
                <!-- Tabela estilizada com a classe CSS do projeto -->
                <table class="styled-table">
                    <thead>
                        <tr>
                            <!-- Cabeçalhos das colunas da tabela -->
                            <th>ID</th>
                            <th>Curso</th>
                            <th>Categoria</th>
                            <th>Carga Horária</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Itera sobre cada curso retornado pelo banco de dados -->
                        <?php foreach ($cursos as $curso): ?>
                            <tr>
                                <!-- ID numérico do curso no banco (chave primária) -->
                                <td><?php echo htmlspecialchars($curso['id']); ?></td>
                                <td>
                                    <!-- Nome em negrito para destaque visual do título do curso -->
                                    <strong><?php echo htmlspecialchars($curso['nome']); ?></strong><br>
                                    <!-- Descrição exibida em tamanho menor e cor suavizada abaixo do nome -->
                                    <small style="color: var(--text-muted);"><?php echo htmlspecialchars($curso['descricao']); ?></small>
                                </td>
                                <!-- Categoria exibida como badge para destaque visual -->
                                <td><span class="badge"><?php echo htmlspecialchars($curso['categoria']); ?></span></td>
                                <!-- Carga horária com o sufixo 'h' (horas) para clareza -->
                                <td><?php echo htmlspecialchars($curso['carga_horaria']); ?>h</td>
                                <td>
                                    <?php
                                    // Verifica o status ativo em múltiplos formatos (bool, string 't', inteiro 1)
                                    // pois diferentes bancos e drivers PHP retornam booleanos de formas distintas
                                    if ($curso['ativo'] === true || $curso['ativo'] === 't' || $curso['ativo'] == 1): ?>
                                        <!-- Badge verde para curso ativo -->
                                        <span class="badge" style="background-color: #D1FAE5; color: #065F46;">Ativo</span>
                                    <?php else: ?>
                                        <!-- Badge vermelho para curso inativo -->
                                        <span class="badge" style="background-color: #FEE2E2; color: #991B1B;">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Link para editar: passa action=edit e o ID do curso via GET -->
                                    <a href="cursos.php?action=edit&id=<?php echo $curso['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.6rem; font-size: 0.85rem;">Editar</a>
                                    <!-- Link para excluir: o onclick pede confirmação antes de enviar a requisição -->
                                    <!-- Isso evita exclusões acidentais de cursos que podem ter matrículas vinculadas -->
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
