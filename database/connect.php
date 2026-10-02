<?php
// Define o endereço do servidor PostgreSQL usado pela aplicação.
$host = "192.168.10.52";
// Define o nome do banco de dados que contém as tabelas do sistema.
$dbname = "hightech_school";
// Informa o usuário que será usado na autenticação com o PostgreSQL.
$user = "admin";
// Informa a senha correspondente ao usuário do banco de dados.
$pass = "admin123";

// Tenta abrir a conexão para que o restante do sistema possa consultar o banco.
try {
    // Cria uma conexão PDO usando o driver PostgreSQL e os dados definidos acima.
    $conexao = new PDO(
        "pgsql:host=$host;dbname=$dbname",
        $user,
        $pass,
        [
            // Faz o PDO lançar exceções quando uma operação no banco falha.
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Retorna os resultados das consultas como arrays associativos.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
// Captura falhas de conexão e evita que consultas sejam executadas sem um PDO válido.
} catch (PDOException $e) {
    // Registra os detalhes técnicos no log do servidor sem exibir credenciais ou erro ao visitante.
    error_log("Erro de Conexão com o Banco: " . $e->getMessage());
    // Informa ao restante da aplicação que a conexão não está disponível.
    $conexao = null;
}
?>
