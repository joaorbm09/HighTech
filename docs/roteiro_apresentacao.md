# Roteiro de apresentação — HighTech

## Abertura

“A HighTech é uma aplicação acadêmica feita em PHP e PostgreSQL. Ela reúne duas frentes: serviços e oportunidades para empresas e uma plataforma de cursos para alunos. O projeto começou com a estrutura de um sistema simples de gestão escolar e foi ampliado para atender também a cursos, empresas e empregabilidade.”

## 1. O que a aplicação oferece

- **Portal institucional:** apresenta serviços de tecnologia para empresas e permite enviar solicitações.
- **HighTech School:** administra cursos, alunos e matrículas; alunos inscritos acessam aulas, acompanham o progresso, fazem provas e podem obter certificados.
- **Área de oportunidades:** publica vagas e apresenta perfis profissionais de alunos que optaram por divulgá-los.
- **Acesso por perfil:** alunos e administradores veem áreas diferentes; as ações administrativas exigem perfil de administrador.

## 2. Como a aplicação está organizada

- Os arquivos da raiz, como [portal_empresa.php](./portal_empresa.php), [aplicacao.php](./aplicacao.php), [vagas.php](./vagas.php) e [talentos.php](./talentos.php), compõem as páginas públicas e institucionais.
- A pasta [app/](./app/) contém as telas de gestão e de aprendizagem: cursos, alunos, matrículas, painel do aluno, aulas, provas e certificados.
- A pasta [login/](./login/) reúne cadastro, login e encerramento da sessão.
- [includes/functions.php](./includes/functions.php) centraliza consultas e operações de negócio reutilizadas pelas páginas.
- [includes/auth.php](./includes/auth.php) mantém as verificações de sessão e de perfil; [includes/header.php](./includes/header.php) e [includes/footer.php](./includes/footer.php) compartilham elementos visuais.
- [database/connect.php](./database/connect.php) cria a conexão PDO com o PostgreSQL. [database/schema.sql](./database/schema.sql) define as tabelas e os dados iniciais; [database/migrations/20261002_learning_flow.sql](./database/migrations/20261002_learning_flow.sql) acrescenta as tabelas do fluxo educacional a bancos existentes.
- [assets/style.css](./assets/style.css) contém o estilo compartilhado da interface.

## 3. Fluxo de aprendizagem

1. O aluno cria uma conta e o sistema associa a conta a um registro acadêmico.
2. O aluno entra no painel e solicita matrícula em um curso disponível.
3. Uma matrícula ativa dá acesso às aulas publicadas daquele curso.
4. A conclusão das aulas obrigatórias é registrada por matrícula.
5. Depois de concluir as aulas obrigatórias, o aluno pode fazer a prova publicada.
6. O servidor corrige as respostas; uma aprovação pode emitir um certificado com código de validação.

Administradores podem publicar ou ocultar aulas, configurar a nota mínima e as tentativas da prova, cadastrar questões e acompanhar as matrículas.

## 4. Principais recursos técnicos

- **PHP com PDO e PostgreSQL:** páginas PHP chamam funções de acesso a dados; instruções preparadas separam os valores recebidos do SQL.
- **Autenticação e autorização:** senhas são armazenadas como hashes; sessões identificam o usuário; as páginas administrativas verificam o perfil.
- **Proteção de formulários:** formulários de conclusão de aulas, provas e gestão de aulas/provas usam tokens CSRF. Saídas exibidas nas páginas são escapadas com `htmlspecialchars`.
- **Integridade dos dados:** chaves estrangeiras conectam cursos, matrículas, aulas, tentativas e certificados. Restrições no banco ajudam a impedir duplicações e dados inválidos.
- **Correção no servidor:** a prova não recebe o gabarito no navegador; o servidor valida as respostas, calcula a nota e controla tentativas e aprovação.
- **Certificação verificável:** o certificado pode ser impresso ou salvo como PDF e consultado pelo código público.
- **Interface compartilhada:** cabeçalho, navegação e estilos comuns mantêm as páginas coerentes.

## 5. O que foi acrescentado à ideia do mini_sistema

O [mini_sistema de referência](https://github.com/joaorbm09/senai-backend-php/blob/1a4ad4829755fe723a46303a03adbbdf79ac773d/mini_sistema/documentacao.md) é um ponto de partida para cadastro, consulta, atualização e remoção de alunos, autenticação e acesso ao PostgreSQL com PDO. A HighTech reaproveita essa base de PHP, banco relacional, funções compartilhadas, formulários e sessões, mas amplia a finalidade e os fluxos da aplicação:

- Sai de um CRUD centrado em alunos e passa a administrar **cursos, matrículas, empresas, solicitações de serviço, vagas e perfis de talentos**.
- Diferencia **aluno e administrador** e organiza páginas para visitantes, estudantes e gestão.
- Adiciona uma **área de aprendizagem** com aulas publicadas, progresso por matrícula, avaliações e emissão/validação de certificados.
- Introduz um **fluxo de avaliação**: questões de múltipla escolha, nota mínima, tentativas configuráveis e correção no servidor.
- Inclui módulos voltados ao relacionamento com empresas e à divulgação de oportunidades profissionais.
- Usa regras e restrições próprias para relacionar os novos dados e preservar o histórico de progresso e resultados.

## 6. Exemplos de código que se destacam em relação ao mini_sistema

No mini_sistema, o foco do código está em operações diretas de CRUD, por exemplo, inserir um aluno com uma consulta preparada ou listar registros com `fetchAll`. A HighTech mantém esse padrão de acesso ao banco, mas acrescenta trechos que protegem o acesso e coordenam fluxos com várias regras.

### Senhas armazenadas como hash

Em [cadastrarUsuario](./includes/functions.php#L10), a senha não é inserida diretamente no banco:

```php
$senha_hash = password_hash($senha, PASSWORD_BCRYPT);
$stmt = $conexao->prepare(
    "INSERT INTO usuarios (nome, email, senha, perfil)
     VALUES (:nome, :email, :senha, :perfil)"
);
```

`password_hash` produz um hash próprio para senhas; a aplicação salva esse resultado, não a senha original. No login, [autenticarUsuario](./includes/functions.php#L39) usa `password_verify` para comparar a senha informada com o hash guardado. Isso é diferente do módulo inicial de autenticação do mini_sistema, cuja função `cadastrar_user` grava diretamente o valor recebido para `senha`. A abordagem atual é uma proteção importante para as credenciais.

### Regras de acesso além de “estar logado”

Em [auth.php](./includes/auth.php#L35), `exigirLogin()` redireciona quem não tem uma sessão autenticada. `exigirAdmin()` primeiro exige login e depois verifica o perfil, bloqueando ações administrativas para contas comuns. Assim, o controle é feito no servidor, e não apenas escondendo links da navegação.

### Tokens CSRF nos formulários de ação

Na página de aulas, [aulas.php](./app/aulas.php#L45) confere o token recebido no formulário com o token guardado na sessão:

```php
if (!is_string($token_enviado) || $token_sessao === '' ||
    !hash_equals($token_sessao, $token_enviado)) {
    http_response_code(400);
}
```

O token é criado com `random_bytes` e validado com `hash_equals`. Isso dificulta que outro site induza um navegador já autenticado a registrar ações involuntárias. É uma proteção adicional aos formulários e operações que não aparece no CRUD básico descrito para o mini_sistema.

### Matrícula validada dentro de uma transação

A função [matricularAlunoEmCursoAtivo](./includes/functions.php#L390) não faz apenas um `INSERT`: começa uma transação, bloqueia a linha do curso enquanto verifica se ele está ativo, evita uma matrícula ativa duplicada e, ao final, confirma ou desfaz as mudanças:

```php
$conexao->beginTransaction();
$curso = $conexao->prepare(
    "SELECT ativo FROM cursos WHERE id = :id FOR UPDATE"
);
// A aplicação verifica o curso e a matrícula antes de inserir.
$conexao->commit();
```

`FOR UPDATE` ajuda a impedir que uma alteração concorrente torne o curso indisponível entre a verificação e a matrícula. Se a operação falhar, o código executa `rollBack`. Esse fluxo coordena regras de negócio e concorrência, em vez de somente gravar dados de formulário.

### Prova corrigida no servidor e certificado emitido no mesmo fluxo

A função [enviarTentativaProva](./includes/functions.php#L1114) valida a matrícula e a publicação da prova, verifica a conclusão das aulas obrigatórias, confere se cada resposta corresponde a uma alternativa existente, calcula a nota e limita as tentativas. O gabarito é consultado no banco e a correção ocorre no servidor — não se confia numa nota enviada pelo navegador.

Quando o aluno passa, a mesma operação cria um código de certificado e registra a tentativa. A transação agrupa essas gravações para que uma falha não deixe a aprovação e o certificado em estados inconsistentes. O código pode depois ser consultado na tela pública de [certificado.php](./app/certificado.php#L1).

### Conclusão de aula sem duplicar o progresso

A função [concluirAulaDaMatricula](./includes/functions.php#L1436) insere o progresso somente se a aula pertencer ao curso da matrícula ativa e estiver publicada. A cláusula `ON CONFLICT (id_matricula, id_aula) DO NOTHING` evita duplicar a conclusão se o aluno enviar o mesmo formulário novamente. A regra é apoiada por uma restrição única no banco para o par matrícula/aula.

### Como apresentar a diferença

“No mini_sistema, uma função como `cadastrar` recebe os campos e insere um aluno; funções de consulta e atualização completam o CRUD. Na HighTech, o CRUD continua existindo, mas aparecem regras que atravessam várias tabelas: validar perfil, proteger formulários, verificar matrícula ativa, controlar progresso e tentativas, corrigir a prova e emitir um certificado. Esses exemplos mostram como o projeto evoluiu de telas de cadastro para fluxos completos de aprendizagem e gestão.”

## 7. Fechamento sugerido

“A principal evolução foi transformar uma demonstração de operações CRUD em uma plataforma com diferentes perfis e processos conectados. O projeto reúne aprendizagem, gestão acadêmica e serviços corporativos. O banco de dados sustenta esses processos e o PHP aplica as regras de acesso, matrícula, progresso, prova e certificação.”

## 8. Ressalvas verificadas

- A sintaxe dos 22 arquivos PHP foi verificada com `php -l` no PHP 8.5.10, sem erros. Não há testes automatizados identificados nesta revisão.
- O teste de integração não foi executado: não havia cliente `psql` disponível nem uma conexão de banco confirmada para este ambiente. Portanto, a validação sintática não comprova, por si só, que todos os fluxos funcionem contra uma base real.
- No cadastro em [login/cadastrar.php](./login/cadastrar.php), o retorno da criação do registro em `alunos` não é verificado. Se a conta em `usuarios` for criada, mas a inclusão do aluno falhar, a página ainda redireciona como se o cadastro tivesse sido concluído. Vale corrigir esse caso e tornar as duas gravações atômicas antes de apresentar o cadastro como plenamente validado.
- Os materiais de cursos listados em [cursos_e_avaliacoes.md](./cursos_e_avaliacoes.md) são referências de estudo, não prova de que os conteúdos foram revisados por um professor. Recomenda-se revisão docente antes de usá-los como formação ou avaliação oficial.
