<?php
session_start();
date_default_timezone_set('America/Fortaleza');

if (!isset($_SESSION['usuario'])) {
  echo "Usuário não logado!";
  exit;
}

$usuario = $_SESSION['usuario'];
$filial = $_SESSION['filial'];
$operacao = $_SESSION['operacao'];
$data_presenca = $_POST['data_presenca'];
$datahora_preenchimento = date('Y-m-d H:i:s');

try {
  $conn = new PDO(
    "mysql:host=assiduidade.mysql.uhserver.com;dbname=assiduidade;charset=utf8mb4",
    "assiduidade",
    "Grupojb2024@@",
    [
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]
  );
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $ids = $_POST['colaborador_id'];

  foreach ($ids as $id) {
    $is_agregado = isset($_POST["agregado_$id"]);
    $nome_agregado = $is_agregado ? ($_POST["nome_$id"] ?? 'Agregado sem nome') : null;

    if ($nome_agregado) {
      $nome_agregado = trim($nome_agregado);
    }

    $falta = isset($_POST["falta_$id"]) ? 1 : 0;
    $folga = isset($_POST["folga_$id"]) ? 1 : 0;

    $hora_entrada = ($falta || $folga) ? null : $_POST["hora_entrada_$id"];
    $hora_saida = ($falta || $folga) ? null : $_POST["hora_saida_$id"];
    $observacoes = $_POST["observacoes_$id"] ?? '';

    $id_referenciado = null; // por padrão

    if ($is_agregado) {
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
          operacao,
          id_referenciado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        $operacao,
        $id_referenciado
      ]);
    } else {
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
          operacao,
          id_referenciado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        $operacao,
        $id_referenciado
      ]);
    }
  }

  // 💡 Aqui entra a lógica dos suplentes:
  if (!empty($_POST['suplente_de'])) {
    foreach ($_POST['suplente_de'] as $id_referenciado) {
      $nome_suplente = trim($_POST["nome_suplente_$id_referenciado"] ?? '');
      $entrada_suplente = $_POST["hora_entrada_suplente_$id_referenciado"] ?? null;
      $saida_suplente = $_POST["hora_saida_suplente_$id_referenciado"] ?? null;
      $obs_suplente = $_POST["observacoes_suplente_$id_referenciado"] ?? '';

      if ($nome_suplente && $entrada_suplente && $saida_suplente) {
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
            operacao,
            id_referenciado
          ) VALUES (?, ?, 0, 0, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
          $nome_suplente,
          $data_presenca,
          $entrada_suplente,
          $saida_suplente,
          $datahora_preenchimento,
          $obs_suplente,
          $filial,
          $operacao,
          $id_referenciado
        ]);
      }
    }
  }

  echo "<script>alert('Presenças registradas com sucesso!'); window.location.href='preenchimento.php';</script>";

} catch (PDOException $e) {
  echo "Erro ao salvar presença: " . $e->getMessage();
}
?>
