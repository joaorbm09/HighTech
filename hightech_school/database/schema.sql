-- Contas de acesso e os perfis permitidos no sistema.
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil VARCHAR(20) NOT NULL DEFAULT 'aluno'
        CHECK (perfil IN ('aluno', 'admin')),
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Dados acadêmicos do aluno; o índice também impede e-mails repetidos sem
-- diferenciar letras maiúsculas de minúsculas.
CREATE TABLE alunos (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    email VARCHAR(100) NOT NULL,
    turma VARCHAR(20),
    nascimento DATE,
    ativo BOOLEAN NOT NULL DEFAULT true,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX alunos_email_unique ON alunos (LOWER(email));

-- Catálogo de cursos que podem ser disponibilizados para matrícula.
CREATE TABLE cursos (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    categoria VARCHAR(50) NOT NULL,
    descricao TEXT,
    carga_horaria INT NOT NULL CHECK (carga_horaria > 0),
    ativo BOOLEAN NOT NULL DEFAULT true
);

-- Relaciona cada aluno a um curso e guarda o estado da matrícula.
CREATE TABLE matriculas (
    id SERIAL PRIMARY KEY,
    id_aluno INT NOT NULL REFERENCES alunos(id) ON DELETE CASCADE,
    id_curso INT NOT NULL REFERENCES cursos(id) ON DELETE CASCADE,
    data_matricula DATE NOT NULL DEFAULT CURRENT_DATE,
    status VARCHAR(20) NOT NULL DEFAULT 'Ativa',
    UNIQUE (id_aluno, id_curso)
);

-- Aulas e a posição em que aparecem dentro de cada curso.
CREATE TABLE aulas (
    id SERIAL PRIMARY KEY,
    id_curso INT NOT NULL REFERENCES cursos(id) ON DELETE CASCADE,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT,
    conteudo TEXT,
    video_url VARCHAR(500),
    ordem INT NOT NULL DEFAULT 1,
    obrigatoria BOOLEAN NOT NULL DEFAULT true,
    ativa BOOLEAN NOT NULL DEFAULT true,
    UNIQUE (id_curso, ordem)
);

-- Registra quais aulas cada matrícula já concluiu.
CREATE TABLE progresso_aulas (
    id SERIAL PRIMARY KEY,
    id_matricula INT NOT NULL REFERENCES matriculas(id) ON DELETE CASCADE,
    id_aula INT NOT NULL REFERENCES aulas(id) ON DELETE CASCADE,
    concluida_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (id_matricula, id_aula)
);

-- Configuração da prova vinculada ao curso.
CREATE TABLE provas (
    id SERIAL PRIMARY KEY,
    id_curso INT NOT NULL UNIQUE REFERENCES cursos(id) ON DELETE CASCADE,
    nota_minima NUMERIC(5, 2) NOT NULL DEFAULT 70
        CHECK (nota_minima BETWEEN 1 AND 100),
    max_tentativas SMALLINT NOT NULL DEFAULT 3
        CHECK (max_tentativas BETWEEN 1 AND 20),
    ativa BOOLEAN NOT NULL DEFAULT false,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Perguntas da prova e suas alternativas, incluindo a resposta correta.
CREATE TABLE questoes_prova (
    id SERIAL PRIMARY KEY,
    id_prova INT NOT NULL REFERENCES provas(id) ON DELETE CASCADE,
    enunciado TEXT NOT NULL,
    ordem INT NOT NULL,
    UNIQUE (id_prova, ordem)
);

CREATE TABLE alternativas_prova (
    id SERIAL PRIMARY KEY,
    id_questao INT NOT NULL REFERENCES questoes_prova(id) ON DELETE CASCADE,
    texto TEXT NOT NULL,
    correta BOOLEAN NOT NULL DEFAULT false
);

-- Histórico das respostas e notas obtidas em cada tentativa.
CREATE TABLE tentativas_prova (
    id SERIAL PRIMARY KEY,
    id_matricula INT NOT NULL REFERENCES matriculas(id) ON DELETE CASCADE,
    id_prova INT NOT NULL REFERENCES provas(id) ON DELETE CASCADE,
    nota NUMERIC(5, 2) NOT NULL CHECK (nota BETWEEN 0 AND 100),
    aprovada BOOLEAN NOT NULL,
    respostas JSONB NOT NULL,
    realizada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Certificado emitido após a conclusão e seu código público de validação.
CREATE TABLE certificados (
    id SERIAL PRIMARY KEY,
    id_matricula INT NOT NULL UNIQUE REFERENCES matriculas(id) ON DELETE CASCADE,
    id_prova INT NOT NULL REFERENCES provas(id) ON DELETE CASCADE,
    codigo UUID NOT NULL UNIQUE,
    emitido_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Cursos iniciais para que o catálogo tenha conteúdo após a instalação.
INSERT INTO cursos (nome, categoria, descricao, carga_horaria)
VALUES
    ('Introdução ao Desenvolvimento Web', 'Desenvolvimento', 'Fundamentos de HTML, CSS e PHP.', 40),
    ('Banco de Dados com PostgreSQL', 'Banco de dados', 'Criação de tabelas e consultas SQL.', 30)
ON CONFLICT DO NOTHING;
