<?php
session_start();  // Inicia a sessão

// Destruir todos os dados da sessão
session_unset();  // Remove todas as variáveis de sessão
session_destroy();  // Destroi a sessão

// Limpar cookies de sessão, caso existam
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

// Redireciona para a página de login
header("Location: index.php");
exit();
?>
