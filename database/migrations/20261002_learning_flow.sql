-- Migração do fluxo educacional para bancos existentes.
-- Execute este arquivo uma vez no banco da aplicação. IF NOT EXISTS permite reexecutar
-- sem apagar dados nem recriar as tabelas que já estiverem presentes.
-- O banco já precisa conter cursos e matriculas, criados pelo schema.sql original.

-- Guarda os links e dados das aulas que pertencem a cada curso.
CREATE TABLE IF NOT EXISTS aulas (
    id SERIAL PRIMARY KEY,
    id_curso INT NOT NULL REFERENCES cursos(id) ON DELETE CASCADE,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT,
    conteudo TEXT,
    video_url VARCHAR(500),
    ordem INT NOT NULL DEFAULT 1,
    obrigatoria BOOLEAN NOT NULL DEFAULT true,
    ativa BOOLEAN NOT NULL DEFAULT true,
    CONSTRAINT aulas_curso_ordem_unique UNIQUE (id_curso, ordem)
);

-- Guarda, por matrícula, quais aulas o aluno marcou como concluídas.
CREATE TABLE IF NOT EXISTS progresso_aulas (
    id SERIAL PRIMARY KEY,
    id_matricula INT NOT NULL REFERENCES matriculas(id) ON DELETE CASCADE,
    id_aula INT NOT NULL REFERENCES aulas(id) ON DELETE CASCADE,
    concluida_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT progresso_aulas_matricula_aula_unique UNIQUE (id_matricula, id_aula)
);

-- Define nota mínima, limite de tentativas e publicação da prova de cada curso.
CREATE TABLE IF NOT EXISTS provas (
    id SERIAL PRIMARY KEY,
    id_curso INT NOT NULL UNIQUE REFERENCES cursos(id) ON DELETE CASCADE,
    nota_minima NUMERIC(5, 2) NOT NULL DEFAULT 70
        CHECK (nota_minima >= 1 AND nota_minima <= 100),
    max_tentativas SMALLINT NOT NULL DEFAULT 3
        CHECK (max_tentativas >= 1 AND max_tentativas <= 20),
    ativa BOOLEAN NOT NULL DEFAULT false,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Guarda cada questão e sua posição dentro da prova.
CREATE TABLE IF NOT EXISTS questoes_prova (
    id SERIAL PRIMARY KEY,
    id_prova INT NOT NULL REFERENCES provas(id) ON DELETE CASCADE,
    enunciado TEXT NOT NULL,
    ordem INT NOT NULL,
    CONSTRAINT questoes_prova_ordem_unique UNIQUE (id_prova, ordem)
);

-- Guarda as alternativas e identifica no banco a resposta usada na correção.
CREATE TABLE IF NOT EXISTS alternativas_prova (
    id SERIAL PRIMARY KEY,
    id_questao INT NOT NULL REFERENCES questoes_prova(id) ON DELETE CASCADE,
    texto TEXT NOT NULL,
    correta BOOLEAN NOT NULL DEFAULT false
);

-- Registra a nota, aprovação e respostas de cada tentativa do aluno.
CREATE TABLE IF NOT EXISTS tentativas_prova (
    id SERIAL PRIMARY KEY,
    id_matricula INT NOT NULL REFERENCES matriculas(id) ON DELETE CASCADE,
    id_prova INT NOT NULL REFERENCES provas(id) ON DELETE CASCADE,
    nota NUMERIC(5, 2) NOT NULL CHECK (nota >= 0 AND nota <= 100),
    aprovada BOOLEAN NOT NULL,
    respostas JSONB NOT NULL,
    realizada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Emite um único certificado por matrícula com código público de validação.
CREATE TABLE IF NOT EXISTS certificados (
    id SERIAL PRIMARY KEY,
    id_matricula INT NOT NULL UNIQUE REFERENCES matriculas(id) ON DELETE CASCADE,
    id_prova INT NOT NULL REFERENCES provas(id) ON DELETE CASCADE,
    codigo UUID NOT NULL UNIQUE,
    emitido_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
