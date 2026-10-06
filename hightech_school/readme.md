# HighTech School

Sistema simples de gestão escolar feito com PHP, HTML, CSS e PostgreSQL.

## O que o sistema faz

- Cadastro e login de alunos.
- Administração de contas e perfis de acesso.
- Cadastro de alunos, cursos e matrículas.
- Cadastro de aulas e acompanhamento do progresso.
- Configuração de provas, questões e tentativas.
- Emissão e consulta de certificados.

O portal de empresas, as vagas e o banco de talentos pertencem ao projeto original e não fazem parte desta versão.

Consulte a [documentação das tags HTML](docs/html-tags.md) para ver os elementos usados nas páginas desta versão.

Os arquivos de PHP, CSS e SQL também têm comentários junto às partes principais, explicando o objetivo das páginas, regras, estilos e tabelas. Os comentários destacam etapas e blocos importantes sem repetir em cada linha o que o código já deixa evidente.

## Como iniciar

1. Instale PHP com `PDO` e `pdo_pgsql`, PostgreSQL e Git.
2. Crie um banco chamado `hightech_school`.
3. No pgAdmin, abra o banco, escolha **Query Tool**, carregue `database/schema.sql` e execute o script. Também pode usar `psql -U postgres -d hightech_school -f database/schema.sql`.
4. Configure as variáveis `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS`. Os valores padrão usam `127.0.0.1:5432`, banco `hightech_school`, usuário `postgres` e senha vazia.
5. Abra um terminal nesta pasta, inicie o servidor PHP com `php -S localhost:8000` e abra `http://localhost:8000` no navegador.

Se usar outro usuário ou senha do PostgreSQL, defina as variáveis de ambiente antes de iniciar o servidor. Não coloque senhas reais nos arquivos do projeto.

## Primeiro administrador

1. Crie uma conta pela página **Cadastro**.
2. No PostgreSQL, transforme essa conta em administradora:

```sql
UPDATE usuarios SET perfil = 'admin' WHERE email = 'seu-email@exemplo.com';
```

3. Entre novamente. O menu administrativo permite gerenciar contas, alunos, cursos, aulas, provas e matrículas.

## Pastas principais

- `app/`: telas de aluno e administração.
- `assets/`: estilos da página.
- `database/`: conexão e estrutura do banco.
- `includes/`: autenticação, funções e partes compartilhadas da página.
- `login/`: cadastro, login e saída.

Operações simples, como listar, cadastrar, editar e excluir cursos e alunos, ficam nas próprias páginas. O arquivo `includes/functions.php` mantém apenas operações compartilhadas ou que precisam aplicar regras escolares, como matrícula, provas, progresso e certificados.

Ao excluir uma conta pela administração, o cadastro acadêmico e o histórico de matrículas são mantidos. A tela não permite excluir a própria conta nem remover o último administrador.
