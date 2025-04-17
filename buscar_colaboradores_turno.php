<?php
session_start();
header('Content-Type: application/json');

// ✅ Verifica se a filial está na sessão
if (!isset($_SESSION['filial']) || !isset($_SESSION['operacao'])) {
  echo json_encode(['erro' => 'Filial ou operação não definida na sessão']);
  exit;
}

$filial = $_SESSION['filial'];
$operacao = $_SESSION['operacao'];  // A operação do usuário
$turno = $_GET['turno'] ?? '';

// ✅ Verifica se turno foi enviado
if (empty($turno)) {
  echo json_encode(['erro' => 'Turno não especificado']);
  exit;
}

try {
  // ✅ Conexão com charset para suportar acentuação
  $conn = new PDO(
    "mysql:host=assiduidade.mysql.uhserver.com;dbname=assiduidade;charset=utf8mb4",
    "assiduidade",
    "Grupojb2024@@"
  );
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // ✅ Busca colaboradores pelo turno, filial e operação
  $stmt = $conn->prepare("SELECT id, nome FROM colaboradores WHERE filial = ? AND turno = ? AND operacao = ?");
  $stmt->execute([$filial, $turno, $operacao]);
  $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($colaboradores);
} catch (PDOException $e) {
  echo json_encode(['erro' => 'Erro ao buscar colaboradores: ' . $e->getMessage()]);
}
