<?php
session_start();
date_default_timezone_set('America/Fortaleza');

if (!isset($_SESSION['usuario'])) {
  echo "Usuário não logado!";
  exit;
}

$usuario = $_SESSION['usuario'];
$filial = $_SESSION['filial'];
$operacao = $_SESSION['operacao']; // Supondo que a operação esteja na sessão
$data_presenca = $_POST['data_presenca'];
$datahora_preenchimento = date('Y-m-d H:i:s');

try {
  $conn = new PDO("mysql:host=assiduidade.mysql.uhserver.com;dbname=assiduidade", "assiduidade", "Grupojb2024@@");
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $ids = $_POST['colaborador_id'];

  foreach ($ids as $id) {
    $is_agregado = isset($_POST["agregado_$id"]);
    $nome_agregado = $is_agregado ? ($_POST["nome_$id"] ?? 'Agregado sem nome') : null;

    $falta = isset($_POST["falta_$id"]) ? 1 : 0;
    $folga = isset($_POST["folga_$id"]) ? 1 : 0;

    $hora_entrada = ($falta || $folga) ? null : $_POST["hora_entrada_$id"];
    $hora_saida = ($falta || $folga) ? null : $_POST["hora_saida_$id"];
    $observacoes = $_POST["observacoes_$id"] ?? '';

    if ($is_agregado) {
      // Inserir presença de agregado com nome manual
      $stmt = $conn->prepare("
        INSERT INTO presencas (
          nome_agregado,
          data_presenca,
          falta,
          folga,
          hora_entrada,
          hora_saida,
          datahora_preenchimento,
          observacoes,
          filial,
          operacao
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([
        $nome_agregado,
        $data_presenca,
        $falta,
        $folga,
        $hora_entrada,
        $hora_saida,
        $datahora_preenchimento,
        $observacoes,
        $filial,
        $operacao // Incluindo a operação aqui
      ]);
    } else {
      // Inserir presença de colaborador com ID
      $stmt = $conn->prepare("
        INSERT INTO presencas (
          colaborador_id,
          data_presenca,
          falta,
          folga,
          hora_entrada,
          hora_saida,
          datahora_preenchimento,
          observacoes,
          filial,
          operacao
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([
        $id,
        $data_presenca,
        $falta,
        $folga,
        $hora_entrada,
        $hora_saida,
        $datahora_preenchimento,
        $observacoes,
        $filial,
        $operacao // Incluindo a operação aqui
      ]);
    }
  }

  echo "<script>alert('Presenças registradas com sucesso!'); window.location.href='preenchimento.php';</script>";

} catch (PDOException $e) {
  echo "Erro ao salvar presença: " . $e->getMessage();
}
?>
