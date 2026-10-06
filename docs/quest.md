Sim. Fiz uma auditoria focada no que realmente pode aparecer numa banca: **fluxo do sistema, PHP, banco, autenticação, autorização, segurança, SQL, transações, provas, certificados e inconsistências entre o roteiro e o código**. Ignorei `cursos_e_avaliacoes.md`, como você pediu.

## 🔴 Antes de tudo: os pontos que eu mais estudaria

Se o professor quiser apertar você, eu apostaria principalmente nestes:

1. **Como funciona o login?**
2. **Por que usar `password_hash()` e `password_verify()`?**
3. **Qual a diferença entre autenticação e autorização?**
4. **Como o sistema sabe que uma pessoa é administrador?**
5. **O que é PDO e por que vocês usam `prepare()`?**
6. **Como vocês evitam SQL Injection?**
7. **O que é CSRF e como vocês implementaram?**
8. **Por que usar `beginTransaction()`, `commit()` e `rollBack()`?**
9. **Como funciona a matrícula entre aluno e curso?**
10. **Por que `matriculas` é uma tabela separada?**
11. **Como o sistema impede o aluno de marcar uma aula de outro curso como concluída?**
12. **Como a prova é corrigida?**
13. **Por que o gabarito não vai para o navegador?**
14. **Como vocês controlam o número máximo de tentativas?**
15. **Como o certificado é gerado e validado?**
16. **O que são chaves estrangeiras e `ON DELETE CASCADE`?**
17. **Qual é a principal falha que vocês encontraram no próprio código?**
18. **O que vocês melhorariam se tivessem mais tempo?**

Essa última é **muito importante**, porque você já tem uma resposta real: o cadastro de usuário/aluno não é atômico.

---

# 🟢 1. Perguntas mais prováveis — funcionamento geral

### 1. "O que exatamente vocês fizeram nesse projeto?"

**O professor quer saber:** se você entende o projeto como um todo.

> "A HighTech começou como um sistema de gerenciamento de alunos e foi evoluindo. Nós mantivemos a estrutura de PHP, PostgreSQL e CRUD, mas acrescentamos cursos, matrículas, aulas, progresso, provas, certificados, usuários com diferentes perfis, vagas e banco de talentos."

---

### 2. "Como funciona o sistema desde o cadastro até o certificado?"

Essa provavelmente é uma das **mais importantes**.

Você pode explicar assim:

> "Primeiro o aluno cria uma conta. Essa conta fica na tabela `usuarios` e também existe um registro acadêmico na tabela `alunos`. Depois ele pode se matricular em um curso. A matrícula relaciona o aluno ao curso. Com uma matrícula ativa, ele acessa as aulas, e o sistema registra quais aulas obrigatórias foram concluídas. Depois de concluir essas aulas, ele pode fazer a prova. O servidor corrige as respostas, calcula a nota e, se atingir a nota mínima, gera um certificado com um código único."

Se você conseguir explicar isso sem olhar o roteiro, já demonstra bastante domínio.

---

### 3. "Por que vocês separaram o projeto em várias pastas?"

Você precisa saber:

- `login/` → autenticação
- `app/` → funcionalidades internas
- `includes/` → funções compartilhadas
- `database/` → conexão e estrutura do banco
- `assets/` → CSS
- arquivos da raiz → páginas públicas/institucionais

**Resposta:**

> "Para separar responsabilidades. Assim, a autenticação não fica misturada com as funções de banco, e as páginas conseguem reutilizar funções comuns."

---

# 🟢 2. PHP e banco de dados

### 4. "O que é PDO?"

> "PDO é uma interface do PHP para trabalhar com bancos de dados. No nosso caso usamos PDO para conectar ao PostgreSQL, executar consultas e trabalhar com parâmetros."

---

### 5. "Por que vocês usam `prepare()`?"

Exemplo:

```php
$stmt = $conexao->prepare(
    "SELECT * FROM usuarios WHERE LOWER(email) = LOWER(:email)"
);
```

Resposta:

> "Porque os valores enviados pelo usuário ficam separados do comando SQL. Isso ajuda a evitar SQL Injection."

---

### 6. "O que é SQL Injection?"

> "É quando alguém tenta colocar comandos SQL dentro de uma entrada do sistema para alterar ou consultar o banco de maneira indevida. As consultas preparadas reduzem esse risco porque os valores são tratados como dados."

---

### 7. "Todo SQL do projeto usa `prepare()`?"

Aqui cuidado.

Nem toda consulta precisa de `prepare()`. Existem consultas estáticas como:

```php
$conexao->query("SELECT * FROM cursos ORDER BY id ASC");
```

Isso não é automaticamente um problema porque não existe entrada do usuário sendo concatenada.

Se o professor perguntar:

> "Por que aqui vocês usaram `query()`?"

Resposta:

> "Porque essa consulta é fixa e não recebe valores externos. Quando existe um valor vindo do usuário ou variável que precisa entrar no SQL, usamos parâmetros preparados."

---

# 🟢 3. Login e segurança

## 8. "Como a senha é armazenada?"

Essa é **muito provável**.

No código:

```php
$senha_hash = password_hash($senha, PASSWORD_BCRYPT);
```

Você deve dizer:

> "A senha original não é armazenada. O PHP transforma a senha em um hash usando `password_hash()` e o banco guarda esse resultado."

---

### 9. "E como vocês verificam a senha no login?"

No login:

```php
password_verify($senha, $usuario['senha'])
```

Resposta:

> "O usuário digita a senha normal. O `password_verify()` compara essa senha com o hash armazenado no banco. Se corresponder, a autenticação é aceita."

### Pegadinha:

**Não diga que vocês descriptografam a senha.**

Hash não é criptografia reversível.

Se ele perguntar:

> "Vocês conseguem descobrir a senha original pelo banco?"

Resposta:

> "Não. A senha não é armazenada de forma reversível; o sistema verifica se a senha informada corresponde ao hash."

---

# 🟢 4. Autenticação x autorização

### 10. "Qual a diferença entre autenticação e autorização?"

Essa é clássica.

**Autenticação:**

> "Verifica quem é o usuário."

**Autorização:**

> "Verifica o que aquele usuário pode fazer."

No projeto:

```php
exigirLogin();
```

→ autenticação.

```php
exigirAdmin();
```

→ autorização.

---

### 11. "Como vocês sabem se o usuário é administrador?"

O login coloca informações na sessão:

```php
$_SESSION['user_id']
$_SESSION['user_nome']
$_SESSION['user_email']
$_SESSION['user_perfil']
```

Depois:

```php
if ($usuario['perfil'] !== 'admin') {
    ...
}
```

Resposta:

> "Depois do login, o perfil fica associado à sessão. Nas páginas administrativas, o servidor verifica se o perfil é `admin`. Não depende apenas de esconder o botão na interface."

---

### 12. "Se eu esconder o botão de administrador pelo HTML, isso é segurança?"

**Não.**

Resposta:

> "Não. O usuário poderia acessar a URL diretamente. Por isso a validação precisa ser feita no servidor com `exigirAdmin()`."

Essa resposta é ótima para impressionar.

---

# 🔴 5. Uma falha importante: sessão

Durante a auditoria encontrei um ponto que pode aparecer se o professor fizer uma pergunta de segurança mais avançada.

O `auth.php` faz:

```php
session_start();
```

mas não encontrei `session_regenerate_id(true)` após o login.

Isso significa que existe uma **melhoria possível contra session fixation**.

Se o professor perguntar:

### "O que você melhoraria na autenticação?"

Você pode responder:

> "Uma melhoria seria regenerar o ID da sessão depois de um login bem-sucedido com `session_regenerate_id(true)`. Isso ajuda a evitar ataques de fixation de sessão."

Não precisa dizer que o sistema está "inseguro" por causa disso. É melhor falar em **melhoria de segurança**.

---

# 🔴 6. CSRF

Essa é uma parte que provavelmente pode aparecer porque o código realmente implementa isso.

Exemplo:

```php
$_SESSION['csrf_aulas'] = bin2hex(random_bytes(32));
```

E depois:

```php
hash_equals($token_sessao, $token_enviado)
```

### 13. "O que é CSRF?"

> "É um tipo de ataque em que um site externo tenta induzir o navegador de um usuário já autenticado a realizar uma ação sem ele perceber."

### 14. "Como vocês evitam?"

> "Geramos um token aleatório, guardamos na sessão e colocamos esse token no formulário. Quando o formulário é enviado, o servidor compara o token recebido com o token da sessão."

### 15. "Por que `hash_equals()`?"

> "Para fazer uma comparação segura dos tokens, evitando problemas de comparação que poderiam ser explorados em determinados ataques."

---

# 🔴 7. Mas encontrei uma inconsistência importante no CSRF

Isso é algo que **eu corrigiria antes da apresentação**, se vocês ainda puderem mexer.

Em algumas telas administrativas, o CSRF está sendo usado para determinadas operações, principalmente exclusões.

Por exemplo, em `cursos.php`, a exclusão possui token:

```php
<input type="hidden" name="csrf_token" ...>
```

e verifica:

```php
hash_equals(...)
```

Porém, o cadastro/atualização do curso é processado por POST **sem a mesma validação CSRF**.

O mesmo problema aparece na criação de matrícula em `app/matriculas.php`: o cancelamento possui CSRF, mas o formulário de criação não faz a validação equivalente.

### Se o professor perguntar:

> "Todos os formulários POST possuem CSRF?"

**Não diga que sim.**

Melhor resposta:

> "Não todos. Durante a auditoria encontramos algumas operações administrativas que já têm proteção CSRF, principalmente exclusões, mas cadastro e atualização em algumas telas ainda precisam receber a mesma proteção. É uma melhoria que identificamos."

Isso é muito melhor do que o professor descobrir sozinho.

---

# 🟡 8. Transações

### 16. "O que é uma transação?"

Você precisa saber:

```php
beginTransaction()
```

→ começa.

```php
commit()
```

→ confirma.

```php
rollBack()
```

→ desfaz.

Resposta:

> "Uma transação agrupa várias operações do banco para que elas sejam tratadas como uma operação única. Se alguma etapa falhar, podemos desfazer as alterações."

---

### 17. "Onde vocês usam isso?"

Principalmente:

- matrícula
- envio/correção da prova
- emissão do certificado

---

### 18. "Por que usar transação na prova?"

Porque existe uma sequência:

1. verificar matrícula;
2. verificar aulas;
3. validar respostas;
4. calcular nota;
5. registrar tentativa;
6. se aprovado, criar certificado.

Se o registro da tentativa funcionar e a emissão do certificado falhar, sem transação poderia haver um estado inconsistente.

Com transação:

```text
BEGIN
   ↓
valida
   ↓
calcula
   ↓
salva tentativa
   ↓
gera certificado
   ↓
COMMIT
```

ou:

```text
ROLLBACK
```

---

# 🟡 9. `FOR UPDATE`

Esse é um ponto mais avançado.

Na matrícula:

```sql
SELECT ativo
FROM cursos
WHERE id = :id
FOR UPDATE
```

### "O que significa `FOR UPDATE`?"

> "Ele bloqueia aquela linha durante a transação para evitar que outra operação altere aquele registro enquanto estamos fazendo a verificação e a matrícula."

Isso mostra que você entende **concorrência**, não apenas CRUD.

---

# 🟢 10. Modelagem do banco

### 19. "Por que existe a tabela `matriculas`?"

Porque aluno e curso formam um relacionamento **N:N**.

Um aluno pode fazer vários cursos.

Um curso pode possuir vários alunos.

Então:

```text
ALUNO
  │
  │ 1:N
  ↓
MATRÍCULA
  ↑
  │ N:1
  │
CURSO
```

---

### 20. "Por que não colocar `id_curso` dentro de `alunos`?"

Porque isso permitiria associar facilmente um aluno a apenas um curso.

Com `matriculas`, temos:

```text
Aluno 1 → Curso A
Aluno 1 → Curso B
Aluno 1 → Curso C
```

e:

```text
Curso A → Aluno 1
Curso A → Aluno 2
Curso A → Aluno 3
```

---

# 🟢 11. Chaves estrangeiras

### 21. "O que é uma Foreign Key?"

Exemplo:

```sql
id_aluno INT REFERENCES alunos(id)
```

Resposta:

> "É uma chave estrangeira que cria uma relação entre tabelas e ajuda a impedir que uma matrícula aponte para um aluno inexistente."

---

### 22. "O que é `ON DELETE CASCADE`?"

Exemplo:

```sql
REFERENCES alunos(id) ON DELETE CASCADE
```

> "Se o aluno for excluído, os registros dependentes, como matrículas relacionadas, também podem ser removidos automaticamente."

### Pegadinha:

O professor pode perguntar:

> "Isso pode ser perigoso?"

Sim.

> "Pode. Se uma exclusão for feita por engano, os dados relacionados também podem ser removidos. Por isso é importante controlar quem pode excluir e proteger essas operações."

---

# 🟡 12. Progresso das aulas

### 23. "Como vocês impedem um aluno de concluir uma aula de outro curso?"

A função:

```php
concluirAulaDaMatricula()
```

faz uma relação entre:

```text
matriculas
      ↓
   id_curso
      ↓
    aulas
```

A consulta exige:

```sql
a.id_curso = m.id_curso
```

Então a aula precisa pertencer ao curso daquela matrícula.

---

### 24. "Por que existe `ON CONFLICT`?"

Código:

```sql
ON CONFLICT (id_matricula, id_aula) DO NOTHING
```

E o banco possui:

```sql
UNIQUE (id_matricula, id_aula)
```

Resposta:

> "Cada combinação de matrícula e aula só pode existir uma vez. Se o aluno enviar o formulário novamente, o banco não cria outro registro."

---

# 🔴 13. Prova

Aqui o professor pode fazer várias perguntas.

### 25. "Onde a prova é corrigida?"

**No servidor.**

O código busca o gabarito no banco:

```sql
a.correta
```

e compara com a resposta enviada.

---

### 26. "Por que não corrigir com JavaScript?"

Resposta:

> "Porque o JavaScript roda no navegador e poderia ser manipulado pelo usuário. A correção precisa acontecer no servidor para que o aluno não consiga alterar o resultado."

Essa é uma excelente resposta.

---

### 27. "O gabarito é enviado para o navegador?"

Não.

A função:

```php
listarQuestoesProvaAluno()
```

seleciona:

```sql
a.id
a.texto
```

mas não envia:

```sql
a.correta
```

Ou seja:

```text
NAVEGADOR
   ↓
pergunta + alternativas

SERVIDOR
   ↓
gabarito
```

---

### 28. "Como vocês calculam a nota?"

O código conta os acertos:

```php
$acertos++;
```

e depois:

```php
$nota = round(($acertos / count($gabarito)) * 100, 2);
```

Então:

> "A nota é calculada pela quantidade de respostas corretas dividida pela quantidade total de questões, multiplicada por 100."

---

### 29. "Como vocês sabem se o aluno passou?"

```php
$aprovada = $nota >= (float) $contexto['nota_minima'];
```

Então:

> "A prova possui uma nota mínima configurável no banco. O servidor compara a nota obtida com essa nota mínima."

---

# 🟡 14. Limite de tentativas

### 30. "Como vocês impedem infinitas tentativas?"

O sistema consulta:

```sql
SELECT COUNT(*)
FROM tentativas_prova
WHERE id_matricula = :id_matricula
AND id_prova = :id_prova
```

e compara com:

```php
$contexto['max_tentativas']
```

Se atingir:

```php
return ['status' => 'attempts_exhausted'];
```

---

### 31. "O aluno pode simplesmente mandar uma nota pelo navegador?"

Não.

> "Não. A nota não é recebida como um valor confiável do navegador. O servidor recebe as respostas, busca o gabarito e calcula a nota."

---

# 🔴 15. Certificado

### 32. "Como o certificado é criado?"

Quando:

```php
$aprovada === true
```

o sistema gera um código e grava:

```sql
INSERT INTO certificados
```

O código é único no banco:

```sql
codigo UUID NOT NULL UNIQUE
```

---

### 33. "Por que usar um código único?"

> "Para permitir validar o certificado sem depender apenas do nome do aluno ou do número interno do banco."

---

### 34. "O que impede o aluno de receber dois certificados?"

Existe:

```sql
id_matricula INT NOT NULL UNIQUE
```

na tabela `certificados`.

Além disso, antes de emitir outro certificado, o código consulta se já existe certificado para aquela matrícula.

---

# 🟡 16. Banco de dados — perguntas clássicas

O professor pode perguntar:

### "O que é Primary Key?"

> Identifica unicamente cada registro.

### "O que é Foreign Key?"

> Cria uma relação com outra tabela.

### "O que é `UNIQUE`?"

> Impede duplicação daquele valor ou combinação.

### "O que é `NOT NULL`?"

> Obriga o campo a receber um valor.

### "O que é `CHECK`?"

Por exemplo:

```sql
CHECK (nota_minima >= 1 AND nota_minima <= 100)
```

> "É uma restrição que impede valores fora de uma regra definida."

### "Por que `JSONB` nas respostas?"

A tabela:

```sql
respostas JSONB
```

guarda as respostas daquela tentativa em uma estrutura JSON.

---

# 🔴 17. Pergunta que eu acho MUITO provável: "Vocês encontraram algum erro?"

Aqui você tem uma resposta excelente.

Existe um problema real em:

`login/cadastrar.php`

O código faz:

```php
if (cadastrarUsuario(...)) {
    cadastrarAluno(...);

    header("Location: login.php?sucesso=cadastrado");
}
```

O problema é que o retorno de:

```php
cadastrarAluno()
```

não é verificado.

Pode acontecer:

```text
cadastrarUsuario()
       ↓
     SUCESSO
       ↓
cadastrarAluno()
       ↓
     FALHA
       ↓
redireciona mesmo assim
```

Então existe a possibilidade de:

```text
usuarios = criado
alunos   = não criado
```

### Como responder?

> "Encontramos uma falha de consistência no cadastro. A conta de usuário é criada e depois o registro acadêmico é criado, mas atualmente o retorno da segunda operação não é verificado. O ideal seria colocar as duas operações dentro de uma transação, fazendo rollback se uma delas falhar."

Essa resposta é **muito boa para uma banca**.

---

# 🔴 18. "Como você corrigiria esse problema?"

O professor pode continuar.

Você poderia explicar:

```text
BEGIN
   ↓
criar usuário
   ↓
criar aluno
   ↓
se tudo funcionar → COMMIT
   ↓
se algo falhar → ROLLBACK
```

Ou seja:

> "Eu colocaria o cadastro do usuário e do aluno na mesma transação e só confirmaria depois que os dois inserts fossem concluídos."

---

# 🔴 19. Outro ponto importante: credenciais do banco

No `database/connect.php`, existem valores padrão:

```php
$host = getenv('DB_HOST') ?: "192.168.10.52";
$port = getenv('DB_PORT') ?: "5432";
$dbname = getenv('DB_NAME') ?: "hightech_school";
$user = getenv('DB_USER') ?: "admin";
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "admin123";
```

Isso é uma coisa que **eu não deixaria passar despercebida**.

### Professor:

> "A senha do banco está no código?"

Resposta honesta:

> "Existe um valor padrão no arquivo, mas o código dá preferência a variáveis de ambiente. Em um ambiente de produção, eu removeria a senha padrão do código e exigiria que ela viesse de uma variável de ambiente ou de um gerenciador de segredos."

Isso demonstra maturidade.

---

# 🟡 20. "O que acontece se o banco estiver fora do ar?"

O `connect.php` captura:

```php
catch (PDOException $e)
```

e define:

```php
$conexao = null;
```

Além disso, registra o erro:

```php
error_log(...)
```

Então:

> "A aplicação não exibe os detalhes técnicos do banco para o usuário e algumas páginas apresentam uma mensagem de indisponibilidade."

---

# 🟡 21. "Por que não mostrar o erro do PostgreSQL para o usuário?"

Porque poderia revelar informações internas.

Por exemplo:

```text
host
banco
tabela
consulta
estrutura interna
```

Resposta:

> "O erro técnico vai para o log do servidor, enquanto o usuário recebe uma mensagem genérica."

---

# 🟢 22. XSS

Você utiliza bastante:

```php
htmlspecialchars()
```

### Professor:

> "Para que serve?"

> "Para escapar caracteres especiais quando dados vindos do usuário ou do banco são colocados no HTML, reduzindo o risco de XSS."

Exemplo:

```php
htmlspecialchars($nome)
```

---

# 🟡 23. "GET ou POST?"

### GET

Usado, por exemplo, para:

```text
talentos.php?busca=PHP
```

Porque é uma consulta/filtro.

### POST

Usado para ações como:

```text
cadastrar
editar
excluir
concluir aula
enviar prova
```

Resposta:

> "GET é adequado para consultas e filtros que podem ficar na URL. POST é usado para operações que enviam dados ao servidor, principalmente quando existe alteração de estado."

---

# 🔴 24. "Toda operação de exclusão está protegida?"

As exclusões administrativas que analisamos utilizam:

- POST
- CSRF
- validação de ID
- função específica no banco
- autorização administrativa

Mas existem **inconsistências na proteção CSRF entre operações**.

Isso é algo que eu recomendo vocês corrigirem antes da apresentação.

---

# 🟡 25. "Por que validar no PHP se o HTML já tem `required`?"

Pergunta muito boa.

Por exemplo:

```html
<input required>
```

não é suficiente.

Resposta:

> "Porque a validação do HTML pode ser burlada. O usuário pode enviar uma requisição diretamente para o servidor. Por isso as regras importantes precisam ser verificadas no PHP e, quando possível, também no banco."

---

# 🔴 26. "O banco também protege os dados?"

Sim.

Isso é uma parte forte do projeto.

O banco possui:

- `PRIMARY KEY`
- `FOREIGN KEY`
- `UNIQUE`
- `NOT NULL`
- `CHECK`
- `ON DELETE CASCADE`

Exemplo:

```sql
CHECK (max_tentativas >= 1 AND max_tentativas <= 20)
```

Então mesmo que alguém tente enviar:

```text
max_tentativas = -500
```

o próprio banco pode rejeitar.

---

# 🟡 27. "Por que colocar regras no banco se o PHP já valida?"

> "Porque são camadas diferentes de proteção. O PHP controla as regras da aplicação, mas o banco também deve proteger a integridade dos próprios dados."

Essa é uma resposta excelente.

---

# 🔴 28. "Qual é a diferença entre validação e integridade?"

**Validação:**

```text
PHP
↓
"Esse valor faz sentido?"
```

**Integridade:**

```text
Banco
↓
"Esse dado pode existir respeitando as regras das tabelas?"
```

---

# 🟡 29. "O que é CRUD?"

Você deve saber:

| Letra | Operação |
|---|---|
| C | Create |
| R | Read |
| U | Update |
| D | Delete |

A HighTech possui CRUD de entidades como:

- alunos
- cursos
- etc.

Mas o projeto vai além do CRUD, principalmente em:

```text
matrícula
→ aulas
→ progresso
→ prova
→ certificado
```

---

# 🔴 30. "Então o projeto é só CRUD?"

**Não.**

Essa é uma pergunta que pode aparecer.

Resposta:

> "Não. O CRUD continua sendo uma parte do projeto, mas existem fluxos que envolvem regras de negócio e várias tabelas. Por exemplo, fazer uma prova depende de uma matrícula ativa e da conclusão das aulas obrigatórias, e a aprovação pode gerar um certificado."

---

# 🟡 31. "Como vocês fizeram o relacionamento das aulas?"

A estrutura é aproximadamente:

```text
CURSOS
   │
   ├── AULAS
   │
   └── PROVAS
        │
        └── QUESTÕES
              │
              └── ALTERNATIVAS
```

E:

```text
ALUNO
   │
   └── MATRÍCULA
          │
          └── CURSO
                │
                └── PROGRESSO
```

Essa estrutura vale muito a pena você desenhar no papel e estudar.

---

# 🔴 32. Perguntas "pegadinha" que eu treinaria

O professor pode mostrar uma linha e perguntar:

### `PDO::PARAM_INT`

> "Indica que aquele parâmetro deve ser tratado como inteiro pelo PDO."

---

### `fetch()`

> "Obtém um registro do resultado da consulta."

### `fetchAll()`

> "Obtém todos os registros."

### `bindValue()`

> "Associa um valor a um parâmetro da consulta preparada."

### `bindParam()`

> "Associa uma variável ao parâmetro."

---

### `$_SESSION`

> "Armazena informações da sessão do usuário entre requisições."

---

### `$_POST`

> "Recebe dados enviados pelo formulário através do método POST."

---

### `$_GET`

> "Recebe parâmetros enviados pela URL."

---

### `filter_var()`

> "Pode validar ou filtrar valores recebidos."

No projeto aparece para validar IDs e e-mails.

---

### `random_bytes(32)`

> "Gera bytes aleatórios criptograficamente seguros, usados aqui para criar tokens CSRF."

---

### `random_int()`

Usado na geração do código do certificado.

> "Gera números inteiros aleatórios de forma adequada para esse tipo de geração."

---

# 🔴 33. Perguntas difíceis que podem separar quem entendeu de quem decorou

### "E se duas pessoas enviarem uma prova ao mesmo tempo?"

O projeto possui uma proteção interessante:

```sql
FOR UPDATE OF m
```

na matrícula.

Além disso, o contador de tentativas fica dentro da transação.

Você pode responder:

> "A operação usa uma transação e bloqueia a linha da matrícula durante a operação. Isso ajuda a serializar as tentativas daquele vínculo e evita que duas requisições concorrentes ultrapassem facilmente o limite de tentativas."

---

### "E se duas pessoas tentarem matricular ao mesmo tempo?"

O código utiliza transação e:

```sql
FOR UPDATE
```

no curso.

Isso ajuda no controle de concorrência.

---

# 🔴 34. Pergunta de arquitetura

### "Por que colocar as funções em `functions.php`?"

> "Para evitar repetir consultas e regras em várias páginas. As páginas cuidam mais do fluxo da interface, enquanto as funções centralizam operações de banco e regras de negócio."

---

### "Isso seria MVC?"

Aqui cuidado.

Eu **não chamaria o projeto de MVC completo**.

Você pode dizer:

> "Ele possui uma separação de responsabilidades inspirada nessa ideia, principalmente com funções compartilhadas e páginas separadas, mas eu não classificaria o projeto como uma implementação completa de MVC."

Essa resposta é mais correta.

---

# 🔴 35. "Quais melhorias vocês fariam?"

Eu responderia com esta ordem:

### 1. Corrigir atomicidade do cadastro

```text
usuarios + alunos
        ↓
     transação
```

### 2. Colocar CSRF em todas as operações que alteram dados

Principalmente:

- cadastro
- atualização
- criação de matrícula
- outras operações POST administrativas.

### 3. Regenerar sessão após login

```php
session_regenerate_id(true);
```

### 4. Remover credenciais padrão do código

Usar obrigatoriamente:

```text
variáveis de ambiente / secrets
```

### 5. Criar testes automatizados

Hoje a auditoria identificou **validação sintática**, mas não uma suíte de testes automatizados completa.

### 6. Criar uma estratégia de migrations mais formal

Hoje existe uma migration educacional, mas o processo ainda pode ser mais estruturado.

---

# 🔥 Ranking final: o que eu acho que o professor mais provavelmente vai perguntar

| Prioridade | Pergunta |
|---|---|
| 🔴 1 | Como funciona o sistema inteiro? |
| 🔴 2 | Como funciona o login? |
| 🔴 3 | Como a senha é protegida? |
| 🔴 4 | O que é `password_hash()`? |
| 🔴 5 | O que é PDO? |
| 🔴 6 | Por que usar `prepare()`? |
| 🔴 7 | Como vocês evitam SQL Injection? |
| 🔴 8 | O que é autenticação x autorização? |
| 🔴 9 | Como funciona `exigirAdmin()`? |
| 🔴 10 | O que é CSRF? |
| 🔴 11 | Como funciona o token CSRF? |
| 🔴 12 | O que é uma transação? |
| 🔴 13 | Por que usar `commit` e `rollback`? |
| 🔴 14 | Como funciona a matrícula? |
| 🔴 15 | Por que existe uma tabela `matriculas`? |
| 🔴 16 | Como o sistema controla o progresso? |
| 🔴 17 | Como a prova é corrigida? |
| 🔴 18 | Por que o gabarito fica no servidor? |
| 🔴 19 | Como limitam as tentativas? |
| 🔴 20 | Como o certificado é gerado? |
| 🟠 21 | O que é Foreign Key? |
| 🟠 22 | O que é `ON DELETE CASCADE`? |
| 🟠 23 | O que é `UNIQUE`? |
| 🟠 24 | O que é `CHECK`? |
| 🟠 25 | O que é `ON CONFLICT`? |
| 🟠 26 | O que é `FOR UPDATE`? |
| 🟠 27 | Por que usar `htmlspecialchars()`? |
| 🟠 28 | Por que POST e não GET? |
| 🟠 29 | Por que validar no PHP se o HTML já valida? |
| 🔥 30 | Qual erro vocês encontraram no próprio projeto? |

---

## ⚠️ Os 5 pontos que eu corrigiria antes da apresentação

Minha auditoria encontrou estes como os pontos mais importantes:

1. **Cadastro `usuarios` + `alunos` não é atômico.**
2. **CSRF não está aplicado de maneira uniforme a todas as operações que alteram dados.**
3. **Não encontrei regeneração do ID da sessão após login.**
4. **Existe credencial padrão do PostgreSQL no `connect.php`.**
5. **Não há uma suíte de testes automatizados identificada; a verificação de sintaxe não garante que todos os fluxos funcionem em produção.**

E tem uma coisa importante: **isso não significa que o projeto esteja ruim**. Pelo contrário, a estrutura tem vários pontos bem acima de um CRUD escolar simples — principalmente transações, `FOR UPDATE`, hash de senha, CSRF, correção da prova no servidor, restrições do banco e controle de concorrência.

### O que eu faria agora

O melhor próximo passo é montar uma **simulação de banca**, mas baseada no código real: eu faria umas **40–50 perguntas como se fosse seu professor**, começando com perguntas fáceis e aumentando até perguntas que podem te pegar, e para cada uma colocaria:

**👨‍🏫 Pergunta → 🎯 o que ele quer testar → 🗣️ resposta que você pode dar → 💻 trecho do seu código relacionado.**

Assim você consegue estudar **entendendo o código**, e não decorando o roteiro.