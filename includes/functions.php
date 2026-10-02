<?php 
// Carrega a conexão PDO e disponibiliza $conexao para as funções deste arquivo.
require_once __DIR__ . '/../database/connect.php';

/*FUNÇÕES DE AUTENTICAÇÃO E USUÁRIOS */

/**
 * Cadastra um novo usuário criptografando a senha com password_hash.
 */
function cadastrarUsuario($conexao, $nome, $email, $senha, $perfil = 'aluno') {
    // Não tenta gravar se a conexão falhou durante a inicialização do sistema.
    if (!$conexao) return false;
    try {
        // Padroniza o e-mail para evitar cadastros duplicados por diferenças de maiúsculas ou espaços.
        $email_normalizado = strtolower(trim($email));
        // Transforma a senha em hash; o valor original não é persistido no banco.
        $senha_hash = password_hash($senha, PASSWORD_BCRYPT);
        // Define os campos a inserir usando parâmetros nomeados para separar SQL de dados.
        $sql = "INSERT INTO usuarios (nome, email, senha, perfil) VALUES (:nome, :email, :senha, :perfil)";
        // Prepara a instrução para que os valores sejam vinculados com segurança.
        $stmt = $conexao->prepare($sql);
        // Associa os dados da conta aos parâmetros correspondentes da instrução.
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":email", $email_normalizado);
        $stmt->bindParam(":senha", $senha_hash);
        $stmt->bindParam(":perfil", $perfil);
        // Executa o INSERT e devolve true ou false conforme o resultado do PDO.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra o erro técnico no log e retorna falha para quem chamou a função.
        error_log("Erro ao cadastrar usuário: " . $e->getMessage());
        return false;
    }
}

/**
 * Autentica um usuário verificando a senha informada com o hash salvo no banco.
 */
function autenticarUsuario($conexao, $email, $senha) {
    // Sem banco disponível, não há como validar as credenciais.
    if (!$conexao) return false;
    try {
        // Busca uma conta pelo e-mail sem diferenciar letras maiúsculas de minúsculas.
        $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE LOWER(email) = LOWER(:email)");
        // Vincula o e-mail recebido ao parâmetro da consulta preparada.
        $stmt->bindParam(":email", $email);
        // Executa a busca e lê o primeiro registro encontrado.
        $stmt->execute();
        $usuario = $stmt->fetch();

        // Só autentica se a conta existir e a senha digitada corresponder ao hash armazenado.
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            return $usuario;
        }
        // Retorna false para credenciais incorretas ou usuário inexistente.
        return false;
    } catch (PDOException $e) {
        // Guarda detalhes do erro no log sem expô-los ao visitante.
        error_log("Erro ao autenticar usuário: " . $e->getMessage());
        return false;
    }
}

/**
 * Procura uma conta pelo e-mail, sem diferenciar letras maiúsculas de minúsculas.
 * Retorna os dados da conta encontrada ou false quando não há resultado ou conexão.
 */
function buscarUsuarioPorEmail($conexao, $email) {
    // Interrompe a busca quando a aplicação não conseguiu conectar ao banco.
    if (!$conexao) return false;
    try {
        // Pesquisa a conta ignorando caixa alta/baixa e mantendo o e-mail parametrizado.
        $stmt = $conexao->prepare("SELECT * FROM usuarios WHERE LOWER(email) = LOWER(:email)");
        $stmt->bindParam(":email", $email);
        // Executa a busca e devolve o registro encontrado ou false se não houver correspondência.
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        // Registra a falha para diagnóstico e sinaliza que a consulta não foi concluída.
        error_log("Erro ao buscar usuário: " . $e->getMessage());
        return false;
    }
}

/* FUNÇÕES DO MÓDULO DE CURSOS*/

/**
 * Lista os cursos cadastrados em ordem crescente de identificador.
 * Retorna uma lista vazia quando a conexão não está disponível ou a consulta falha.
 */
function listarCursos($conexao) {
    // Uma lista vazia permite que as páginas tratem a ausência de conexão sem tentar consultar null.
    if (!$conexao) return [];
    try {
        // Busca todos os cursos em ordem de ID para exibi-los de forma estável.
        $stmt = $conexao->query("SELECT * FROM cursos ORDER BY id ASC");
        // Converte todas as linhas do resultado em uma lista de arrays associativos.
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Registra o problema e devolve uma lista vazia para a camada de apresentação.
        error_log("Erro ao listar cursos: " . $e->getMessage());
        return [];
    }
}

/**
 * Busca um único curso pelo identificador numérico.
 * Retorna os dados do curso ou false se ele não existir ou ocorrer uma falha.
 */
function buscarCursoPorId($conexao, $id) {
    // Sem conexão, não é possível localizar o curso.
    if (!$conexao) return false;
    try {
        // Prepara uma busca restrita ao ID recebido, sem concatená-lo no SQL.
        $stmt = $conexao->prepare("SELECT * FROM cursos WHERE id = :id");
        // Vincula o ID como inteiro para corresponder à chave primária.
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        // Executa a consulta e devolve o curso encontrado, se existir.
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        // Registra a falha e retorna false para distinguir de um curso encontrado.
        error_log("Erro ao buscar curso: " . $e->getMessage());
        return false;
    }
}

/**
 * Insere um curso e converte o status recebido para um booleano do PostgreSQL.
 * Retorna true quando o INSERT é executado e false quando não é possível gravar.
 */
function cadastrarCurso($conexao, $nome, $categoria, $descricao, $carga_horaria, $ativo = true) {
    // Confirma a existência da conexão antes de iniciar o cadastro.
    if (!$conexao) return false;
    try {
        // Especifica os campos do curso e reserva parâmetros para os valores fornecidos.
        $sql = "INSERT INTO cursos (nome, categoria, descricao, carga_horaria, ativo) VALUES (:nome, :categoria, :descricao, :carga_horaria, :ativo)";
        $stmt = $conexao->prepare($sql);
        // Vincula os campos textuais e a carga horária à instrução preparada.
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":categoria", $categoria);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":carga_horaria", $carga_horaria, PDO::PARAM_INT);
        // Normaliza formatos booleanos vindos de formulários e os envia como booleano SQL.
        $stmt->bindValue(":ativo", ($ativo === 'true' || $ativo === true || $ativo === 1 || $ativo === '1'), PDO::PARAM_BOOL);
        // Executa a inserção e informa se ela foi concluída.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra detalhes técnicos sem interromper o fluxo da página chamadora.
        error_log("Erro ao cadastrar curso: " . $e->getMessage());
        return false;
    }
}

/**
 * Atualiza os dados e o status de um curso existente usando parâmetros PDO.
 * Retorna true quando o UPDATE é executado e false quando ocorre uma falha.
 */
function atualizarCurso($conexao, $id, $nome, $categoria, $descricao, $carga_horaria, $ativo) {
    // Evita executar o UPDATE caso a conexão não tenha sido estabelecida.
    if (!$conexao) return false;
    try {
        // Atualiza os campos do curso cuja chave primária corresponde ao ID informado.
        $sql = "UPDATE cursos SET nome = :nome, categoria = :categoria, descricao = :descricao, carga_horaria = :carga_horaria, ativo = :ativo WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        // Vincula os novos dados do curso aos respectivos parâmetros SQL.
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":categoria", $categoria);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":carga_horaria", $carga_horaria, PDO::PARAM_INT);
        // Normaliza o status ativo para o tipo booleano esperado pelo PostgreSQL.
        $stmt->bindValue(":ativo", ($ativo === 'true' || $ativo === true || $ativo === 1 || $ativo === '1'), PDO::PARAM_BOOL);
        // Vincula o identificador inteiro que seleciona o curso a alterar.
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        // Executa o UPDATE e retorna o resultado da operação.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra a falha para diagnóstico e retorna false à página administrativa.
        error_log("Erro ao atualizar curso: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove o curso identificado pelo ID; restrições de chave estrangeira são respeitadas.
 * Retorna true quando a instrução DELETE é executada e false se houver erro.
 */
function excluirCurso($conexao, $id) {
    // Não tenta excluir registros sem uma conexão PDO ativa.
    if (!$conexao) return false;
    try {
        // Prepara a exclusão do curso indicado; as chaves estrangeiras do banco controlam dependências.
        $stmt = $conexao->prepare("DELETE FROM cursos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        // Executa a exclusão e devolve o status para a página chamadora.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra, por exemplo, falhas causadas por restrições do banco.
        error_log("Erro ao excluir curso: " . $e->getMessage());
        return false;
    }
}

/* FUNÇÕES DO MÓDULO DE ALUNOS */

/**
 * Lista todos os alunos em ordem crescente de identificador.
 * Retorna uma lista vazia quando não há conexão ou a consulta falha.
 */
function listarAlunos($conexao) {
    // Retorna coleção vazia quando não existe conexão para consulta.
    if (!$conexao) return [];
    try {
        // Busca os alunos cadastrados ordenando pelo identificador.
        $stmt = $conexao->query("SELECT * FROM alunos ORDER BY id ASC");
        // Devolve todas as linhas como uma lista que as telas podem percorrer.
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Registra a falha e mantém o formato de retorno esperado pelas telas de listagem.
        error_log("Erro ao listar alunos: " . $e->getMessage());
        return [];
    }
}

/**
 * Busca os dados de um aluno pelo ID.
 * Retorna o registro encontrado ou false se não existir ou ocorrer uma falha.
 */
function buscarAlunoPorId($conexao, $id) {
    // Interrompe a busca se não houver conexão com o PostgreSQL.
    if (!$conexao) return false;
    try {
        // Prepara uma consulta limitada a um aluno específico.
        $stmt = $conexao->prepare("SELECT * FROM alunos WHERE id = :id");
        // Informa ao PDO que o identificador é um inteiro.
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        // Executa a busca e devolve uma linha ou false se não existir aluno com esse ID.
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        // Registra o erro e informa à página que a busca falhou.
        error_log("Erro ao buscar aluno: " . $e->getMessage());
        return false;
    }
}

/**
 * Cadastra aluno e transforma CPF ou nascimento vazios em valores SQL NULL.
 * O status recebido é convertido para booleano antes da gravação.
 */
function cadastrarAluno($conexao, $nome, $cpf, $email, $turma, $nasc, $ativo = true) {
    // Confirma que existe uma conexão disponível antes de preparar a gravação.
    if (!$conexao) return false;
    try {
        // Declara a inserção e nomeia cada coluna para associar os valores por parâmetro.
        $sql = "INSERT INTO alunos (nome, cpf, email, turma, nascimento, ativo) VALUES (:nome, :cpf, :email, :turma, :nascimento, :ativo)";
        $stmt = $conexao->prepare($sql);
        // Vincula os dados obrigatórios e opcionais do aluno.
        $stmt->bindParam(":nome", $nome);
        // Converte CPF vazio em null; se houver valor, remove espaços externos.
        $cpf_val = (!empty($cpf) && trim($cpf) !== '') ? trim($cpf) : null;
        $stmt->bindValue(":cpf", $cpf_val, $cpf_val === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":turma", $turma);
        // Trata a data de nascimento como opcional e grava NULL quando o campo ficou vazio.
        $nasc_val = (!empty($nasc) && trim($nasc) !== '') ? trim($nasc) : null;
        $stmt->bindValue(":nascimento", $nasc_val, $nasc_val === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // Converte valores recebidos como texto ou booleano para o tipo booleano do banco.
        $stmt->bindValue(":ativo", ($ativo === 'true' || $ativo === true || $ativo === 1 || $ativo === '1'), PDO::PARAM_BOOL);
        // Executa o INSERT e retorna se a gravação teve sucesso.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra o erro técnico e sinaliza falha sem exibir detalhes do banco ao usuário.
        error_log("Erro ao cadastrar aluno: " . $e->getMessage());
        return false;
    }
}

/**
 * Atualiza os dados de um aluno, mantendo CPF e nascimento como opcionais.
 * Retorna true quando o UPDATE é executado e false se ocorrer uma falha.
 */
function atualizarAluno($conexao, $id, $nome, $cpf, $email, $turma, $nasc, $ativo) {
    // Sem conexão, o registro não pode ser atualizado.
    if (!$conexao) return false;
    try {
        // Define a atualização dos campos do aluno identificado pelo ID.
        $sql = "UPDATE alunos SET nome = :nome, cpf = :cpf, email = :email, turma = :turma, nascimento = :nascimento, ativo = :ativo WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        // Prepara os valores informados; CPF e nascimento recebem tratamento opcional logo abaixo.
        $stmt->bindParam(":nome", $nome);
        // Remove espaços do CPF e representa ausência do documento com SQL NULL.
        $cpf_val = (!empty($cpf) && trim($cpf) !== '') ? trim($cpf) : null;
        $stmt->bindValue(":cpf", $cpf_val, $cpf_val === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":turma", $turma);
        // Remove espaços da data e grava NULL quando o aluno não informou nascimento.
        $nasc_val = (!empty($nasc) && trim($nasc) !== '') ? trim($nasc) : null;
        $stmt->bindValue(":nascimento", $nasc_val, $nasc_val === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // Normaliza o status recebido do formulário para um booleano.
        $stmt->bindValue(":ativo", ($ativo === 'true' || $ativo === true || $ativo === 1 || $ativo === '1'), PDO::PARAM_BOOL);
        // Usa o ID como inteiro para limitar a alteração ao aluno correto.
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        // Executa a atualização e devolve o resultado.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra a causa técnica da falha para consulta nos logs do servidor.
        error_log("Erro ao atualizar aluno: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove um aluno pelo identificador; vínculos dependentes seguem as regras do banco.
 * Retorna true quando a instrução DELETE é executada e false se houver erro.
 */
function excluirAluno($conexao, $id) {
    // Evita tentativa de DELETE quando o banco está indisponível.
    if (!$conexao) return false;
    try {
        // Monta a exclusão restrita ao ID informado; relações dependentes seguem as regras SQL.
        $stmt = $conexao->prepare("DELETE FROM alunos WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        // Executa o DELETE e devolve seu resultado.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra erros, inclusive possíveis restrições de integridade referencial.
        error_log("Erro ao excluir aluno: " . $e->getMessage());
        return false;
    }
}

/* FUNÇÕES DO MÓDULO DE MATRÍCULAS (RELACIONAMENTO RELACIONAL N:N)*/

/**
 * Cria o vínculo entre um aluno e um curso com o status informado.
 * Retorna true quando a matrícula é criada ou false se a conexão/consulta falhar.
 */
function matricularAluno($conexao, $id_aluno, $id_curso, $status = 'Ativa') {
    // Matrículas dependem do banco para validar as chaves estrangeiras de aluno e curso.
    if (!$conexao) return false;
    try {
        // Prepara a inserção que cria a relação entre um aluno e um curso.
        $sql = "INSERT INTO matriculas (id_aluno, id_curso, status) VALUES (:id_aluno, :id_curso, :status)";
        $stmt = $conexao->prepare($sql);
        // Vincula os identificadores como inteiros e define o estado inicial do vínculo.
        $stmt->bindParam(":id_aluno", $id_aluno, PDO::PARAM_INT);
        $stmt->bindParam(":id_curso", $id_curso, PDO::PARAM_INT);
        $stmt->bindParam(":status", $status);
        // Executa a criação da matrícula.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra a falha, como ID inexistente ou violação de restrição do banco.
        error_log("Erro ao matricular aluno: " . $e->getMessage());
        return false;
    }
}

/**
 * Lista cursos ativos em que o aluno ainda não possui uma matrícula ativa.
 */
function listarCursosDisponiveisParaMatricula($conexao, $id_aluno) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare(
            "SELECT c.id, c.nome, c.categoria, c.carga_horaria
             FROM cursos c
             WHERE c.ativo = true
               AND NOT EXISTS (
                   SELECT 1
                   FROM matriculas m
                   WHERE m.id_curso = c.id
                     AND m.id_aluno = :id_aluno
                     AND LOWER(m.status) = 'ativa'
               )
             ORDER BY c.nome ASC"
        );
        $stmt->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao listar cursos disponíveis para matrícula: " . $e->getMessage());
        return false;
    }
}

/**
 * Matricula um aluno em curso ativo, evitando vínculos ativos duplicados.
 */
function matricularAlunoEmCursoAtivo($conexao, $id_aluno, $id_curso) {
    if (!$conexao) return 'error';
    try {
        $conexao->beginTransaction();
        $curso = $conexao->prepare("SELECT ativo FROM cursos WHERE id = :id FOR UPDATE");
        $curso->bindValue(":id", $id_curso, PDO::PARAM_INT);
        $curso->execute();
        $ativo = $curso->fetchColumn();
        if ($ativo === false ||
            !in_array(strtolower((string) $ativo), ['1', 't', 'true', 'yes'], true)) {
            $conexao->rollBack();
            return 'course_unavailable';
        }

        $existente = $conexao->prepare(
            "SELECT id FROM matriculas
             WHERE id_aluno = :id_aluno
               AND id_curso = :id_curso
               AND LOWER(status) = 'ativa'
             LIMIT 1"
        );
        $existente->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
        $existente->bindValue(":id_curso", $id_curso, PDO::PARAM_INT);
        $existente->execute();
        if ($existente->fetchColumn()) {
            $conexao->commit();
            return 'already_enrolled';
        }

        $insert = $conexao->prepare(
            "INSERT INTO matriculas (id_aluno, id_curso, status)
             VALUES (:id_aluno, :id_curso, 'Ativa')"
        );
        $insert->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
        $insert->bindValue(":id_curso", $id_curso, PDO::PARAM_INT);
        $insert->execute();
        $conexao->commit();
        return 'created';
    } catch (PDOException $e) {
        if ($conexao->inTransaction()) $conexao->rollBack();
        error_log("Erro ao matricular aluno em curso ativo: " . $e->getMessage());
        return 'error';
    }
}

/**
 * Lista matrículas junto aos nomes, e-mails e categorias relacionados por JOIN.
 * Retorna uma lista vazia se não houver conexão ou a consulta falhar.
 */
function listarMatriculas($conexao) {
    // Devolve uma coleção vazia se não for possível consultar o banco.
    if (!$conexao) return [];
    try {
        // Combina matrícula, aluno e curso para exibir uma linha completa por vínculo.
        $sql = "SELECT m.id, m.data_matricula, m.status, 
                       a.nome AS aluno_nome, a.email AS aluno_email, a.turma,
                       c.nome AS curso_nome, c.categoria AS curso_categoria
                FROM matriculas m
                JOIN alunos a ON m.id_aluno = a.id
                JOIN cursos c ON m.id_curso = c.id
                ORDER BY m.id DESC";
        // Executa a consulta sem parâmetros externos e lê todas as linhas resultantes.
        $stmt = $conexao->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Registra o erro e devolve uma lista vazia para a tela administrativa.
        error_log("Erro ao listar matrículas: " . $e->getMessage());
        return [];
    }
}

/**
 * Remove uma matrícula pelo seu próprio identificador.
 * Retorna true quando a instrução DELETE é executada e false se houver erro.
 */
function excluirMatricula($conexao, $id) {
    // Interrompe a operação quando a conexão não está disponível.
    if (!$conexao) return false;
    try {
        // Prepara o cancelamento do vínculo pelo identificador da própria matrícula.
        $stmt = $conexao->prepare("DELETE FROM matriculas WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        // Executa a exclusão e informa seu resultado à página chamadora.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra a falha no log do servidor.
        error_log("Erro ao cancelar matrícula: " . $e->getMessage());
        return false;
    }
}

/* FUNÇÕES DO MODULO B2B (DEMANDAS E SERVIÇOS CORPORATIVOS) */

/**
 * Registra uma solicitação corporativa com status inicial "Pendente".
 * Retorna true quando os dados são inseridos e false se a gravação falhar.
 */
function cadastrarSolicitacaoEmpresa($conexao, $nome_empresa, $cnpj, $responsavel, $email, $telefone, $tamanho_equipe, $servico_interesse, $mensagem) {
    // Se não houver conexão, não há como registrar a solicitação recebida pelo portal.
    if (!$conexao) return false;
    try {
        // Prepara o INSERT; o status é definido no SQL para toda nova solicitação iniciar pendente.
        $sql = "INSERT INTO solicitacoes_empresas (nome_empresa, cnpj, responsavel, email, telefone, tamanho_equipe, servico_interesse, mensagem, status) 
                VALUES (:nome_empresa, :cnpj, :responsavel, :email, :telefone, :tamanho_equipe, :servico_interesse, :mensagem, 'Pendente')";
        $stmt = $conexao->prepare($sql);
        // Associa as informações da empresa e do contato aos parâmetros da consulta.
        $stmt->bindParam(":nome_empresa", $nome_empresa);
        $stmt->bindParam(":cnpj", $cnpj);
        $stmt->bindParam(":responsavel", $responsavel);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":telefone", $telefone);
        $stmt->bindParam(":tamanho_equipe", $tamanho_equipe);
        $stmt->bindParam(":servico_interesse", $servico_interesse);
        $stmt->bindParam(":mensagem", $mensagem);
        // Executa a gravação e retorna se foi concluída.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra a exceção para diagnóstico técnico e devolve falha para a página.
        error_log("Erro ao cadastrar solicitação de empresa: " . $e->getMessage());
        return false;
    }
}

/**
 * Lista solicitações recentes, opcionalmente limitadas a um status específico.
 * Retorna uma lista vazia se não houver conexão ou ocorrer uma falha na consulta.
 */
function listarSolicitacoesEmpresas($conexao, $filtro_status = null) {
    // Sem conexão, a função retorna o formato esperado pelas listas: um array vazio.
    if (!$conexao) return [];
    try {
        // Com filtro, prepara consulta parametrizada para retornar apenas o status solicitado.
        if ($filtro_status) {
            $stmt = $conexao->prepare("SELECT * FROM solicitacoes_empresas WHERE status = :status ORDER BY id DESC");
            $stmt->bindParam(":status", $filtro_status);
            $stmt->execute();
        } else {
            // Sem filtro, lista todas as solicitações da mais recente para a mais antiga.
            $stmt = $conexao->query("SELECT * FROM solicitacoes_empresas ORDER BY id DESC");
        }
        // Extrai todas as linhas da consulta, tanto filtrada quanto completa.
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Registra o erro e devolve lista vazia para a tela.
        error_log("Erro ao listar solicitações de empresas: " . $e->getMessage());
        return [];
    }
}

/**
 * Atualiza o status de uma solicitação corporativa.
 * Retorna true quando o UPDATE é executado e false se houver falha.
 */
function atualizarStatusSolicitacaoEmpresa($conexao, $id, $status) {
    // Verifica se o banco está acessível antes de tentar atualizar.
    if (!$conexao) return false;
    try {
        // Prepara a alteração do status para a solicitação identificada pelo ID.
        $stmt = $conexao->prepare("UPDATE solicitacoes_empresas SET status = :status WHERE id = :id");
        // Vincula o novo status e o identificador numérico aos parâmetros SQL.
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        // Executa o UPDATE e retorna seu resultado.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra detalhes técnicos da falha.
        error_log("Erro ao atualizar status da solicitação: " . $e->getMessage());
        return false;
    }
}

/**
 * Exclui uma solicitação corporativa pelo identificador.
 * Retorna true quando a instrução DELETE é executada e false se houver erro.
 */
function excluirSolicitacaoEmpresa($conexao, $id) {
    // Não prossegue quando a conexão PDO não foi criada.
    if (!$conexao) return false;
    try {
        // Prepara a exclusão da solicitação específica, sem inserir o ID diretamente na query.
        $stmt = $conexao->prepare("DELETE FROM solicitacoes_empresas WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        // Executa a operação de exclusão e devolve o resultado ao chamador.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra a exceção para que a falha não fique sem diagnóstico.
        error_log("Erro ao excluir solicitação de empresa: " . $e->getMessage());
        return false;
    }
}

/*FUNÇÕES DO MÓDULO DE VAGAS TECH (OPORTUNIDADES DE EMPREGO) */

/**
 * Lista vagas em ordem decrescente de cadastro, filtrando as ativas por padrão.
 * Passe false em $somente_ativas para incluir vagas inativas.
 */
function listarVagas($conexao, $somente_ativas = true) {
    // Sem banco disponível, não há vagas para retornar.
    if (!$conexao) return [];
    try {
        // Começa selecionando os campos das vagas; o filtro de status é opcional.
        $sql = "SELECT * FROM vagas";
        // Mantém apenas vagas publicadas quando o argumento padrão está ativo.
        if ($somente_ativas) {
            $sql .= " WHERE ativa = true";
        }
        // Coloca as oportunidades mais novas no início da listagem.
        $sql .= " ORDER BY id DESC";
        // Executa a consulta já montada e devolve todas as vagas encontradas.
        $stmt = $conexao->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Registra falhas de consulta e mantém o retorno como lista vazia.
        error_log("Erro ao listar vagas: " . $e->getMessage());
        return [];
    }
}

/**
 * Busca uma vaga pelo identificador.
 * Retorna o registro encontrado ou false se não existir ou ocorrer uma falha.
 */
function buscarVagaPorId($conexao, $id) {
    // Evita consultar quando a conexão não existe.
    if (!$conexao) return false;
    try {
        // Prepara consulta de uma única vaga, selecionada por chave primária.
        $stmt = $conexao->prepare("SELECT * FROM vagas WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        // Executa a busca e devolve o registro ou false se não houver correspondência.
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        // Registra a falha e retorna false como indicador de consulta malsucedida.
        error_log("Erro ao buscar vaga: " . $e->getMessage());
        return false;
    }
}

/**
 * Insere uma vaga e converte o campo de publicação para booleano.
 * Retorna true quando o INSERT é executado e false quando ocorre uma falha.
 */
function cadastrarVaga($conexao, $titulo, $empresa, $modalidade, $tipo_contrato, $localizacao, $salario, $descricao, $requisitos, $contato_candidatura, $ativa = true) {
    // Sem conexão não é possível publicar a vaga.
    if (!$conexao) return false;
    try {
        // Lista os campos que serão gravados e usa parâmetros para todos os valores.
        $sql = "INSERT INTO vagas (titulo, empresa, modalidade, tipo_contrato, localizacao, salario, descricao, requisitos, contato_candidatura, ativa) 
                VALUES (:titulo, :empresa, :modalidade, :tipo_contrato, :localizacao, :salario, :descricao, :requisitos, :contato_candidatura, :ativa)";
        $stmt = $conexao->prepare($sql);
        // Associa os detalhes textuais da vaga aos parâmetros da instrução preparada.
        $stmt->bindParam(":titulo", $titulo);
        $stmt->bindParam(":empresa", $empresa);
        $stmt->bindParam(":modalidade", $modalidade);
        $stmt->bindParam(":tipo_contrato", $tipo_contrato);
        $stmt->bindParam(":localizacao", $localizacao);
        $stmt->bindParam(":salario", $salario);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":requisitos", $requisitos);
        $stmt->bindParam(":contato_candidatura", $contato_candidatura);
        // Aceita booleano ou representações comuns de formulário e converte para booleano PDO.
        $stmt->bindValue(":ativa", ($ativa === 'true' || $ativa === true || $ativa === 1 || $ativa === '1'), PDO::PARAM_BOOL);
        // Executa a inserção da oportunidade.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra a causa da falha para diagnóstico pelo servidor.
        error_log("Erro ao cadastrar vaga: " . $e->getMessage());
        return false;
    }
}

/**
 * Atualiza todos os campos de uma vaga existente, incluindo o status de publicação.
 * Retorna true quando o UPDATE é executado e false se houver falha.
 */
function atualizarVaga($conexao, $id, $titulo, $empresa, $modalidade, $tipo_contrato, $localizacao, $salario, $descricao, $requisitos, $contato_candidatura, $ativa) {
    // Confirma a disponibilidade do banco antes de preparar o UPDATE.
    if (!$conexao) return false;
    try {
        // Atualiza os dados da vaga selecionada pelo ID recebido como parâmetro.
        $sql = "UPDATE vagas SET titulo = :titulo, empresa = :empresa, modalidade = :modalidade, tipo_contrato = :tipo_contrato, 
                localizacao = :localizacao, salario = :salario, descricao = :descricao, requisitos = :requisitos, 
                contato_candidatura = :contato_candidatura, ativa = :ativa WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        // Vincula todos os dados editáveis da vaga à consulta preparada.
        $stmt->bindParam(":titulo", $titulo);
        $stmt->bindParam(":empresa", $empresa);
        $stmt->bindParam(":modalidade", $modalidade);
        $stmt->bindParam(":tipo_contrato", $tipo_contrato);
        $stmt->bindParam(":localizacao", $localizacao);
        $stmt->bindParam(":salario", $salario);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":requisitos", $requisitos);
        $stmt->bindParam(":contato_candidatura", $contato_candidatura);
        // Converte o estado de publicação para booleano, independentemente do formato de origem.
        $stmt->bindValue(":ativa", ($ativa === 'true' || $ativa === true || $ativa === 1 || $ativa === '1'), PDO::PARAM_BOOL);
        // Informa o identificador que determina qual registro deve ser alterado.
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        // Executa a atualização e devolve o sucesso ou a falha da instrução.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra o erro para consulta nos logs do servidor.
        error_log("Erro ao atualizar vaga: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove uma vaga pelo identificador.
 * Retorna true quando a instrução DELETE é executada e false se houver erro.
 */
function excluirVaga($conexao, $id) {
    // Não executa comandos se o banco não estiver conectado.
    if (!$conexao) return false;
    try {
        // Prepara a remoção somente da vaga cujo ID foi informado.
        $stmt = $conexao->prepare("DELETE FROM vagas WHERE id = :id");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        // Executa o DELETE e comunica o resultado à camada que chamou a função.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra a falha técnica.
        error_log("Erro ao excluir vaga: " . $e->getMessage());
        return false;
    }
}

/* FUNÇÕES DE AULAS E PROGRESSO DO ALUNO */

/**
 * Lista cursos e estados para a página de administração de aulas.
 */
function listarCursosParaGestaoAulas($conexao) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->query("SELECT id, nome, ativo FROM cursos ORDER BY nome ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao listar cursos para gestão de aulas: " . $e->getMessage());
        return false;
    }
}

/**
 * Lista as aulas de um curso para a área administrativa, incluindo aulas ocultas.
 */
function listarAulasDoCursoAdmin($conexao, $id_curso) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare(
            "SELECT id, id_curso, titulo, descricao, conteudo, video_url,
                    ordem, obrigatoria, ativa
             FROM aulas
             WHERE id_curso = :id_curso
             ORDER BY ordem ASC, id ASC"
        );
        $stmt->bindValue(":id_curso", $id_curso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao listar aulas para administração: " . $e->getMessage());
        return false;
    }
}

/**
 * Busca uma aula para edição administrativa.
 */
function buscarAulaPorId($conexao, $id_aula) {
    if (!$conexao) return null;
    try {
        $stmt = $conexao->prepare("SELECT * FROM aulas WHERE id = :id");
        $stmt->bindValue(":id", $id_aula, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erro ao buscar aula: " . $e->getMessage());
        return null;
    }
}

/**
 * Cadastra uma aula e identifica conflito na ordem dentro do mesmo curso.
 */
function cadastrarAula($conexao, $id_curso, $titulo, $descricao, $conteudo, $video_url, $ordem, $obrigatoria) {
    if (!$conexao) return 'error';
    try {
        $stmt = $conexao->prepare(
            "INSERT INTO aulas (id_curso, titulo, descricao, conteudo, video_url, ordem, obrigatoria)
             VALUES (:id_curso, :titulo, :descricao, :conteudo, :video_url, :ordem, :obrigatoria)"
        );
        $stmt->bindValue(":id_curso", $id_curso, PDO::PARAM_INT);
        $stmt->bindValue(":titulo", $titulo);
        $stmt->bindValue(":descricao", $descricao);
        $stmt->bindValue(":conteudo", $conteudo);
        $stmt->bindValue(":video_url", $video_url);
        $stmt->bindValue(":ordem", $ordem, PDO::PARAM_INT);
        $stmt->bindValue(":obrigatoria", $obrigatoria, PDO::PARAM_BOOL);
        $stmt->execute();
        return 'created';
    } catch (PDOException $e) {
        if ($e->getCode() === '23505') return 'order_conflict';
        error_log("Erro ao cadastrar aula: " . $e->getMessage());
        return 'error';
    }
}

/**
 * Atualiza os dados de uma aula e identifica conflito na ordem dentro do curso.
 */
function atualizarAula($conexao, $id_aula, $id_curso, $titulo, $descricao, $conteudo, $video_url, $ordem, $obrigatoria) {
    if (!$conexao) return 'error';
    try {
        $stmt = $conexao->prepare(
            "UPDATE aulas
             SET id_curso = :id_curso,
                 titulo = :titulo,
                 descricao = :descricao,
                 conteudo = :conteudo,
                 video_url = :video_url,
                 ordem = :ordem,
                 obrigatoria = :obrigatoria
             WHERE id = :id"
        );
        $stmt->bindValue(":id_curso", $id_curso, PDO::PARAM_INT);
        $stmt->bindValue(":titulo", $titulo);
        $stmt->bindValue(":descricao", $descricao);
        $stmt->bindValue(":conteudo", $conteudo);
        $stmt->bindValue(":video_url", $video_url);
        $stmt->bindValue(":ordem", $ordem, PDO::PARAM_INT);
        $stmt->bindValue(":obrigatoria", $obrigatoria, PDO::PARAM_BOOL);
        $stmt->bindValue(":id", $id_aula, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? 'updated' : 'unchanged';
    } catch (PDOException $e) {
        if ($e->getCode() === '23505') return 'order_conflict';
        error_log("Erro ao atualizar aula: " . $e->getMessage());
        return 'error';
    }
}

/**
 * Publica ou oculta uma aula sem apagar o progresso dos alunos.
 */
function definirAulaAtiva($conexao, $id_aula, $ativa) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare("UPDATE aulas SET ativa = :ativa WHERE id = :id");
        $stmt->bindValue(":ativa", $ativa, PDO::PARAM_BOOL);
        $stmt->bindValue(":id", $id_aula, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Erro ao alterar publicação da aula: " . $e->getMessage());
        return false;
    }
}

/**
 * Recupera a avaliação de um curso. Retorna false quando ainda não foi configurada.
 */
function obterProvaDoCurso($conexao, $id_curso, $somente_ativa = false) {
    if (!$conexao) return null;
    try {
        $sql = "SELECT id, id_curso, nota_minima, max_tentativas, ativa
                FROM provas
                WHERE id_curso = :id_curso";
        if ($somente_ativa) $sql .= " AND ativa = true";
        $sql .= " LIMIT 1";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(":id_curso", $id_curso, PDO::PARAM_INT);
        $stmt->execute();
        $prova = $stmt->fetch();
        return $prova ?: false;
    } catch (PDOException $e) {
        error_log("Erro ao buscar prova do curso: " . $e->getMessage());
        return null;
    }
}

/**
 * Salva a nota mínima e o limite de tentativas da prova de um curso.
 */
function salvarProvaDoCurso($conexao, $id_curso, $nota_minima, $max_tentativas) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare(
            "INSERT INTO provas (id_curso, nota_minima, max_tentativas)
             VALUES (:id_curso, :nota_minima, :max_tentativas)
             ON CONFLICT (id_curso) DO UPDATE
             SET nota_minima = EXCLUDED.nota_minima,
                 max_tentativas = EXCLUDED.max_tentativas
             RETURNING id"
        );
        $stmt->bindValue(":id_curso", $id_curso, PDO::PARAM_INT);
        $stmt->bindValue(":nota_minima", $nota_minima);
        $stmt->bindValue(":max_tentativas", $max_tentativas, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erro ao salvar configuração da prova: " . $e->getMessage());
        return false;
    }
}

/**
 * Publica ou desativa a prova sem remover questões nem resultados já registrados.
 */
function definirProvaAtiva($conexao, $id_prova, $ativa) {
    if (!$conexao) return 'error';
    try {
        if ($ativa) {
            $validacao = $conexao->prepare(
                "SELECT COUNT(*) AS total_questoes,
                        COUNT(*) FILTER (
                            WHERE total_alternativas <> 4
                               OR total_corretas <> 1
                        ) AS questoes_invalidas
                 FROM (
                    SELECT q.id,
                           COUNT(a.id) AS total_alternativas,
                           COUNT(a.id) FILTER (WHERE a.correta = true) AS total_corretas
                    FROM questoes_prova q
                    LEFT JOIN alternativas_prova a ON a.id_questao = q.id
                    WHERE q.id_prova = :id_prova
                    GROUP BY q.id
                 ) questoes"
            );
            $validacao->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
            $validacao->execute();
            $resultado_validacao = $validacao->fetch();
            if (!$resultado_validacao || (int) $resultado_validacao['total_questoes'] === 0 ||
                (int) $resultado_validacao['questoes_invalidas'] > 0) {
                return 'invalid_questions';
            }
        }
        $stmt = $conexao->prepare("UPDATE provas SET ativa = :ativa WHERE id = :id");
        $stmt->bindValue(":ativa", $ativa, PDO::PARAM_BOOL);
        $stmt->bindValue(":id", $id_prova, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0 ? ($ativa ? 'published' : 'unpublished') : 'unchanged';
    } catch (PDOException $e) {
        error_log("Erro ao alterar publicação da prova: " . $e->getMessage());
        return 'error';
    }
}

/**
 * Lista questões e alternativas para o formulário administrativo.
 */
function listarQuestoesProvaAdmin($conexao, $id_prova) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare(
            "SELECT q.id, q.enunciado, q.ordem,
                    a.id AS id_alternativa, a.texto, a.correta
             FROM questoes_prova q
             LEFT JOIN alternativas_prova a ON a.id_questao = q.id
             WHERE q.id_prova = :id_prova
             ORDER BY q.ordem, q.id, a.id"
        );
        $stmt->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
        $stmt->execute();
        $questoes = [];
        foreach ($stmt->fetchAll() as $linha) {
            $id = (int) $linha['id'];
            if (!isset($questoes[$id])) {
                $questoes[$id] = [
                    'id' => $id,
                    'enunciado' => $linha['enunciado'],
                    'ordem' => (int) $linha['ordem'],
                    'alternativas' => []
                ];
            }
            if ($linha['id_alternativa'] !== null) {
                $questoes[$id]['alternativas'][] = [
                    'id' => (int) $linha['id_alternativa'],
                    'texto' => $linha['texto'],
                    'correta' => in_array(
                        strtolower((string) $linha['correta']),
                        ['1', 't', 'true', 'yes'],
                        true
                    )
                ];
            }
        }
        return array_values($questoes);
    } catch (PDOException $e) {
        error_log("Erro ao listar questões administrativas: " . $e->getMessage());
        return false;
    }
}

/**
 * Cadastra ou edita questão e suas quatro alternativas em uma transação.
 */
function salvarQuestaoProva($conexao, $id_prova, $id_questao, $enunciado, $ordem, $alternativas, $indice_correto) {
    if (!$conexao) return 'error';
    try {
        $conexao->beginTransaction();
        if ($id_questao === null) {
            $stmt = $conexao->prepare(
                "INSERT INTO questoes_prova (id_prova, enunciado, ordem)
                 VALUES (:id_prova, :enunciado, :ordem)
                 RETURNING id"
            );
            $stmt->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
            $stmt->bindValue(":enunciado", $enunciado);
            $stmt->bindValue(":ordem", $ordem, PDO::PARAM_INT);
            $stmt->execute();
            $id_questao = (int) $stmt->fetchColumn();
            $resultado = 'created';
        } else {
            $stmt = $conexao->prepare(
                "UPDATE questoes_prova
                 SET enunciado = :enunciado, ordem = :ordem
                 WHERE id = :id_questao AND id_prova = :id_prova"
            );
            $stmt->bindValue(":enunciado", $enunciado);
            $stmt->bindValue(":ordem", $ordem, PDO::PARAM_INT);
            $stmt->bindValue(":id_questao", $id_questao, PDO::PARAM_INT);
            $stmt->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() === 0) {
                $check = $conexao->prepare(
                    "SELECT 1 FROM questoes_prova WHERE id = :id AND id_prova = :id_prova"
                );
                $check->bindValue(":id", $id_questao, PDO::PARAM_INT);
                $check->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
                $check->execute();
                if (!$check->fetchColumn()) {
                    $conexao->rollBack();
                    return 'not_found';
                }
            }
            $delete = $conexao->prepare("DELETE FROM alternativas_prova WHERE id_questao = :id_questao");
            $delete->bindValue(":id_questao", $id_questao, PDO::PARAM_INT);
            $delete->execute();
            $resultado = 'updated';
        }

        $insert = $conexao->prepare(
            "INSERT INTO alternativas_prova (id_questao, texto, correta)
             VALUES (:id_questao, :texto, :correta)"
        );
        foreach ($alternativas as $indice => $texto) {
            $insert->bindValue(":id_questao", $id_questao, PDO::PARAM_INT);
            $insert->bindValue(":texto", $texto);
            $insert->bindValue(":correta", ((int) $indice === (int) $indice_correto), PDO::PARAM_BOOL);
            $insert->execute();
        }
        $conexao->commit();
        return $resultado;
    } catch (PDOException $e) {
        if ($conexao->inTransaction()) $conexao->rollBack();
        if ($e->getCode() === '23505') return 'order_conflict';
        error_log("Erro ao salvar questão da prova: " . $e->getMessage());
        return 'error';
    }
}

/**
 * Remove uma questão da prova indicada.
 */
function excluirQuestaoProva($conexao, $id_prova, $id_questao) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare(
            "DELETE FROM questoes_prova WHERE id = :id AND id_prova = :id_prova"
        );
        $stmt->bindValue(":id", $id_questao, PDO::PARAM_INT);
        $stmt->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Erro ao excluir questão da prova: " . $e->getMessage());
        return false;
    }
}

/**
 * Lista prova e alternativas para o aluno sem retornar a resposta correta.
 */
function listarQuestoesProvaAluno($conexao, $id_prova) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare(
            "SELECT q.id, q.enunciado, q.ordem,
                    a.id AS id_alternativa, a.texto
             FROM questoes_prova q
             JOIN alternativas_prova a ON a.id_questao = q.id
             WHERE q.id_prova = :id_prova
             ORDER BY q.ordem, q.id, a.id"
        );
        $stmt->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
        $stmt->execute();
        $questoes = [];
        foreach ($stmt->fetchAll() as $linha) {
            $id = (int) $linha['id'];
            if (!isset($questoes[$id])) {
                $questoes[$id] = [
                    'id' => $id,
                    'enunciado' => $linha['enunciado'],
                    'ordem' => (int) $linha['ordem'],
                    'alternativas' => []
                ];
            }
            $questoes[$id]['alternativas'][] = [
                'id' => (int) $linha['id_alternativa'],
                'texto' => $linha['texto']
            ];
        }
        return array_values($questoes);
    } catch (PDOException $e) {
        error_log("Erro ao listar questões para o aluno: " . $e->getMessage());
        return false;
    }
}

/**
 * Submete e corrige prova no servidor, validando matrícula, pré-requisitos e tentativas.
 */
function enviarTentativaProva($conexao, $id_matricula, $id_prova, $respostas) {
    if (!$conexao) return ['status' => 'error'];
    if (!is_array($respostas)) return ['status' => 'invalid_answers'];
    try {
        $conexao->beginTransaction();
        $stmt = $conexao->prepare(
            "SELECT m.id, m.id_curso, p.nota_minima, p.max_tentativas
             FROM matriculas m
             JOIN provas p ON p.id = :id_prova AND p.id_curso = m.id_curso
             WHERE m.id = :id_matricula AND m.status = 'Ativa' AND p.ativa = true
             FOR UPDATE OF m"
        );
        $stmt->bindValue(":id_matricula", $id_matricula, PDO::PARAM_INT);
        $stmt->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
        $stmt->execute();
        $contexto = $stmt->fetch();
        if (!$contexto) {
            $conexao->rollBack();
            return ['status' => 'unavailable'];
        }

        $certificado = $conexao->prepare(
            "SELECT codigo FROM certificados WHERE id_matricula = :id_matricula"
        );
        $certificado->bindValue(":id_matricula", $id_matricula, PDO::PARAM_INT);
        $certificado->execute();
        if ($certificado->fetchColumn()) {
            $conexao->rollBack();
            return ['status' => 'already_passed'];
        }

        $pendentes = $conexao->prepare(
            "SELECT COUNT(*)
             FROM aulas a
             LEFT JOIN progresso_aulas pa
                    ON pa.id_aula = a.id AND pa.id_matricula = :id_matricula
             WHERE a.id_curso = :id_curso
               AND a.ativa = true
               AND a.obrigatoria = true
               AND pa.id IS NULL"
        );
        $pendentes->bindValue(":id_matricula", $id_matricula, PDO::PARAM_INT);
        $pendentes->bindValue(":id_curso", $contexto['id_curso'], PDO::PARAM_INT);
        $pendentes->execute();
        if ((int) $pendentes->fetchColumn() > 0) {
            $conexao->rollBack();
            return ['status' => 'lessons_pending'];
        }

        $questoes = $conexao->prepare(
            "SELECT q.id AS id_questao, a.id AS id_alternativa, a.correta
             FROM questoes_prova q
             JOIN alternativas_prova a ON a.id_questao = q.id
             WHERE q.id_prova = :id_prova
             ORDER BY q.ordem, q.id, a.id"
        );
        $questoes->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
        $questoes->execute();
        $gabarito = [];
        $corretas_por_questao = [];
        foreach ($questoes->fetchAll() as $linha) {
            $id_questao = (int) $linha['id_questao'];
            if (!isset($gabarito[$id_questao])) $gabarito[$id_questao] = [];
            if (in_array(strtolower((string) $linha['correta']), ['1', 't', 'true', 'yes'], true)) {
                $gabarito[$id_questao]['correta'] = (int) $linha['id_alternativa'];
                $corretas_por_questao[$id_questao] = ($corretas_por_questao[$id_questao] ?? 0) + 1;
            }
            $gabarito[$id_questao]['alternativas'][] = (int) $linha['id_alternativa'];
        }
        if (!$gabarito || count($respostas) !== count($gabarito)) {
            $conexao->rollBack();
            return ['status' => 'invalid_answers'];
        }
        $respostas_normalizadas = [];
        foreach ($gabarito as $id_questao => $dados) {
            $resposta = isset($respostas[$id_questao]) && is_scalar($respostas[$id_questao])
                ? filter_var($respostas[$id_questao], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
                : false;
            if (count($dados['alternativas']) !== 4 ||
                ($corretas_por_questao[$id_questao] ?? 0) !== 1 ||
                !isset($dados['correta']) ||
                $resposta === false ||
                !in_array($resposta, $dados['alternativas'], true)) {
                $conexao->rollBack();
                return ['status' => 'invalid_answers'];
            }
            $respostas_normalizadas[$id_questao] = $resposta;
        }

        $count = $conexao->prepare(
            "SELECT COUNT(*) FROM tentativas_prova
             WHERE id_matricula = :id_matricula AND id_prova = :id_prova"
        );
        $count->bindValue(":id_matricula", $id_matricula, PDO::PARAM_INT);
        $count->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
        $count->execute();
        $tentativas = (int) $count->fetchColumn();
        if ($tentativas >= (int) $contexto['max_tentativas']) {
            $conexao->rollBack();
            return ['status' => 'attempts_exhausted'];
        }

        $acertos = 0;
        $respostas_registradas = [];
        foreach ($gabarito as $id_questao => $dados) {
            $resposta = $respostas_normalizadas[$id_questao];
            if ($resposta === $dados['correta']) $acertos++;
            $respostas_registradas[$id_questao] = $resposta;
        }
        $nota = round(($acertos / count($gabarito)) * 100, 2);
        $aprovada = $nota >= (float) $contexto['nota_minima'];
        $respostas_json = json_encode($respostas_registradas);
        if ($respostas_json === false) {
            $conexao->rollBack();
            return ['status' => 'error'];
        }

        $insert = $conexao->prepare(
            "INSERT INTO tentativas_prova
                (id_matricula, id_prova, nota, aprovada, respostas)
             VALUES (:id_matricula, :id_prova, :nota, :aprovada, CAST(:respostas AS JSONB))
             RETURNING id"
        );
        $insert->bindValue(":id_matricula", $id_matricula, PDO::PARAM_INT);
        $insert->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
        $insert->bindValue(":nota", $nota);
        $insert->bindValue(":aprovada", $aprovada, PDO::PARAM_BOOL);
        $insert->bindValue(":respostas", $respostas_json);
        $insert->execute();

        $codigo = null;
        if ($aprovada) {
            $codigo = sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff)
            );
            $emitir = $conexao->prepare(
                "INSERT INTO certificados (id_matricula, id_prova, codigo)
                 VALUES (:id_matricula, :id_prova, :codigo)"
            );
            $emitir->bindValue(":id_matricula", $id_matricula, PDO::PARAM_INT);
            $emitir->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
            $emitir->bindValue(":codigo", $codigo);
            $emitir->execute();
        }
        $conexao->commit();
        return [
            'status' => $aprovada ? 'passed' : 'failed',
            'nota' => $nota,
            'nota_minima' => (float) $contexto['nota_minima'],
            'tentativas_restantes' => max(
                0,
                (int) $contexto['max_tentativas'] - $tentativas - 1
            ),
            'codigo_certificado' => $codigo
        ];
    } catch (PDOException $e) {
        if ($conexao->inTransaction()) $conexao->rollBack();
        error_log("Erro ao registrar tentativa de prova: " . $e->getMessage());
        return ['status' => 'error'];
    }
}

/**
 * Lista tentativas recentes de uma matrícula na prova.
 */
function listarTentativasProvaAluno($conexao, $id_matricula, $id_prova) {
    if (!$conexao) return false;
    try {
        $stmt = $conexao->prepare(
            "SELECT nota, aprovada, realizada_em
             FROM tentativas_prova
             WHERE id_matricula = :id_matricula AND id_prova = :id_prova
             ORDER BY realizada_em DESC, id DESC"
        );
        $stmt->bindValue(":id_matricula", $id_matricula, PDO::PARAM_INT);
        $stmt->bindValue(":id_prova", $id_prova, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao listar tentativas de prova: " . $e->getMessage());
        return false;
    }
}

/**
 * Conta aulas obrigatórias ainda pendentes na matrícula.
 */
function contarAulasObrigatoriasPendentes($conexao, $id_matricula, $id_curso) {
    if (!$conexao) return null;
    try {
        $stmt = $conexao->prepare(
            "SELECT COUNT(*)
             FROM aulas a
             LEFT JOIN progresso_aulas p
                    ON p.id_aula = a.id AND p.id_matricula = :id_matricula
             WHERE a.id_curso = :id_curso
               AND a.ativa = true
               AND a.obrigatoria = true
               AND p.id IS NULL"
        );
        $stmt->bindValue(":id_matricula", $id_matricula, PDO::PARAM_INT);
        $stmt->bindValue(":id_curso", $id_curso, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erro ao contar aulas obrigatórias pendentes: " . $e->getMessage());
        return null;
    }
}

/**
 * Busca o certificado associado a uma matrícula.
 */
function obterCertificadoDaMatricula($conexao, $id_matricula) {
    if (!$conexao) return null;
    try {
        $stmt = $conexao->prepare(
            "SELECT ce.id, ce.codigo, ce.emitido_em, c.nome AS curso_nome,
                    c.carga_horaria, a.nome AS aluno_nome
             FROM certificados ce
             JOIN matriculas m ON m.id = ce.id_matricula
             JOIN cursos c ON c.id = m.id_curso
             JOIN alunos a ON a.id = m.id_aluno
             WHERE ce.id_matricula = :id_matricula"
        );
        $stmt->bindValue(":id_matricula", $id_matricula, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch() ?: false;
    } catch (PDOException $e) {
        error_log("Erro ao buscar certificado da matrícula: " . $e->getMessage());
        return null;
    }
}

/**
 * Localiza certificado por código para verificação pública.
 */
function validarCertificadoPorCodigo($conexao, $codigo) {
    if (!$conexao) return null;
    try {
        $stmt = $conexao->prepare(
            "SELECT ce.codigo, ce.emitido_em, c.nome AS curso_nome,
                    c.carga_horaria, a.nome AS aluno_nome
             FROM certificados ce
             JOIN matriculas m ON m.id = ce.id_matricula
             JOIN cursos c ON c.id = m.id_curso
             JOIN alunos a ON a.id = m.id_aluno
             WHERE ce.codigo = :codigo"
        );
        $stmt->bindValue(":codigo", $codigo);
        $stmt->execute();
        return $stmt->fetch() ?: false;
    } catch (PDOException $e) {
        error_log("Erro ao validar certificado: " . $e->getMessage());
        return null;
    }
}

/**
 * Busca a matrícula ativa do aluno no curso solicitado.
 */
function obterMatriculaAtivaAlunoCurso($conexao, $id_aluno, $id_curso) {
    if (!$conexao) return false;
    try {
        $sql = "SELECT m.id AS matricula_id, c.id AS curso_id, c.nome AS curso_nome,
                       c.categoria, c.descricao, c.carga_horaria
                FROM matriculas m
                JOIN cursos c ON c.id = m.id_curso
                WHERE m.id_aluno = :id_aluno
                  AND m.id_curso = :id_curso
                  AND m.status = 'Ativa'
                ORDER BY m.id DESC
                LIMIT 1";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(":id_aluno", $id_aluno, PDO::PARAM_INT);
        $stmt->bindValue(":id_curso", $id_curso, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erro ao buscar matrícula ativa: " . $e->getMessage());
        return null;
    }
}

/**
 * Lista as aulas ativas do curso e indica quais foram concluídas na matrícula.
 */
function listarAulasDaMatricula($conexao, $id_matricula) {
    if (!$conexao) return false;
    try {
        $sql = "SELECT a.id, a.id_curso, a.titulo, a.descricao, a.conteudo,
                       a.video_url, a.ordem, a.obrigatoria,
                       CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END AS concluida
                FROM aulas a
                JOIN matriculas m ON m.id_curso = a.id_curso
                LEFT JOIN progresso_aulas p
                    ON p.id_aula = a.id AND p.id_matricula = m.id
                WHERE m.id = :id_matricula
                  AND m.status = 'Ativa'
                  AND a.ativa = true
                ORDER BY a.ordem ASC, a.id ASC";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(":id_matricula", $id_matricula, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao listar aulas da matrícula: " . $e->getMessage());
        return false;
    }
}

/**
 * Marca uma aula como concluída somente se ela pertencer à matrícula ativa.
 */
function concluirAulaDaMatricula($conexao, $id_matricula, $id_aula) {
    if (!$conexao) return false;
    try {
        $sql = "INSERT INTO progresso_aulas (id_matricula, id_aula)
                SELECT m.id, a.id
                FROM matriculas m
                JOIN aulas a ON a.id = :id_aula
                            AND a.id_curso = m.id_curso
                            AND a.ativa = true
                WHERE m.id = :id_matricula
                  AND m.status = 'Ativa'
                ON CONFLICT (id_matricula, id_aula) DO NOTHING";
        $stmt = $conexao->prepare($sql);
        $stmt->bindValue(":id_matricula", $id_matricula, PDO::PARAM_INT);
        $stmt->bindValue(":id_aula", $id_aula, PDO::PARAM_INT);
        $stmt->execute();

        $check = $conexao->prepare(
            "SELECT 1
             FROM progresso_aulas p
             JOIN matriculas m ON m.id = p.id_matricula
             JOIN aulas a ON a.id = p.id_aula AND a.id_curso = m.id_curso
             WHERE p.id_matricula = :id_matricula
               AND p.id_aula = :id_aula
               AND m.status = 'Ativa'
               AND a.ativa = true"
        );
        $check->bindValue(":id_matricula", $id_matricula, PDO::PARAM_INT);
        $check->bindValue(":id_aula", $id_aula, PDO::PARAM_INT);
        $check->execute();
        return (bool) $check->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erro ao concluir aula: " . $e->getMessage());
        return null;
    }
}

/* FUNÇÕES DO ALUNO & BANCO DE TALENTOS */

/**
 * Procura o registro acadêmico associado ao e-mail informado.
 * A comparação ignora diferenças entre letras maiúsculas e minúsculas.
 */
function buscarAlunoPorEmail($conexao, $email) {
    // Sem conexão, não é possível associar o e-mail a um registro acadêmico.
    if (!$conexao) return false;
    try {
        // Compara os e-mails sem diferenciar caixa alta/baixa, usando valor parametrizado.
        $stmt = $conexao->prepare("SELECT * FROM alunos WHERE LOWER(email) = LOWER(:email)");
        $stmt->bindParam(":email", $email);
        // Executa a consulta e devolve o aluno encontrado ou false.
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        // Registra a falha de consulta no log do servidor.
        error_log("Erro ao buscar aluno por email: " . $e->getMessage());
        return false;
    }
}

/**
 * Lista os cursos e os dados de matrícula de um aluno específico.
 * Retorna uma lista vazia se o aluno não tiver cursos ou a consulta falhar.
 */
function listarCursosDoAluno($conexao, $id_aluno) {
    // Retorna lista vazia se não for possível acessar o banco.
    if (!$conexao) return [];
    try {
        // Junta matrícula e curso para retornar os detalhes de cada curso do aluno informado.
        $sql = "SELECT m.id AS matricula_id, m.data_matricula, m.status AS matricula_status,
                       c.id AS curso_id, c.nome AS curso_nome, c.categoria, c.descricao, c.carga_horaria,
                       (SELECT COUNT(*) FROM aulas a
                        WHERE a.id_curso = c.id AND a.ativa = true) AS aulas_total,
                       (SELECT COUNT(*) FROM progresso_aulas p
                        JOIN aulas a ON a.id = p.id_aula AND a.ativa = true
                        WHERE p.id_matricula = m.id AND a.id_curso = c.id) AS aulas_concluidas,
                       (SELECT COUNT(*) FROM aulas a
                        WHERE a.id_curso = c.id AND a.ativa = true AND a.obrigatoria = true) AS aulas_obrigatorias_total,
                       (SELECT COUNT(*) FROM progresso_aulas p
                        JOIN aulas a ON a.id = p.id_aula AND a.ativa = true AND a.obrigatoria = true
                        WHERE p.id_matricula = m.id AND a.id_curso = c.id) AS aulas_obrigatorias_concluidas,
                       (SELECT p.id FROM provas p WHERE p.id_curso = c.id AND p.ativa = true LIMIT 1) AS prova_id,
                       (SELECT p.nota_minima FROM provas p WHERE p.id_curso = c.id AND p.ativa = true LIMIT 1) AS prova_nota_minima,
                       (SELECT p.max_tentativas FROM provas p WHERE p.id_curso = c.id AND p.ativa = true LIMIT 1) AS prova_max_tentativas,
                       (SELECT COUNT(*) FROM tentativas_prova tp
                        WHERE tp.id_matricula = m.id
                          AND tp.id_prova = (SELECT p.id FROM provas p WHERE p.id_curso = c.id AND p.ativa = true LIMIT 1)) AS prova_tentativas_usadas,
                       (SELECT ce.codigo FROM certificados ce WHERE ce.id_matricula = m.id LIMIT 1) AS certificado_codigo
                FROM matriculas m
                JOIN cursos c ON m.id_curso = c.id
                WHERE m.id_aluno = :id_aluno
                ORDER BY m.id DESC";
        // Prepara a consulta e vincula o ID do aluno como inteiro.
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":id_aluno", $id_aluno, PDO::PARAM_INT);
        // Executa a busca e coleta todos os cursos associados às matrículas.
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Registra a falha e preserva o formato de retorno de lista.
        error_log("Erro ao listar cursos do aluno: " . $e->getMessage());
        return [];
    }
}

/**
 * Recupera o perfil de talento associado a um aluno.
 * Retorna o registro ou false quando não existe perfil ou ocorre uma falha.
 */
function obterPerfilTalentoPorAluno($conexao, $id_aluno) {
    // Sem conexão não é possível verificar se o aluno já possui perfil.
    if (!$conexao) return false;
    try {
        // Prepara a busca usando a coluna única que associa perfil e aluno.
        $stmt = $conexao->prepare("SELECT * FROM perfil_talento WHERE id_aluno = :id_aluno");
        $stmt->bindParam(":id_aluno", $id_aluno, PDO::PARAM_INT);
        // Executa e devolve o perfil correspondente ou false se não houver registro.
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        // Registra qualquer erro de banco ao consultar o perfil.
        error_log("Erro ao buscar perfil de talento: " . $e->getMessage());
        return false;
    }
}

/**
 * Cria ou atualiza o perfil de talento do aluno conforme já exista um registro.
 * Atualizações também registram o horário atual em atualizado_em.
 */
function salvarPerfilTalento($conexao, $id_aluno, $titulo_profissional, $bio, $linkedin, $github, $habilidades, $disponivel_mercado = true) {
    // Não tenta gravar informações se o PDO não estiver disponível.
    if (!$conexao) return false;
    try {
        // Verifica se já há um perfil para decidir entre UPDATE e INSERT.
        $existente = obterPerfilTalentoPorAluno($conexao, $id_aluno);
        // Normaliza os formatos possíveis do campo de disponibilidade para um booleano.
        $disponivel = ($disponivel_mercado === 'true' || $disponivel_mercado === true || $disponivel_mercado === 1 || $disponivel_mercado === '1');
        
        // Perfil existente: atualiza os dados e registra o momento da edição.
        if ($existente) {
            $sql = "UPDATE perfil_talento 
                    SET titulo_profissional = :titulo, bio = :bio, linkedin = :linkedin, github = :github, 
                        habilidades = :habilidades, disponivel_mercado = :disponivel, atualizado_em = CURRENT_TIMESTAMP
                    WHERE id_aluno = :id_aluno";
        } else {
            // Sem perfil anterior: prepara um novo registro associado ao aluno.
            $sql = "INSERT INTO perfil_talento (id_aluno, titulo_profissional, bio, linkedin, github, habilidades, disponivel_mercado)
                    VALUES (:id_aluno, :titulo, :bio, :linkedin, :github, :habilidades, :disponivel)";
        }
        // Prepara a operação escolhida e vincula o aluno e os dados profissionais.
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":id_aluno", $id_aluno, PDO::PARAM_INT);
        $stmt->bindParam(":titulo", $titulo_profissional);
        $stmt->bindParam(":bio", $bio);
        $stmt->bindParam(":linkedin", $linkedin);
        $stmt->bindParam(":github", $github);
        $stmt->bindParam(":habilidades", $habilidades);
        // Envia a disponibilidade ao PostgreSQL com o tipo booleano correto.
        $stmt->bindValue(":disponivel", $disponivel, PDO::PARAM_BOOL);
        // Executa INSERT ou UPDATE e retorna o resultado.
        return $stmt->execute();
    } catch (PDOException $e) {
        // Registra problemas técnicos para diagnóstico.
        error_log("Erro ao salvar perfil de talento: " . $e->getMessage());
        return false;
    }
}

/**
 * Lista perfis disponíveis de alunos ativos para exibição na vitrine pública.
 * Os resultados são ordenados pela atualização mais recente.
 */
function listarTalentosPublicos($conexao) {
    // Se o banco estiver indisponível, não há resultados públicos a exibir.
    if (!$conexao) return [];
    try {
        // Junta cada perfil ao aluno e seleciona apenas perfis visíveis de alunos ativos.
        $sql = "SELECT p.*, a.nome AS aluno_nome, a.email AS aluno_email, a.turma
                FROM perfil_talento p
                JOIN alunos a ON p.id_aluno = a.id
                WHERE p.disponivel_mercado = true AND a.ativo = true
                ORDER BY p.atualizado_em DESC";
        // Executa a consulta ordenada do perfil mais recentemente atualizado ao mais antigo.
        $stmt = $conexao->query($sql);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // Registra o erro e retorna lista vazia para a vitrine.
        error_log("Erro ao listar talentos públicos: " . $e->getMessage());
        return [];
    }
}

/* FUNÇÕES DE DASHBOARD & MÉTRICAS*/

/**
 * Calcula os totais usados no painel e inicializa as métricas com zero.
 * Se a conexão estiver indisponível, devolve os valores iniciais.
 */
function obterMetricasDashboard($conexao) {
    // Define as chaves e os valores padrão para que a resposta tenha sempre o mesmo formato.
    $metricas = [
        'total_alunos' => 0,
        'total_cursos' => 0,
        'total_matriculas' => 0,
        'total_demandas' => 0,
        'demandas_pendentes' => 0,
        'total_vagas' => 0
    ];
    // Se não há conexão, devolve os totais zerados em vez de executar consultas inválidas.
    if (!$conexao) return $metricas;

    try {
        // Conta alunos e cursos ativos para os indicadores do painel.
        $metricas['total_alunos'] = (int) $conexao->query("SELECT COUNT(*) FROM alunos WHERE ativo = true")->fetchColumn();
        $metricas['total_cursos'] = (int) $conexao->query("SELECT COUNT(*) FROM cursos WHERE ativo = true")->fetchColumn();
        // Conta matrículas ativas, todas as demandas e as demandas ainda pendentes.
        $metricas['total_matriculas'] = (int) $conexao->query("SELECT COUNT(*) FROM matriculas WHERE status = 'Ativa'")->fetchColumn();
        $metricas['total_demandas'] = (int) $conexao->query("SELECT COUNT(*) FROM solicitacoes_empresas")->fetchColumn();
        $metricas['demandas_pendentes'] = (int) $conexao->query("SELECT COUNT(*) FROM solicitacoes_empresas WHERE status = 'Pendente'")->fetchColumn();
        // Conta somente as vagas publicadas como ativas.
        $metricas['total_vagas'] = (int) $conexao->query("SELECT COUNT(*) FROM vagas WHERE ativa = true")->fetchColumn();
    } catch (PDOException $e) {
        // Registra falha; os valores já calculados permanecem disponíveis no array.
        error_log("Erro ao obter métricas: " . $e->getMessage());
    }
    // Devolve o conjunto de métricas calculado ou parcialmente preenchido.
    return $metricas;
}

/**
 * Valida a data de nascimento garantindo idade entre 14 e 100 anos, sem permitir datas futuras.
 * Retorna a data no formato Y-m-d, null se estiver vazia (campo opcional), ou false em caso de erro.
 */
function validarDataNascimento($data_string, &$mensagem_erro = null) {
    $data_limpa = trim((string)$data_string);
    if ($data_limpa === '') {
        return null;
    }

    $d = DateTime::createFromFormat('Y-m-d', $data_limpa);
    $hoje = new DateTime('today');

    if (!$d || $d->format('Y-m-d') !== $data_limpa) {
        $mensagem_erro = 'Por favor, informe uma data de nascimento válida no formato dd/mm/aaaa.';
        return false;
    }

    if ($d > $hoje) {
        $mensagem_erro = 'A data de nascimento não pode estar no futuro.';
        return false;
    }

    $idade = $hoje->diff($d)->y;

    if ($idade < 14) {
        $mensagem_erro = 'O aluno deve ter no mínimo 14 anos para se matricular (idade calculada: ' . $idade . ' anos).';
        return false;
    }

    if ($idade > 100) {
        $mensagem_erro = 'Por favor, informe uma data de nascimento válida (idade máxima permitida: 100 anos).';
        return false;
    }

    return $data_limpa;
}

/**
 * Normaliza URLs externas (LinkedIn, GitHub, etc.) garantindo o protocolo https:// caso o usuário tenha omitido.
 */
function normalizarUrlExterna($url) {
    $url_limpa = trim((string)$url);
    if ($url_limpa === '') {
        return '';
    }
    if (!preg_match('/^https?:\/\//i', $url_limpa)) {
        return 'https://' . $url_limpa;
    }
    return $url_limpa;
}

/**
 * Normaliza e formata o CPF. Se informado apenas com 11 dígitos, formata automaticamente como 000.000.000-00.
 * Limita a no máximo 14 caracteres para respeitar a coluna VARCHAR(14) do banco de dados.
 */
function formatarOuLimparCpf($cpf) {
    if (empty($cpf) || trim((string)$cpf) === '') {
        return null;
    }
    $nums = preg_replace('/\D/', '', (string)$cpf);
    if (strlen($nums) === 11) {
        return vsprintf('%s%s%s.%s%s%s.%s%s%s-%s%s', str_split($nums));
    }
    return substr(trim((string)$cpf), 0, 14);
}
?>
