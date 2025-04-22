<?php
session_start();

// Verifica se a variável de sessão do usuário está definida
if (!isset($_SESSION['usuario_id'])) {  // Substitua 'usuario_id' pelo nome da sua variável de sessão
    header("Location: index.php");
    exit();  // Garante que o código pare aqui e não continue
}
?>
