<?php
ob_start();
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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

// Desativar colaborador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desativar_id'])) {
    try {
        $conn = new PDO(
            "mysql:host=assiduidade.mysql.uhserver.com;dbname=assiduidade;charset=utf8mb4",
            "assiduidade",
            "Grupojb2024@@"
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = $_POST['desativar_id'];
        $stmt = $conn->prepare("UPDATE colaboradores SET ativo = FALSE WHERE id = ?");
        $stmt->execute([$id]);

        echo "<script>alert('Colaborador desativado com sucesso!'); window.location.href='" . basename(__FILE__) . "';</script>";
        exit();
    } catch (PDOException $e) {
        die("Erro ao desativar colaborador.");
    }
}

try {
    $conn = new PDO(
        "mysql:host=assiduidade.mysql.uhserver.com;dbname=assiduidade;charset=utf8mb4",
        "assiduidade",
        "Grupojb2024@@"
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Parâmetros GET
    $turno_filtro = $_GET['turno'] ?? '';
    $busca_nome = $_GET['busca'] ?? '';
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $limite = 10;
    $offset = ($pagina - 1) * $limite;

    // Consulta para turnos distintos
    $stmtTurnos = $conn->prepare("SELECT DISTINCT turno FROM colaboradores WHERE filial = ? AND operacao = ?");
    $stmtTurnos->execute([$filial, $operacao]);
    $turnos = $stmtTurnos->fetchAll(PDO::FETCH_COLUMN);

    // Monta a consulta principal
    $sql = "SELECT * FROM colaboradores WHERE filial = ? AND operacao = ?";
    $params = [$filial, $operacao];

    if (!empty($turno_filtro)) {
        $sql .= " AND turno = ?";
        $params[] = $turno_filtro;
    }

    if (!empty($busca_nome)) {
        $sql .= " AND nome LIKE ?";
        $params[] = "%$busca_nome%";
    }

    $sql .= " ORDER BY nome LIMIT $limite OFFSET $offset";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total para paginação
    $sqlCount = "SELECT COUNT(*) FROM colaboradores WHERE filial = ? AND operacao = ?";
    $paramsCount = [$filial, $operacao];

    if (!empty($turno_filtro)) {
        $sqlCount .= " AND turno = ?";
        $paramsCount[] = $turno_filtro;
    }

    if (!empty($busca_nome)) {
        $sqlCount .= " AND nome LIKE ?";
        $paramsCount[] = "%$busca_nome%";
    }

    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->execute($paramsCount);
    $totalRegistros = $stmtCount->fetchColumn();
    $totalPaginas = ceil($totalRegistros / $limite);

} catch (PDOException $e) {
    error_log("Erro de banco: " . $e->getMessage());
    die("Erro ao acessar o banco.");
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Consultar Colaboradores</title>
    <link rel="shortcut icon" href="./img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="./estilos/consultar.css">
    <link rel="stylesheet" href="./estilos/menu.css">
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
    <h2>Lista de Colaboradores - Filial: <?php echo $filial; ?> | Operação: <?php echo $operacao; ?></h2>

    <form method="get" style="margin-bottom: 20px;">
        <label for="turno">Turno:</label>
        <select name="turno" id="turno" onchange="this.form.submit()">
            <option value="">Todos</option>
            <?php foreach ($turnos as $t): ?>
                <option value="<?= $t ?>" <?= $turno_filtro == $t ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
        </select>

        <label for="busca">Buscar por nome:</label>
        <input type="text" name="busca" value="<?= htmlspecialchars($busca_nome) ?>">
        <button type="submit">Buscar</button>
    </form>

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Turno</th>
                <th>Função</th>
                <th>Data de Cadastro</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($colaboradores as $col): ?>
                <tr>
                    <td><?= htmlspecialchars($col['nome']) ?></td>
                    <td><?= htmlspecialchars($col['turno']) ?></td>
                    <td><?= htmlspecialchars($col['funcao']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($col['data_colaborador'])) ?></td>
                    <td>
                        <?= $col['ativo'] ? 'Ativo' : 'Inativo' ?>
                        <?php if ($col['ativo']): ?>
                            <form method="post" style="display:inline;" onsubmit="return confirmarDesativar();">
                                <input type="hidden" name="desativar_id" value="<?= $col['id'] ?>">
                                <button type="submit">Desativar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="?pagina=<?= $i ?>&turno=<?= urlencode($turno_filtro) ?>&busca=<?= urlencode($busca_nome) ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>

    <script>
        function confirmarDesativar() {
            return confirm("Você tem certeza que deseja desativar este colaborador?");
        }
    </script>
</body>
</html>
