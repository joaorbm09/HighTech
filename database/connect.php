<?php 
$host = "192.168.10.52";
$dbname = "hightech_school";
$user = "admin";
$pass = "admin123";

try {
    $conexao = new PDO(
        "pgsql:host=$host;dbname=$dbname",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    // Tratamento de falha de conexão silenciosa no layout
    error_log("Erro de Conexão com o Banco: " . $e->getMessage());
    $conexao = null;
}
?>
