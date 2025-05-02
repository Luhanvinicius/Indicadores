<?php
session_start();
header('Content-Type: application/json');

// ✅ Verifica se a filial e operação estão na sessão
if (!isset($_SESSION['filial']) || !isset($_SESSION['operacao'])) {
  echo json_encode(['erro' => 'Filial ou operação não definida na sessão']);
  exit;
}

$filial = $_SESSION['filial'];
$operacao = $_SESSION['operacao'];  // A operação do usuário
$turno = $_GET['turno'] ?? '';
$operacao_filtro = $_GET['operacao'] ?? '';

// ✅ Verifica se o turno foi enviado
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

  // ✅ Verificação dos parâmetros recebidos
  error_log("Filial: $filial, Operação: $operacao, Turno: $turno, Operação Filtro: $operacao_filtro");

  // ✅ Construção da consulta SQL com base nos filtros
  if (!empty($operacao_filtro)) {
    // Caso o filtro de operação tenha sido enviado
    $stmt = $conn->prepare("SELECT id, nome FROM colaboradores WHERE filial = ? AND turno = ? AND operacao = ?");
    $stmt->execute([$filial, $turno, $operacao_filtro]);
  } else {
    // Caso não tenha sido enviado o filtro de operação
    $stmt = $conn->prepare("SELECT id, nome FROM colaboradores WHERE filial = ? AND turno = ?");
    $stmt->execute([$filial, $turno]);
  }

  // ✅ Verifica se a consulta retornou dados
  $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($colaboradores)) {
    echo json_encode(['erro' => 'Nenhum colaborador encontrado para os filtros informados']);
  } else {
    echo json_encode($colaboradores);
  }

} catch (PDOException $e) {
  // ✅ Captura erros e retorna mensagem
  echo json_encode(['erro' => 'Erro ao buscar colaboradores: ' . $e->getMessage()]);
  error_log("Erro ao buscar colaboradores: " . $e->getMessage());
}
?>
