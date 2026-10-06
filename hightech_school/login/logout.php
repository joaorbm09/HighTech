<?php
// Limpa os dados da sessão e encerra o acesso do usuário.
session_start();
$_SESSION = [];
session_destroy();

header('Location: ../aplicacao.php');
exit;
