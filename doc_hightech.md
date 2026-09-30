# HighTech - Sistema de serviços e educacional em tecnologia

A **HighTech** é uma aplicação web desenvolvida em PHP com foco no gerenciamento de uma empresa que fornece serviços e ensino educacional com foco em tecnologia. O sistema foi criado no intuito de ser uma empresa que ajuda outras empresas com serviços, no setor de tecnologia(seja a empresa pequena ou grande), além disso temos uma pagina onde ofereçemos cursos na area da tecnologia, cursos gratuitos, que tem a intenção de ser didatico e intuitivo.

A plataforma permite realizar operações essenciais de gerenciamento, como cadastro, listagem, edição e exclusão de registros, utilizando conceito de CRUD integrado a um banco de dados PostrgreSQL. Dessa forma, o sistema e usuários, facilitando o gerenciamento do sistema.

---

## Como executar o projeto localmente

Para rodar o prjeto na sua maquina, será necessário ter instalado um ambiente de servidor local (**PHP**) e o banco de dados **PostgreSQL**.

### Pré-requisitos
* **PHP** (v7.4 ou superior) habilitado com a extensão `pdo_pgsql`.
* **PostgreSQL** instalado e rodar na porta `5432` padrão.
* **Git** instaldo na máquina.

### Passo 1: Clonar o repositório 

Abra o terminal e execute:
```
1º Clone este repositório 
git clone  clone https://github.com/joaorbm09/HighTech.git

2º Acesse a pasta do projeto
cd geek-hub
```

### Passo 2: conigurar o banco de dados

* 1 - Abra o terminal/Prompt de Comando ou PowerShell

* 2 - Conecta-se ao PostgreSQL usando o ue usuário principal (normalmente `postgres`):
```
psql -U postgres
```
* 3 - Se o banco de dados já existir de testes anteriores, apague-o e crie um novo para evitar conflitos de tabelas. Dentro do terminal interativo do `psql`, digite: 
```
DROP DATABASE IF EXISTS  hightech;
CREATE DATABASE hightech;
\q
```

* 4 - Navegue para a pasta `db` do projeto clonado e restaure o banco de dados executando o seguinte comando: 
```
# Supondo que voce estja na raiz do projeto clonado, utlize o comando:  
cd db

# Restaure o banco de dados, utlize o comando:  
psql -U postgres -d hightech -f dump_hightech.sql

# Volte para a raiz do projeto, utlize o comando: 
cd ..
```

### passo 3: Configurar a conexão no PHP

* 1 -  Na raiz do projeto, abra o ficheiro `connet.php`.

* 2 - Altere as credenciais para corresopderem ás do seu ambiente local PostgreSQL:
```
$host = 'localhost';
$port = '5432';
$dbname = '[nome_bd]'; // O mesmo nome que criou no Passo 2
$user = 'postgres'; // O seu usuário do PostgreSQL
$password = 'sua_senha_aqui'; // A sua senha do PostgreSQL
```

### passo 4: Acessar a aplicação

Com o banco de dados configurado, inicie o servidor embutido do PHP.
Certifique-se de que o seu terminal está na raiz do projeto e execute: 
```
php -S 0.0.0.0:8000
```

o servido4r estara ativo. Agora, basta abrir o seu navegador de preferencia e acessar:
```
http://localhost:8000
```

### Passo 5: Informações de entrada
utilize essas informações para entrar no sistema.

* Para entrar como administrador: 
(apenas o professor deve acessar)

**E-mail**: `admin@hightech.com`

**senha**: `admin123`

* Para entrar como usuario voce pode acessar se cadastrando apertando o botão de cadastrar ou voce pode seguir esse usuario aleatório:

**E-mail**: `joao@hightech.com`

**Senha**: `123456`

--- 