<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    try {
        $conn = new PDO(
            "mysql:host=assiduidade.mysql.uhserver.com;dbname=assiduidade;charset=utf8mb4",
            "assiduidade",
            "Grupojb2024@@"
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE nome = ? AND senha = ?");
        $stmt->execute([$usuario, $senha]);
        $usuarioDados = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuarioDados) {
            $_SESSION['usuario'] = $usuarioDados['nome'];
            $_SESSION['filial'] = $usuarioDados['filial'];
            $_SESSION['turno'] = $usuarioDados['turno'];
            $_SESSION['operacao'] = $usuarioDados['operacao'];

            header("Location: preenchimento.php");
            exit;
        } else {
            header("Location: index.php?erro=1");
            exit;
        }

    } catch (PDOException $e) {
        echo "Erro na conexão: " . $e->getMessage();
    }
}
?>
