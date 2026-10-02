<?php
// Define as credenciais do PostgreSQL com suporte a variáveis de ambiente ou valores padrão.
$host = getenv('DB_HOST') ?: "192.168.10.52";
$port = getenv('DB_PORT') ?: "5432";
$dbname = getenv('DB_NAME') ?: "hightech_school";
$user = getenv('DB_USER') ?: "admin";
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "admin123";

// Tenta abrir a conexão para que o restante do sistema possa consultar o banco.
try {
    // Cria uma conexão PDO usando o driver PostgreSQL e os dados definidos acima.
    $conexao = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
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
