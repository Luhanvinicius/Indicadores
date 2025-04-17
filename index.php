<?php
// Ativa a exibição de erros (útil para testes)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verifica se existe parâmetro de erro na URL
$erro = isset($_GET['erro']) && $_GET['erro'] == 1;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Login - Controle de Assiduidade</title>
  <link rel="stylesheet" href="./estilos/style.css">
  <style>
    .erro {
      color: red;
      font-weight: bold;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <img src="./img/logo.png" alt="Logo da empresa" class="logo">

    <h2>Controle de Assiduidade</h2>

    <?php if ($erro): ?>
      <p class="erro">Usuário ou senha inválidos.</p>
    <?php endif; ?>

    <form action="login.php" method="POST">
      <input type="text" name="usuario" placeholder="Usuário" required>
      <input type="password" name="senha" placeholder="Senha" required>
      <button type="submit">Entrar</button>
    </form>
  </div>
</body>
</html>
