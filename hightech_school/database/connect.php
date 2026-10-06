<?php
// Lê a configuração do banco do ambiente e usa estes valores como padrão local.
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'hightech_school';
$user = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASS') ?: '';

try {
    // Cria a conexão PDO com PostgreSQL e configura erros como exceções.
    $conexao = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    // Registra detalhes técnicos no log, sem expor informações do banco na página.
    error_log('Erro de conexão com o PostgreSQL: ' . $e->getMessage());
    $conexao = null;
}
