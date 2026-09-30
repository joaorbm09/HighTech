-- Script DDL para criação do Banco de Dados Relacional da HighTech
-- Banco SGBD: PostgreSQL

-- 1. Tabela de Cursos
CREATE TABLE IF NOT EXISTS cursos (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    descricao TEXT,
    carga_horaria INT NOT NULL,
    ativo BOOLEAN DEFAULT true
);

-- 2. Tabela de Alunos
CREATE TABLE IF NOT EXISTS alunos (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14),
    email VARCHAR(100) NOT NULL,
    turma VARCHAR(20),
    nascimento DATE,
    ativo BOOLEAN DEFAULT true,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE alunos ADD COLUMN IF NOT EXISTS cpf VARCHAR(14);
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
CREATE UNIQUE INDEX IF NOT EXISTS alunos_cpf_unique ON alunos (cpf) WHERE cpf IS NOT NULL;

-- 3. Tabela de Matrículas (Relacionamento N:N entre Alunos e Cursos)
CREATE TABLE IF NOT EXISTS matriculas (
    id SERIAL PRIMARY KEY,
    id_aluno INT NOT NULL REFERENCES alunos(id) ON DELETE CASCADE,
    id_curso INT NOT NULL REFERENCES cursos(id) ON DELETE CASCADE,
    data_matricula DATE DEFAULT CURRENT_DATE,
    status VARCHAR(20) DEFAULT 'Ativa'
);

-- 4. Tabela de Usuários (Autenticação e Login)
CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    perfil VARCHAR(20) DEFAULT 'aluno', -- 'aluno' ou 'admin'
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Dados Iniciais (Seeds) para Testes (Idempotentes)

INSERT INTO cursos (nome, categoria, descricao, carga_horaria, ativo)
SELECT 'Desenvolvimento Web & PHP', 'Desenvolvimento', 'Aprenda a criar aplicações web dinâmicas, modernas e integradas com banco de dados PostgreSQL.', 80, true
WHERE NOT EXISTS (SELECT 1 FROM cursos WHERE nome = 'Desenvolvimento Web & PHP');

INSERT INTO cursos (nome, categoria, descricao, carga_horaria, ativo)
SELECT 'Gestão e Negócios Digitais', 'Gestão & Ágil', 'Capacitação voltada para a gestão estratégica de empresas, Scrum, Kanban e projetos de TI.', 60, true
WHERE NOT EXISTS (SELECT 1 FROM cursos WHERE nome = 'Gestão e Negócios Digitais');

INSERT INTO cursos (nome, categoria, descricao, carga_horaria, ativo)
SELECT 'Transformação Digital para PMEs', 'Inovação & PMEs', 'Modernize os processos do seu negócio utilizando ferramentas de automação e análise de dados.', 40, true
WHERE NOT EXISTS (SELECT 1 FROM cursos WHERE nome = 'Transformação Digital para PMEs');

INSERT INTO alunos (nome, cpf, email, turma, nascimento, ativo)
SELECT 'João Victor', '111.222.333-44', 'joao@hightech.com', 'DEV-2026', '2005-04-12', true
WHERE NOT EXISTS (SELECT 1 FROM alunos WHERE email = 'joao@hightech.com');

INSERT INTO alunos (nome, cpf, email, turma, nascimento, ativo)
SELECT 'Maria Silva', '222.333.444-55', 'maria@hightech.com', 'GES-2026', '2003-08-25', true
WHERE NOT EXISTS (SELECT 1 FROM alunos WHERE email = 'maria@hightech.com');

INSERT INTO matriculas (id_aluno, id_curso, status)
SELECT 1, 1, 'Ativa'
WHERE NOT EXISTS (SELECT 1 FROM matriculas WHERE id_aluno = 1 AND id_curso = 1);

INSERT INTO matriculas (id_aluno, id_curso, status)
SELECT 2, 2, 'Ativa'
WHERE NOT EXISTS (SELECT 1 FROM matriculas WHERE id_aluno = 2 AND id_curso = 2);

-- 5. Tabela de Demandas e Solicitações de Empresas (B2B)
CREATE TABLE IF NOT EXISTS solicitacoes_empresas (
    id SERIAL PRIMARY KEY,
    nome_empresa VARCHAR(150) NOT NULL,
    cnpj VARCHAR(20),
    responsavel VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(25),
    tamanho_equipe VARCHAR(50),
    servico_interesse VARCHAR(100) NOT NULL,
    mensagem TEXT,
    status VARCHAR(30) DEFAULT 'Pendente', -- 'Pendente', 'Em Análise', 'Atendido'
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Tabela de Vagas Tech (Mural de Oportunidades Conectando Empresas e Alunos)
CREATE TABLE IF NOT EXISTS vagas (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    empresa VARCHAR(150) NOT NULL,
    modalidade VARCHAR(50) DEFAULT 'Remoto', -- 'Remoto', 'Híbrido', 'Presencial'
    tipo_contrato VARCHAR(50) DEFAULT 'CLT', -- 'CLT', 'PJ', 'Estágio'
    localizacao VARCHAR(100) DEFAULT 'Brasil',
    salario VARCHAR(50),
    descricao TEXT NOT NULL,
    requisitos TEXT,
    contato_candidatura VARCHAR(255),
    ativa BOOLEAN DEFAULT true,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Tabela de Perfil Profissional dos Alunos (Banco de Talentos)
CREATE TABLE IF NOT EXISTS perfil_talento (
    id SERIAL PRIMARY KEY,
    id_aluno INT NOT NULL REFERENCES alunos(id) ON DELETE CASCADE,
    titulo_profissional VARCHAR(100),
    bio TEXT,
    linkedin VARCHAR(255),
    github VARCHAR(255),
    habilidades VARCHAR(255),
    disponivel_mercado BOOLEAN DEFAULT true,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT perfil_talento_id_aluno_unique UNIQUE (id_aluno)
);

-- Seeds para Vagas de Exemplo
INSERT INTO vagas (titulo, empresa, modalidade, tipo_contrato, localizacao, salario, descricao, requisitos, contato_candidatura, ativa)
SELECT 'Desenvolvedor(a) Web Júnior (PHP & SQL)', 'TechInova Solutions', 'Remoto', 'CLT', 'Remoto - Brasil', 'R$ 3.500 - R$ 4.200',
       'Buscamos desenvolvedor júnior para atuar na manutenção e criação de rotinas web, integração com banco de dados PostgreSQL e APIs.',
       'Conhecimento em PHP, PDO, PostgreSQL, Git e HTML/CSS. Diferencial: formações na HighTech School.',
       'rh@techinova.com.br', true
WHERE NOT EXISTS (SELECT 1 FROM vagas WHERE titulo = 'Desenvolvedor(a) Web Júnior (PHP & SQL)');

INSERT INTO vagas (titulo, empresa, modalidade, tipo_contrato, localizacao, salario, descricao, requisitos, contato_candidatura, ativa)
SELECT 'Estágio em Suporte e Gestão de TI', 'Nexus Cloud Consulting', 'Híbrido', 'Estágio', 'São Paulo, SP', 'R$ 1.800 + Benefícios',
       'Oportunidade para estudantes de tecnologia auxiliarem no suporte de infraestrutura e gestão ágil de projetos.',
       'Cursando TI, Administração de Redes ou afins. Noções de metodologias ágeis (Scrum/Kanban).',
       'estagio@nexusconsulting.com', true
WHERE NOT EXISTS (SELECT 1 FROM vagas WHERE titulo = 'Estágio em Suporte e Gestão de TI');

-- Seed para Solicitação de Empresa de Teste
INSERT INTO solicitacoes_empresas (nome_empresa, cnpj, responsavel, email, telefone, tamanho_equipe, servico_interesse, mensagem, status)
SELECT 'Vanguard Logística & Tech', '12.345.678/0001-90', 'Carlos Mendes', 'carlos@vanguardlog.com', '(11) 98765-4321', '21 a 50 colaboradores',
       'Consultoria em TI & Banco de Dados', 'Precisamos migrar relatórios manuais para um dashboard em tempo real integrado ao PostgreSQL.', 'Em Análise'
WHERE NOT EXISTS (SELECT 1 FROM solicitacoes_empresas WHERE nome_empresa = 'Vanguard Logística & Tech');

-- Seed para Perfil de Talento do Aluno 1
INSERT INTO perfil_talento (id_aluno, titulo_profissional, bio, linkedin, github, habilidades, disponivel_mercado)
SELECT 1, 'Desenvolvedor Back-End Júnior', 'Apaixonado por desenvolvimento web, arquitetura de bancos de dados relacionais e automação de rotinas.',
       'https://linkedin.com', 'https://github.com', 'PHP, PostgreSQL, HTML5, CSS3, Git, Metodologias Ágeis', true
WHERE NOT EXISTS (SELECT 1 FROM perfil_talento WHERE id_aluno = 1);

