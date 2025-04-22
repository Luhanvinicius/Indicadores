<?php
ob_start();
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Verificação de sessão
if (
    empty($_SESSION['usuario']) || 
    empty($_SESSION['filial']) || 
    empty($_SESSION['turno']) || 
    empty($_SESSION['operacao'])
) {
    session_unset();
    session_destroy();
    header("Location: index.php?erro=2");
    exit();
}

// Verifica tempo de inatividade
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: index.php?erro=3");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

$usuario = $_SESSION['usuario'];
$filial = $_SESSION['filial'];
$operacao = $_SESSION['operacao'];

// Conexão com o banco
try {
    $conn = new PDO(
        "mysql:host=assiduidade.mysql.uhserver.com;dbname=assiduidade;charset=utf8mb4",
        "assiduidade",
        "Grupojb2024@@"
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Erro de banco de dados: " . $e->getMessage());
    die("Erro ao conectar ao banco de dados.");
}

// Cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = mb_strtoupper(trim($_POST['nome']), 'UTF-8'); // Garantia de maiúsculo
    $funcao = $_POST['funcao'];
    $turno = $_POST['turno'];
    $setor = ""; // Campo desativado
    $data_colaborador = date('Y-m-d H:i:s');

    $sql = "INSERT INTO colaboradores (nome, filial, turno, operacao, data_colaborador, setor, funcao)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $nome,
        $filial,
        $turno,
        $operacao,
        $data_colaborador,
        $setor,
        $funcao
    ]);

    echo "<script>alert('Colaborador cadastrado com sucesso!'); window.location.href='" . basename(__FILE__) . "';</script>";
    exit();
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Colaborador</title>
    <link rel="stylesheet" href="./estilos/cadastro.css">
    <link rel="stylesheet" href="./estilos/menu.css">
    <style>
        input[name="nome"] {
            text-transform: uppercase;
        }
    </style>
</head>
<body>
<nav class="menu">
  <ul>
    <li><a href="preenchimento.php">Preenchimento</a></li>
    <li><a href="cadastro.php">Cadastro</a></li>
    <li><a href="consultar.php">Consultar Lista</a></li>
    <li><a href="logout.php">Deslogar</a></li>
  </ul>
</nav>

    <h2>Cadastro de Colaborador</h2>
    <form method="post" autocomplete="off">
        <label>Nome:</label><br>
        <input type="text" name="nome" required><br><br>

        <label>Função:</label><br>
        <select name="funcao" required>
            <option value="">Selecione</option>
            <option value="LIDER">Líder</option>
            <option value="AUXILIAR">Auxiliar</option>
            <option value="SERVICOS GERAIS">Serviços Gerais</option>
        </select><br><br>

        <label>Turno:</label><br>
        <select name="turno" required>
            <option value="">Selecione</option>
            <option value="MANHÃ">MANHÃ</option>
            <option value="TARDE">TARDE</option>
            <option value="NOITE">NOITE</option>
        </select><br><br>

        <button type="submit">Cadastrar</button>
    </form>
</body>
</html>
