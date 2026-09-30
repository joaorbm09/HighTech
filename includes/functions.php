<?php 
require_once __DIR__ . '/../database/connect.php';

/*FUNÇÕES DE AUTENTICAÇÃO E USUÁRIOS */

/**
 * Cadastra um novo usuário criptografando a senha com password_hash.
 */
function cadastrarUsuario($conexao, $nome, $email, $senha, $perfil = 'aluno') {
    if (!$conexao) return false;
    try {
        $email_normalizado = strtolower(trim($email));
        $senha_hash = password_hash($senha, PASSWORD_BCRYPT);
        $sql = "INSERT INTO usuarios (nome, email, senha, perfil) VALUES (:nome, :email, :senha, :perfil)";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":email", $email_normalizado);
        $stmt->bindParam(":senha", $senha_hash);
        $stmt->bindParam(":perfil", $perfil);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao cadastrar usuário: " . $e->getMessage());
        return false;
    }
}

/**
 * Autentica um usuário verificando a senha informada com o hash salvo no banco.
 */
function autenticarUsuario($conexao, $email, $senha) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE LOWER(email) = LOWER(:email)");
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            return $usuario;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Erro ao autenticar usuário: " . $e->getMessage());
        return false;
    }
}

function buscarUsuarioPorEmail($conexao, $email) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE LOWER(email) = LOWER(:email)");
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erro ao buscar usuário: " . $e->getMessage());
        return false;
    }
}

/* FUNÇÕES DO MÓDULO DE CURSOS*/

function listarCursos($conexao) {
    if (!$conexao) return [];
    try {
        $stmt = $conexao->query("SELECT * FROM cursos ORDER BY id ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao listar cursos: " . $e->getMessage());
        return [];
    }
}

function buscarCursoPorId($conexao, $id) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("SELECT * FROM cursos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erro ao buscar curso: " . $e->getMessage());
        return false;
    }
}

function cadastrarCurso($conexao, $nome, $categoria, $descricao, $carga_horaria, $ativo = true) {
    if (!$conexao) return false;
    try {
        $sql = "INSERT INTO cursos (nome, categoria, descricao, carga_horaria, ativo) VALUES (:nome, :categoria, :descricao, :carga_horaria, :ativo)";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":categoria", $categoria);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":carga_horaria", $carga_horaria, PDO::PARAM_INT);
        $stmt->bindValue(":ativo", ($ativo === 'true' || $ativo === true || $ativo === 1 || $ativo === '1'), PDO::PARAM_BOOL);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao cadastrar curso: " . $e->getMessage());
        return false;
    }
}

function atualizarCurso($conexao, $id, $nome, $categoria, $descricao, $carga_horaria, $ativo) {
    if (!$conexao) return false;
    try {
        $sql = "UPDATE cursos SET nome = :nome, categoria = :categoria, descricao = :descricao, carga_horaria = :carga_horaria, ativo = :ativo WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":categoria", $categoria);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":carga_horaria", $carga_horaria, PDO::PARAM_INT);
        $stmt->bindValue(":ativo", ($ativo === 'true' || $ativo === true || $ativo === 1 || $ativo === '1'), PDO::PARAM_BOOL);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar curso: " . $e->getMessage());
        return false;
    }
}

function excluirCurso($conexao, $id) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("DELETE FROM cursos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao excluir curso: " . $e->getMessage());
        return false;
    }
}

/* FUNÇÕES DO MÓDULO DE ALUNOS */

function listarAlunos($conexao) {
    if (!$conexao) return [];
    try {
        $stmt = $conexao->query("SELECT * FROM alunos ORDER BY id ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao listar alunos: " . $e->getMessage());
        return [];
    }
}

function buscarAlunoPorId($conexao, $id) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("SELECT * FROM alunos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erro ao buscar aluno: " . $e->getMessage());
        return false;
    }
}

function cadastrarAluno($conexao, $nome, $cpf, $email, $turma, $nasc, $ativo = true) {
    if (!$conexao) return false;
    try {
        $sql = "INSERT INTO alunos (nome, cpf, email, turma, nascimento, ativo) VALUES (:nome, :cpf, :email, :turma, :nascimento, :ativo)";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":nome", $nome);
        $cpf_val = (!empty($cpf) && trim($cpf) !== '') ? trim($cpf) : null;
        $stmt->bindValue(":cpf", $cpf_val, $cpf_val === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":turma", $turma);
        $nasc_val = (!empty($nasc) && trim($nasc) !== '') ? trim($nasc) : null;
        $stmt->bindValue(":nascimento", $nasc_val, $nasc_val === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(":ativo", ($ativo === 'true' || $ativo === true || $ativo === 1 || $ativo === '1'), PDO::PARAM_BOOL);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao cadastrar aluno: " . $e->getMessage());
        return false;
    }
}

function atualizarAluno($conexao, $id, $nome, $cpf, $email, $turma, $nasc, $ativo) {
    if (!$conexao) return false;
    try {
        $sql = "UPDATE alunos SET nome = :nome, cpf = :cpf, email = :email, turma = :turma, nascimento = :nascimento, ativo = :ativo WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":nome", $nome);
        $cpf_val = (!empty($cpf) && trim($cpf) !== '') ? trim($cpf) : null;
        $stmt->bindValue(":cpf", $cpf_val, $cpf_val === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":turma", $turma);
        $nasc_val = (!empty($nasc) && trim($nasc) !== '') ? trim($nasc) : null;
        $stmt->bindValue(":nascimento", $nasc_val, $nasc_val === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(":ativo", ($ativo === 'true' || $ativo === true || $ativo === 1 || $ativo === '1'), PDO::PARAM_BOOL);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar aluno: " . $e->getMessage());
        return false;
    }
}

function excluirAluno($conexao, $id) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("DELETE FROM alunos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao excluir aluno: " . $e->getMessage());
        return false;
    }
}

/* FUNÇÕES DO MÓDULO DE MATRÍCULAS (RELACIONAMENTO RELACIONAL N:N)*/

function matricularAluno($conexao, $id_aluno, $id_curso, $status = 'Ativa') {
    if (!$conexao) return false;
    try {
        $sql = "INSERT INTO matriculas (id_aluno, id_curso, status) VALUES (:id_aluno, :id_curso, :status)";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":id_aluno", $id_aluno, PDO::PARAM_INT);
        $stmt->bindParam(":id_curso", $id_curso, PDO::PARAM_INT);
        $stmt->bindParam(":status", $status);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao matricular aluno: " . $e->getMessage());
        return false;
    }
}

function listarMatriculas($conexao) {
    if (!$conexao) return [];
    try {
        $sql = "SELECT m.id, m.data_matricula, m.status, 
                       a.nome AS aluno_nome, a.email AS aluno_email, a.turma,
                       c.nome AS curso_nome, c.categoria AS curso_categoria
                FROM matriculas m
                JOIN alunos a ON m.id_aluno = a.id
                JOIN cursos c ON m.id_curso = c.id
                ORDER BY m.id DESC";
        $stmt = $conexao->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao listar matrículas: " . $e->getMessage());
        return [];
    }
}

function excluirMatricula($conexao, $id) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("DELETE FROM matriculas WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao cancelar matrícula: " . $e->getMessage());
        return false;
    }
}

/* FUNÇÕES DO MODULO B2B (DEMANDAS E SERVIÇOS CORPORATIVOS) */

function cadastrarSolicitacaoEmpresa($conexao, $nome_empresa, $cnpj, $responsavel, $email, $telefone, $tamanho_equipe, $servico_interesse, $mensagem) {
    if (!$conexao) return false;
    try {
        $sql = "INSERT INTO solicitacoes_empresas (nome_empresa, cnpj, responsavel, email, telefone, tamanho_equipe, servico_interesse, mensagem, status) 
                VALUES (:nome_empresa, :cnpj, :responsavel, :email, :telefone, :tamanho_equipe, :servico_interesse, :mensagem, 'Pendente')";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":nome_empresa", $nome_empresa);
        $stmt->bindParam(":cnpj", $cnpj);
        $stmt->bindParam(":responsavel", $responsavel);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":telefone", $telefone);
        $stmt->bindParam(":tamanho_equipe", $tamanho_equipe);
        $stmt->bindParam(":servico_interesse", $servico_interesse);
        $stmt->bindParam(":mensagem", $mensagem);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao cadastrar solicitação de empresa: " . $e->getMessage());
        return false;
    }
}

function listarSolicitacoesEmpresas($conexao, $filtro_status = null) {
    if (!$conexao) return [];
    try {
        if ($filtro_status) {
            $stmt = $conexao->prepare("SELECT * FROM solicitacoes_empresas WHERE status = :status ORDER BY id DESC");
            $stmt->bindParam(":status", $filtro_status);
            $stmt->execute();
        } else {
            $stmt = $conexao->query("SELECT * FROM solicitacoes_empresas ORDER BY id DESC");
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao listar solicitações de empresas: " . $e->getMessage());
        return [];
    }
}

function atualizarStatusSolicitacaoEmpresa($conexao, $id, $status) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("UPDATE solicitacoes_empresas SET status = :status WHERE id = :id");
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar status da solicitação: " . $e->getMessage());
        return false;
    }
}

function excluirSolicitacaoEmpresa($conexao, $id) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("DELETE FROM solicitacoes_empresas WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao excluir solicitação de empresa: " . $e->getMessage());
        return false;
    }
}

/*FUNÇÕES DO MÓDULO DE VAGAS TECH (OPORTUNIDADES DE EMPREGO) */

function listarVagas($conexao, $somente_ativas = true) {
    if (!$conexao) return [];
    try {
        $sql = "SELECT * FROM vagas";
        if ($somente_ativas) {
            $sql .= " WHERE ativa = true";
        }
        $sql .= " ORDER BY id DESC";
        $stmt = $conexao->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao listar vagas: " . $e->getMessage());
        return [];
    }
}

function buscarVagaPorId($conexao, $id) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("SELECT * FROM vagas WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erro ao buscar vaga: " . $e->getMessage());
        return false;
    }
}

function cadastrarVaga($conexao, $titulo, $empresa, $modalidade, $tipo_contrato, $localizacao, $salario, $descricao, $requisitos, $contato_candidatura, $ativa = true) {
    if (!$conexao) return false;
    try {
        $sql = "INSERT INTO vagas (titulo, empresa, modalidade, tipo_contrato, localizacao, salario, descricao, requisitos, contato_candidatura, ativa) 
                VALUES (:titulo, :empresa, :modalidade, :tipo_contrato, :localizacao, :salario, :descricao, :requisitos, :contato_candidatura, :ativa)";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":titulo", $titulo);
        $stmt->bindParam(":empresa", $empresa);
        $stmt->bindParam(":modalidade", $modalidade);
        $stmt->bindParam(":tipo_contrato", $tipo_contrato);
        $stmt->bindParam(":localizacao", $localizacao);
        $stmt->bindParam(":salario", $salario);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":requisitos", $requisitos);
        $stmt->bindParam(":contato_candidatura", $contato_candidatura);
        $stmt->bindValue(":ativa", ($ativa === 'true' || $ativa === true || $ativa === 1 || $ativa === '1'), PDO::PARAM_BOOL);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao cadastrar vaga: " . $e->getMessage());
        return false;
    }
}

function atualizarVaga($conexao, $id, $titulo, $empresa, $modalidade, $tipo_contrato, $localizacao, $salario, $descricao, $requisitos, $contato_candidatura, $ativa) {
    if (!$conexao) return false;
    try {
        $sql = "UPDATE vagas SET titulo = :titulo, empresa = :empresa, modalidade = :modalidade, tipo_contrato = :tipo_contrato, 
                localizacao = :localizacao, salario = :salario, descricao = :descricao, requisitos = :requisitos, 
                contato_candidatura = :contato_candidatura, ativa = :ativa WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":titulo", $titulo);
        $stmt->bindParam(":empresa", $empresa);
        $stmt->bindParam(":modalidade", $modalidade);
        $stmt->bindParam(":tipo_contrato", $tipo_contrato);
        $stmt->bindParam(":localizacao", $localizacao);
        $stmt->bindParam(":salario", $salario);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":requisitos", $requisitos);
        $stmt->bindParam(":contato_candidatura", $contato_candidatura);
        $stmt->bindValue(":ativa", ($ativa === 'true' || $ativa === true || $ativa === 1 || $ativa === '1'), PDO::PARAM_BOOL);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar vaga: " . $e->getMessage());
        return false;
    }
}

function excluirVaga($conexao, $id) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("DELETE FROM vagas WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao excluir vaga: " . $e->getMessage());
        return false;
    }
}

/*FUNÇÕES DO ALUNO & BANCO DE TALENTOS */

function buscarAlunoPorEmail($conexao, $email) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("SELECT * FROM alunos WHERE LOWER(email) = LOWER(:email)");
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erro ao buscar aluno por email: " . $e->getMessage());
        return false;
    }
}

function listarCursosDoAluno($conexao, $id_aluno) {
    if (!$conexao) return [];
    try {
        $sql = "SELECT m.id AS matricula_id, m.data_matricula, m.status AS matricula_status,
                       c.id AS curso_id, c.nome AS curso_nome, c.categoria, c.descricao, c.carga_horaria
                FROM matriculas m
                JOIN cursos c ON m.id_curso = c.id
                WHERE m.id_aluno = :id_aluno
                ORDER BY m.id DESC";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":id_aluno", $id_aluno, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao listar cursos do aluno: " . $e->getMessage());
        return [];
    }
}

function obterPerfilTalentoPorAluno($conexao, $id_aluno) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("SELECT * FROM perfil_talento WHERE id_aluno = :id_aluno");
        $stmt->bindParam(":id_aluno", $id_aluno, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erro ao buscar perfil de talento: " . $e->getMessage());
        return false;
    }
}

function salvarPerfilTalento($conexao, $id_aluno, $titulo_profissional, $bio, $linkedin, $github, $habilidades, $disponivel_mercado = true) {
    if (!$conexao) return false;
    try {
        $existente = obterPerfilTalentoPorAluno($conexao, $id_aluno);
        $disponivel = ($disponivel_mercado === 'true' || $disponivel_mercado === true || $disponivel_mercado === 1 || $disponivel_mercado === '1');
        
        if ($existente) {
            $sql = "UPDATE perfil_talento 
                    SET titulo_profissional = :titulo, bio = :bio, linkedin = :linkedin, github = :github, 
                        habilidades = :habilidades, disponivel_mercado = :disponivel, atualizado_em = CURRENT_TIMESTAMP
                    WHERE id_aluno = :id_aluno";
        } else {
            $sql = "INSERT INTO perfil_talento (id_aluno, titulo_profissional, bio, linkedin, github, habilidades, disponivel_mercado)
                    VALUES (:id_aluno, :titulo, :bio, :linkedin, :github, :habilidades, :disponivel)";
        }
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":id_aluno", $id_aluno, PDO::PARAM_INT);
        $stmt->bindParam(":titulo", $titulo_profissional);
        $stmt->bindParam(":bio", $bio);
        $stmt->bindParam(":linkedin", $linkedin);
        $stmt->bindParam(":github", $github);
        $stmt->bindParam(":habilidades", $habilidades);
        $stmt->bindValue(":disponivel", $disponivel, PDO::PARAM_BOOL);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Erro ao salvar perfil de talento: " . $e->getMessage());
        return false;
    }
}

function listarTalentosPublicos($conexao) {
    if (!$conexao) return [];
    try {
        $sql = "SELECT p.*, a.nome AS aluno_nome, a.email AS aluno_email, a.turma
                FROM perfil_talento p
                JOIN alunos a ON p.id_aluno = a.id
                WHERE p.disponivel_mercado = true AND a.ativo = true
                ORDER BY p.atualizado_em DESC";
        $stmt = $conexao->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao listar talentos públicos: " . $e->getMessage());
        return [];
    }
}

/* FUNÇÕES DE DASHBOARD & MÉTRICAS*/

function obterMetricasDashboard($conexao) {
    $metricas = [
        'total_alunos' => 0,
        'total_cursos' => 0,
        'total_matriculas' => 0,
        'total_demandas' => 0,
        'demandas_pendentes' => 0,
        'total_vagas' => 0
    ];
    if (!$conexao) return $metricas;

    try {
        $metricas['total_alunos'] = (int) $conexao->query("SELECT COUNT(*) FROM alunos WHERE ativo = true")->fetchColumn();
        $metricas['total_cursos'] = (int) $conexao->query("SELECT COUNT(*) FROM cursos WHERE ativo = true")->fetchColumn();
        $metricas['total_matriculas'] = (int) $conexao->query("SELECT COUNT(*) FROM matriculas WHERE status = 'Ativa'")->fetchColumn();
        $metricas['total_demandas'] = (int) $conexao->query("SELECT COUNT(*) FROM solicitacoes_empresas")->fetchColumn();
        $metricas['demandas_pendentes'] = (int) $conexao->query("SELECT COUNT(*) FROM solicitacoes_empresas WHERE status = 'Pendente'")->fetchColumn();
        $metricas['total_vagas'] = (int) $conexao->query("SELECT COUNT(*) FROM vagas WHERE ativa = true")->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erro ao obter métricas: " . $e->getMessage());
    }
    return $metricas;
}
?>
