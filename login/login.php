<?php 
/* INICIALIZAÇÃO — Carregamento das dependências do sistema */

// Importa o arquivo de autenticação: contém as funções usuarioLogado(), obterUsuarioLogado() e autenticarUsuario()
// O __DIR__ garante que o caminho seja sempre relativo à pasta atual, independente de onde o script for chamado
require_once __DIR__ . '/../includes/auth.php';

// Importa funções auxiliares gerais do sistema (helpers reutilizáveis)
// Esse arquivo também inicializa a variável $conexao com o banco de dados PostgreSQL
require_once __DIR__ . '/../includes/functions.php';

/* VERIFICAÇÃO DE SESSÃO ATIVA — Evita que usuário já logado veja a tela de login novamente */

// Se o usuário já tiver uma sessão ativa, não faz sentido mostrar o login — redireciona direto para o sistema
if (usuarioLogado()) {
    $user = obterUsuarioLogado(); // Recupera os dados do usuário logado da sessão atual

    // Verifica o perfil para decidir para qual página redirecionar:
    // Administradores vão para o painel de alunos; demais usuários vão para a aplicação geral
    if ($user && $user['perfil'] === 'admin') {
        header("Location: ../app/alunos.php"); // Redireciona admin para o painel de gestão
    } else {
        header("Location: ../aplicacao.php"); // Redireciona aluno/usuário comum para a área de conteúdo
    }
    exit; // Encerra o script imediatamente após o redirecionamento para não renderizar HTML desnecessário
}

/* VARIÁVEIS DE CONTROLE — Inicializadas vazias para evitar erros de variável indefinida */

$mensagem = ''; // Armazena mensagens de feedback visual (erros, sucessos, alertas) para o usuário
$email = '';    // Armazena o e-mail digitado para reexibir no campo após um erro (evita o usuário redigitar)

/* MENSAGEM DE SUCESSO — Exibida quando o usuário chegou aqui após se cadastrar com sucesso */

// O parâmetro ?sucesso=cadastrado é passado na URL pela página cadastrar.php após um cadastro bem-sucedido
// Isso evita mostrar a mensagem em qualquer outro acesso à página de login
if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'cadastrado') {
    $mensagem = '<div class="alert alert-success"> Cadastro realizado com sucesso! Faça seu login abaixo.</div>';
}

/* VERIFICAÇÃO DE CONEXÃO COM O BANCO — Antes de qualquer operação, confirma que o banco está acessível */

// Se a variável $conexao for falsa (null, false ou recurso inválido), avisa o usuário sobre o problema
// Isso protege o sistema de tentar executar queries sem conexão, o que causaria erros PHP fatais
if (!$conexao) {
    $mensagem = '<div class="alert alert-danger"> Erro de conexão com o banco de dados PostgreSQL. Verifique as configurações em database/connect.php.</div>';
}

/* PROCESSAMENTO DO FORMULÁRIO — Executado apenas quando o formulário de login é enviado via POST */

// REQUEST_METHOD === 'POST' garante que este bloco só rode quando o formulário for submetido
// (evita processamento em acessos normais GET à página)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Captura e sanitiza o e-mail: trim() remove espaços em branco no início e no fim
    // O operador ?? '' garante que, se o campo não existir no POST, use string vazia (evita erro de índice)
    $email = trim($_POST['email'] ?? '');

    // Captura a senha sem trim() intencional — senhas podem ter espaços propositalmente nos extremos
    $senha = $_POST['senha'] ?? '';

    /* VALIDAÇÃO DOS CAMPOS — Verificações em cascata, da mais simples à mais complexa */

    if (empty($email) || empty($senha)) {
        // Se algum campo obrigatório estiver vazio, exibe aviso e não tenta acessar o banco
        $mensagem = '<div class="alert alert-warning">Por favor, preencha o E-mail e a Senha.</div>';

    } else if (!$conexao) {
        // Segunda barreira: verifica novamente a conexão antes de consultar o banco
        // (pode ter caído entre o carregamento da página e o envio do formulário)
        $mensagem = '<div class="alert alert-danger">Não foi possível processar o login: sem conexão com o banco de dados.</div>';

    } else {
        // Campos preenchidos e banco acessível: tenta autenticar o usuário
        // autenticarUsuario() busca o usuário no banco e verifica a senha com password_verify()
        $usuario = autenticarUsuario($conexao, $email, $senha);

        if ($usuario) {
            /* CRIAÇÃO DA SESSÃO — Armazena os dados do usuário autenticado na sessão PHP */

            // Salva o ID único do usuário na sessão para identificá-lo em todas as próximas requisições
            $_SESSION['user_id'] = $usuario['id'];

            // Salva o nome completo para exibição personalizada nas telas do sistema
            $_SESSION['user_nome'] = $usuario['nome'];

            // Salva o e-mail para referência e possíveis validações futuras
            $_SESSION['user_email'] = $usuario['email'];

            // Salva o perfil (ex: 'admin', 'aluno') para controle de acesso em todo o sistema
            $_SESSION['user_perfil'] = $usuario['perfil'];

            // Redireciona conforme o perfil: admin vai ao painel; demais usuários vão à aplicação
            if ($usuario['perfil'] === 'admin') {
                header("Location: ../app/alunos.php"); // Área administrativa
            } else {
                header("Location: ../aplicacao.php"); // Área do aluno
            }
            exit; // Encerra o script após redirecionar para não executar código HTML abaixo

        } else {
            // Autenticação falhou: e-mail não encontrado ou senha incorreta
            // A mensagem é propositalmente genérica para não revelar qual dado está errado (segurança)
            $mensagem = '<div class="alert alert-danger">E-mail ou senha incorretos! Verifique suas credenciais.</div>';
        }
    }
}
?>

<!--  INÍCIO DO HTML  -->
<!DOCTYPE html>
<!-- Define o tipo do documento como HTML5 e o idioma como português brasileiro -->
<html lang="pt-br">
<head>
    <!-- Define a codificação de caracteres como UTF-8 para suportar acentos e caracteres especiais -->
    <meta charset="UTF-8">
    <!-- Garante que a página seja responsiva em dispositivos móveis (ajusta a largura à tela do aparelho) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Título da aba do navegador: identifica a página dentro do sistema HighTech -->
    <title>Login - HighTech Sistema</title>
    <!-- Importa a folha de estilos global do projeto, que define cores, fontes, botões e cards -->
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <!-- Inclui o cabeçalho padrão do site (logo, menu de navegação) reutilizado em todas as páginas -->
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- Área principal de conteúdo da página -->
    <main class="container">

        <!-- Card centralizado com largura máxima de 450px para manter o formulário compacto e elegante -->
        <div class="admin-card" style="max-width: 450px; margin: 2rem auto;">

            <!-- Título principal do formulário, centralizado -->
            <h2 style="text-align: center; margin-bottom: 0.5rem;">Acesse sua Conta </h2>

            <!-- Subtítulo explicativo para orientar o usuário sobre o que preencher -->
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 1.5rem;">Entre com seu e-mail e senha cadastrados</p>

            <!-- Exibe a mensagem de feedback (erro, sucesso ou aviso) gerada pelo PHP acima -->
            <!-- O echo renderiza o HTML completo da div de alerta, incluindo classes e ícones -->
            <?php echo $mensagem; ?>

            <!-- Formulário de login: envia os dados via POST para este mesmo arquivo (login.php) -->
            <!-- O método POST é usado porque dados sensíveis (senha) não devem aparecer na URL -->
            <form action="login.php" method="post">

                <!-- Campo de e-mail: o type="email" valida o formato antes mesmo de enviar ao servidor -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="email">E-mail: *</label>
                    <!-- htmlspecialchars() previne XSS: converte caracteres especiais em entidades HTML seguras -->
                    <!-- O value reexibe o e-mail digitado em caso de erro, para o usuário não precisar redigitar -->
                    <input type="email" name="email" id="email" required placeholder="seuemail@hightech.com" value="<?php echo htmlspecialchars($email); ?>">
                </div>

                <!-- Campo de senha: type="password" oculta os caracteres digitados na tela -->
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="senha">Senha: *</label>
                    <!-- A senha não é reexibida por questão de segurança — o usuário deve redigitá-la em caso de erro -->
                    <input type="password" name="senha" id="senha" required placeholder="Digite sua senha">
                </div>

                <!-- Botão de envio do formulário: ocupa toda a largura do card para facilitar o clique -->
                <button type="submit" class="btn btn-primary" style="width: 100%;">Entrar no Sistema</button>
            </form>

            <!-- Separador visual entre o formulário e o link de cadastro -->
            <hr style="display: block; margin: 1.5rem 0; border: none; border-top: 1px solid var(--border-color);">

            <!-- Link alternativo para usuários que ainda não possuem conta no sistema -->
            <p style="text-align: center; font-size: 0.95rem;">
                Ainda não possui uma conta? <a href="cadastrar.php" style="color: var(--primary); font-weight: 600;">Cadastre-se aqui</a>
            </p>
        </div>
    </main>

    <!-- Inclui o rodapé padrão do site (informações da empresa, links úteis) -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
