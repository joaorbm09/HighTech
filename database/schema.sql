-- Script DDL para criação do Banco de Dados Relacional da HighTech
-- Banco SGBD: PostgreSQL

-- 1. Tabela de Cursos
CREATE TABLE IF NOT EXISTS cursos (
    id SERIAL PRIMARY KEY, -- Identificador numérico gerado automaticamente para cada curso.
    nome VARCHAR(100) NOT NULL, -- Nome obrigatório exibido no catálogo.
    categoria VARCHAR(50) NOT NULL, -- Área usada para classificar o curso.
    descricao TEXT, -- Texto explicativo opcional sobre o conteúdo.
    carga_horaria INT NOT NULL, -- Duração do curso em horas.
    ativo BOOLEAN DEFAULT true -- Indica se o curso está disponível para uso.
);

-- 2. Tabela de Alunos
CREATE TABLE IF NOT EXISTS alunos (
    id SERIAL PRIMARY KEY, -- Identificador numérico gerado automaticamente para cada aluno.
    nome VARCHAR(100) NOT NULL, -- Nome obrigatório usado nas telas e relatórios.
    cpf VARCHAR(14), -- Documento opcional, armazenado com ou sem pontuação.
    email VARCHAR(100) NOT NULL, -- E-mail de contato e associação com a conta.
    turma VARCHAR(20), -- Turma ou grupo acadêmico do aluno.
    nascimento DATE, -- Data de nascimento, quando informada.
    ativo BOOLEAN DEFAULT true, -- Indica se o aluno está ativo no sistema.
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Data e hora em que o registro foi criado.
);

-- Mantém compatibilidade com bases antigas que ainda não tenham esses campos.
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS cpf VARCHAR(14);
ALTER TABLE alunos ADD COLUMN IF NOT EXISTS criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
-- Impede CPF repetido sem impedir que vários alunos deixem o campo opcional vazio.
CREATE UNIQUE INDEX IF NOT EXISTS alunos_cpf_unique ON alunos (cpf) WHERE cpf IS NOT NULL;

-- 3. Tabela de Matrículas (Relacionamento N:N entre Alunos e Cursos)
CREATE TABLE IF NOT EXISTS matriculas (
    id SERIAL PRIMARY KEY, -- Identificador da matrícula.
    id_aluno INT NOT NULL REFERENCES alunos(id) ON DELETE CASCADE, -- Aluno relacionado; a matrícula é removida junto com ele.
    id_curso INT NOT NULL REFERENCES cursos(id) ON DELETE CASCADE, -- Curso relacionado; a matrícula é removida junto com ele.
    data_matricula DATE DEFAULT CURRENT_DATE, -- Data de criação da matrícula quando não informada.
    status VARCHAR(20) DEFAULT 'Ativa' -- Situação atual do vínculo entre aluno e curso.
);

-- 4. Aulas e progresso do aluno
-- Links e dados das aulas publicados para cada curso.
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

-- Progresso registrado por aluno e matrícula, sem duplicar conclusões.
CREATE TABLE IF NOT EXISTS progresso_aulas (
    id SERIAL PRIMARY KEY,
    id_matricula INT NOT NULL REFERENCES matriculas(id) ON DELETE CASCADE,
    id_aula INT NOT NULL REFERENCES aulas(id) ON DELETE CASCADE,
    concluida_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT progresso_aulas_matricula_aula_unique UNIQUE (id_matricula, id_aula)
);

-- 5. Avaliações, questões, tentativas e certificados
-- Uma configuração de prova por curso; começa desativada até o admin publicar.
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

-- Questões ordenadas pertencentes a uma prova.
CREATE TABLE IF NOT EXISTS questoes_prova (
    id SERIAL PRIMARY KEY,
    id_prova INT NOT NULL REFERENCES provas(id) ON DELETE CASCADE,
    enunciado TEXT NOT NULL,
    ordem INT NOT NULL,
    CONSTRAINT questoes_prova_ordem_unique UNIQUE (id_prova, ordem)
);

-- Alternativas de cada questão; a correção é feita no servidor.
CREATE TABLE IF NOT EXISTS alternativas_prova (
    id SERIAL PRIMARY KEY,
    id_questao INT NOT NULL REFERENCES questoes_prova(id) ON DELETE CASCADE,
    texto TEXT NOT NULL,
    correta BOOLEAN NOT NULL DEFAULT false
);

-- Histórico de respostas e notas das tentativas dos alunos.
CREATE TABLE IF NOT EXISTS tentativas_prova (
    id SERIAL PRIMARY KEY,
    id_matricula INT NOT NULL REFERENCES matriculas(id) ON DELETE CASCADE,
    id_prova INT NOT NULL REFERENCES provas(id) ON DELETE CASCADE,
    nota NUMERIC(5, 2) NOT NULL CHECK (nota >= 0 AND nota <= 100),
    aprovada BOOLEAN NOT NULL,
    respostas JSONB NOT NULL,
    realizada_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Certificado emitido após aprovação, identificado por UUID público e único.
CREATE TABLE IF NOT EXISTS certificados (
    id SERIAL PRIMARY KEY,
    id_matricula INT NOT NULL UNIQUE REFERENCES matriculas(id) ON DELETE CASCADE,
    id_prova INT NOT NULL REFERENCES provas(id) ON DELETE CASCADE,
    codigo UUID NOT NULL UNIQUE,
    emitido_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 6. Tabela de Usuários (Autenticação e Login)
CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY, -- Identificador da conta usada para autenticação.
    nome VARCHAR(100) NOT NULL, -- Nome que será exibido na interface.
    email VARCHAR(100) UNIQUE NOT NULL, -- E-mail único usado para entrar no sistema.
    senha VARCHAR(255) NOT NULL, -- Hash da senha, nunca a senha em texto simples.
    perfil VARCHAR(20) DEFAULT 'aluno', -- Perfil de acesso permitido: 'aluno' ou 'admin'.
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Data e hora de criação da conta.
);

-- Dados Iniciais (Seeds) para Testes (Idempotentes)
-- Cada inserção verifica a existência do registro para evitar duplicação ao executar o script novamente.

-- Adiciona um curso introdutório de desenvolvimento web se ainda não estiver cadastrado.
INSERT INTO cursos (nome, categoria, descricao, carga_horaria, ativo)
SELECT 'Desenvolvimento Web & PHP', 'Desenvolvimento', 'Aprenda a criar aplicações web dinâmicas, modernas e integradas com banco de dados PostgreSQL.', 80, true
WHERE NOT EXISTS (SELECT 1 FROM cursos WHERE nome = 'Desenvolvimento Web & PHP');

-- Adiciona um curso demonstrativo de gestão e negócios digitais.
INSERT INTO cursos (nome, categoria, descricao, carga_horaria, ativo)
SELECT 'Gestão e Negócios Digitais', 'Gestão & Ágil', 'Capacitação voltada para a gestão estratégica de empresas, Scrum, Kanban e projetos de TI.', 60, true
WHERE NOT EXISTS (SELECT 1 FROM cursos WHERE nome = 'Gestão e Negócios Digitais');

-- Adiciona um curso demonstrativo sobre transformação digital para PMEs.
INSERT INTO cursos (nome, categoria, descricao, carga_horaria, ativo)
SELECT 'Transformação Digital para PMEs', 'Inovação & PMEs', 'Modernize os processos do seu negócio utilizando ferramentas de automação e análise de dados.', 40, true
WHERE NOT EXISTS (SELECT 1 FROM cursos WHERE nome = 'Transformação Digital para PMEs');

-- Cria um aluno de exemplo da turma de desenvolvimento, se o e-mail ainda não existir.
INSERT INTO alunos (nome, cpf, email, turma, nascimento, ativo)
SELECT 'João Victor', '111.222.333-44', 'joao@hightech.com', 'DEV-2026', '2005-04-12', true
WHERE NOT EXISTS (SELECT 1 FROM alunos WHERE email = 'joao@hightech.com');

-- Cria um segundo aluno de exemplo da turma de gestão, sem duplicar o e-mail.
INSERT INTO alunos (nome, cpf, email, turma, nascimento, ativo)
SELECT 'Maria Silva', '222.333.444-55', 'maria@hightech.com', 'GES-2026', '2003-08-25', true
WHERE NOT EXISTS (SELECT 1 FROM alunos WHERE email = 'maria@hightech.com');

-- Matricula o primeiro aluno no primeiro curso caso essa relação ainda não exista.
INSERT INTO matriculas (id_aluno, id_curso, status)
SELECT 1, 1, 'Ativa'
WHERE NOT EXISTS (SELECT 1 FROM matriculas WHERE id_aluno = 1 AND id_curso = 1);

-- Matricula o segundo aluno no segundo curso caso essa relação ainda não exista.
INSERT INTO matriculas (id_aluno, id_curso, status)
SELECT 2, 2, 'Ativa'
WHERE NOT EXISTS (SELECT 1 FROM matriculas WHERE id_aluno = 2 AND id_curso = 2);

-- 7. Tabela de Demandas e Solicitações de Empresas (B2B)
CREATE TABLE IF NOT EXISTS solicitacoes_empresas (
    id SERIAL PRIMARY KEY, -- Identificador da solicitação corporativa.
    nome_empresa VARCHAR(150) NOT NULL, -- Nome da organização que enviou o formulário.
    cnpj VARCHAR(20), -- CNPJ opcional da organização.
    responsavel VARCHAR(100) NOT NULL, -- Pessoa de contato da empresa.
    email VARCHAR(100) NOT NULL, -- E-mail para retorno da equipe.
    telefone VARCHAR(25), -- Telefone de contato opcional.
    tamanho_equipe VARCHAR(50), -- Faixa de tamanho da equipe selecionada no formulário.
    servico_interesse VARCHAR(100) NOT NULL, -- Serviço ou solução sobre a qual a empresa quer conversar.
    mensagem TEXT, -- Contexto adicional enviado pela empresa.
    status VARCHAR(30) DEFAULT 'Pendente', -- Situação da demanda: 'Pendente', 'Em Análise' ou 'Atendido'.
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Data e hora de envio da solicitação.
);

-- 8. Tabela de Vagas Tech (Mural de Oportunidades Conectando Empresas e Alunos)
CREATE TABLE IF NOT EXISTS vagas (
    id SERIAL PRIMARY KEY, -- Identificador da vaga.
    titulo VARCHAR(150) NOT NULL, -- Cargo ou título exibido no mural.
    empresa VARCHAR(150) NOT NULL, -- Nome da empresa contratante.
    modalidade VARCHAR(50) DEFAULT 'Remoto', -- Forma de trabalho: 'Remoto', 'Híbrido' ou 'Presencial'.
    tipo_contrato VARCHAR(50) DEFAULT 'CLT', -- Tipo de vínculo, como 'CLT', 'PJ' ou 'Estágio'.
    localizacao VARCHAR(100) DEFAULT 'Brasil', -- Cidade, estado ou abrangência geográfica.
    salario VARCHAR(50), -- Faixa salarial opcional apresentada como texto.
    descricao TEXT NOT NULL, -- Descrição obrigatória das responsabilidades da vaga.
    requisitos TEXT, -- Experiência e conhecimentos desejados.
    contato_candidatura VARCHAR(255), -- Endereço de e-mail ou link para candidatura.
    ativa BOOLEAN DEFAULT true, -- Controla se a vaga aparece no mural público.
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Data e hora de publicação.
);

-- 9. Tabela de Perfil Profissional dos Alunos (Banco de Talentos)
CREATE TABLE IF NOT EXISTS perfil_talento (
    id SERIAL PRIMARY KEY, -- Identificador do perfil profissional.
    id_aluno INT NOT NULL REFERENCES alunos(id) ON DELETE CASCADE, -- Aluno dono do perfil; sua remoção também remove o perfil.
    titulo_profissional VARCHAR(100), -- Cargo ou especialidade que o aluno deseja divulgar.
    bio TEXT, -- Resumo profissional opcional.
    linkedin VARCHAR(255), -- Link do perfil profissional no LinkedIn.
    github VARCHAR(255), -- Link do GitHub ou portfólio.
    habilidades VARCHAR(255), -- Lista textual de competências separadas por vírgula.
    disponivel_mercado BOOLEAN DEFAULT true, -- Define se o perfil pode aparecer na vitrine pública.
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Momento da última atualização do perfil.
    CONSTRAINT perfil_talento_id_aluno_unique UNIQUE (id_aluno) -- Garante no máximo um perfil por aluno.
);

-- Inclui vagas demonstrativas para que o mural tenha conteúdo em uma instalação nova.
-- Publica uma vaga remota de desenvolvimento para preencher o mural em instalações novas.
INSERT INTO vagas (titulo, empresa, modalidade, tipo_contrato, localizacao, salario, descricao, requisitos, contato_candidatura, ativa)
SELECT 'Desenvolvedor(a) Web Júnior (PHP & SQL)', 'TechInova Solutions', 'Remoto', 'CLT', 'Remoto - Brasil', 'R$ 3.500 - R$ 4.200',
       'Buscamos desenvolvedor júnior para atuar na manutenção e criação de rotinas web, integração com banco de dados PostgreSQL e APIs.',
       'Conhecimento em PHP, PDO, PostgreSQL, Git e HTML/CSS. Diferencial: formações na HighTech School.',
       'rh@techinova.com.br', true
WHERE NOT EXISTS (SELECT 1 FROM vagas WHERE titulo = 'Desenvolvedor(a) Web Júnior (PHP & SQL)');

-- Publica uma oportunidade de estágio híbrida como segundo exemplo do mural.
INSERT INTO vagas (titulo, empresa, modalidade, tipo_contrato, localizacao, salario, descricao, requisitos, contato_candidatura, ativa)
SELECT 'Estágio em Suporte e Gestão de TI', 'Nexus Cloud Consulting', 'Híbrido', 'Estágio', 'São Paulo, SP', 'R$ 1.800 + Benefícios',
       'Oportunidade para estudantes de tecnologia auxiliarem no suporte de infraestrutura e gestão ágil de projetos.',
       'Cursando TI, Administração de Redes ou afins. Noções de metodologias ágeis (Scrum/Kanban).',
       'estagio@nexusconsulting.com', true
WHERE NOT EXISTS (SELECT 1 FROM vagas WHERE titulo = 'Estágio em Suporte e Gestão de TI');

-- Inclui uma solicitação demonstrativa para ilustrar o fluxo corporativo.
INSERT INTO solicitacoes_empresas (nome_empresa, cnpj, responsavel, email, telefone, tamanho_equipe, servico_interesse, mensagem, status)
SELECT 'Vanguard Logística & Tech', '12.345.678/0001-90', 'Carlos Mendes', 'carlos@vanguardlog.com', '(11) 98765-4321', '21 a 50 colaboradores',
       'Consultoria em TI & Banco de Dados', 'Precisamos migrar relatórios manuais para um dashboard em tempo real integrado ao PostgreSQL.', 'Em Análise'
WHERE NOT EXISTS (SELECT 1 FROM solicitacoes_empresas WHERE nome_empresa = 'Vanguard Logística & Tech');

-- Inclui um perfil demonstrativo associado ao aluno de ID 1, caso ele exista.
INSERT INTO perfil_talento (id_aluno, titulo_profissional, bio, linkedin, github, habilidades, disponivel_mercado)
SELECT 1, 'Desenvolvedor Back-End Júnior', 'Apaixonado por desenvolvimento web, arquitetura de bancos de dados relacionais e automação de rotinas.',
       'https://linkedin.com', 'https://github.com', 'PHP, PostgreSQL, HTML5, CSS3, Git, Metodologias Ágeis', true
WHERE NOT EXISTS (SELECT 1 FROM perfil_talento WHERE id_aluno = 1);
