<?php
// Inicia o buffer de saída para evitar problemas com headers
ob_start();

// Inicia/reinicia a sessão
session_start();

// Impede cache para forçar verificação de login
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Verificação robusta de sessão
if (empty($_SESSION['usuario']) || empty($_SESSION['filial']) || empty($_SESSION['turno']) || empty($_SESSION['operacao'])) {
    // Destrói a sessão completamente se faltar algum dado
    session_unset();
    session_destroy();
    header("Location: index.php?erro=2");
    exit();
}

// Verificação de tempo de inatividade (30 minutos)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: index.php?erro=3");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time(); // Atualiza o tempo da última atividade

// Atribui variáveis da sessão
$usuario = $_SESSION['usuario'];
$filial = $_SESSION['filial'];
$turno = $_SESSION['turno'];
$operacao = $_SESSION['operacao'];

// Conexão com o banco de dados
try {
    $conn = new PDO(
        "mysql:host=assiduidade.mysql.uhserver.com;dbname=assiduidade;charset=utf8mb4",
        "assiduidade",
        "Grupojb2024@@"
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta para colaboradores com base no turno e operação
    $stmtColaboradores = $conn->prepare("SELECT * FROM colaboradores WHERE filial = ? AND turno = ? AND operacao = ? AND ativo = 1");
    $stmtColaboradores->execute([$filial, $turno, $operacao]);
    $colaboradores_padrao = $stmtColaboradores->fetchAll(PDO::FETCH_ASSOC);  // Definindo corretamente a variável

    // Consulta para todos colaboradores
    $stmtTodos = $conn->prepare("SELECT * FROM colaboradores WHERE filial = ? AND operacao = ? AND ativo = 1");
    $stmtTodos->execute([$filial, $operacao]);
    $todos_colaboradores = $stmtTodos->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para turnos disponíveis
    $stmtTurnos = $conn->prepare("SELECT DISTINCT turno FROM colaboradores WHERE filial = ? AND operacao = ? AND ativo = 1");
    $stmtTurnos->execute([$filial, $operacao]);
    $todos_turnos = $stmtTurnos->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para operações disponíveis
    $stmtOperacoes = $conn->prepare("SELECT DISTINCT operacao FROM colaboradores WHERE filial = ? AND ativo = 1");
    $stmtOperacoes->execute([$filial]);
    $todas_operacoes = $stmtOperacoes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log do erro (em produção, considere registrar em um arquivo de log)
    error_log("Erro de banco de dados: " . $e->getMessage());

    // Mensagem genérica para o usuário
    die("Ocorreu um erro ao acessar o sistema. Por favor, tente novamente mais tarde.");
}

// Datas importantes
$hoje = date('Y-m-d');
$minDate = date('Y-m-d', strtotime('-3 days'));

// Libera o buffer de saída
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Registro de Presença</title>
  <link rel="stylesheet" href="./estilos/lista.css">
  <link rel="shortcut icon" href="./img/logo.png" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

<h2>Registro de Presença - <?php echo htmlspecialchars($filial); ?> - Operação: <?php echo htmlspecialchars($operacao); ?></h2>

<form action="salvar_presenca.php" method="POST" onsubmit="return validarCampos();">
  <label>Data da Presença:
    <input type="date" name="data_presenca" id="data_presenca"
           value="<?= $hoje ?>"
           min="<?= $minDate ?>"
           max="<?= $hoje ?>"
           required>
    <small>(Permitido: hoje e até 3 dias anteriores)</small>
  </label><br><br>

  <label>Adicionar Terceiro (exceção):
    <select id="colaboradorSelect" style="width: 300px;">
      <option value="">Buscar por nome</option>
      <?php foreach ($todos_colaboradores as $colaborador): ?>
        <option value="<?= $colaborador['id']; ?>"><?= htmlspecialchars($colaborador['nome']); ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" onclick="adicionarColaborador()">Adicionar Terceirizado</button>
  </label>

  <label>Selecionar turno:
    <select id="turnoSelect" onchange="carregarColaboradoresPorTurno()">
      <?php foreach ($todos_turnos as $t): ?>
        <option value="<?= htmlspecialchars($t['turno']); ?>" <?= ($t['turno'] === $turno) ? 'selected' : '' ?>>
          <?= htmlspecialchars($t['turno']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Selecionar operação:
  <select id="operacaoSelect" onchange="carregarColaboradoresPorOperacao()">
    <?php foreach ($todas_operacoes as $o): ?>
      <option value="<?= htmlspecialchars($o['operacao']); ?>" <?= ($o['operacao'] === $operacao) ? 'selected' : '' ?>>
        <?= htmlspecialchars($o['operacao']); ?>
      </option>
    <?php endforeach; ?>
  </select>
</label>

  <button type="button" onclick="adicionarAgregado()">Adicionar Diarista</button>

  <div id="colaboradoresCampos">
    <?php foreach ($colaboradores_padrao as $colaborador): ?>
      <div class="colaborador-row">
        <span class="colaborador-nome"><?= htmlspecialchars($colaborador['nome']); ?></span>
        <input type="hidden" name="colaborador_id[]" value="<?= $colaborador['id']; ?>">
        <label>Falta? <input type="checkbox" name="falta_<?= $colaborador['id'] ?>" class="falta" data-id="<?= $colaborador['id'] ?>"></label>
        <label>Folga? <input type="checkbox" name="folga_<?= $colaborador['id'] ?>" class="folga" data-id="<?= $colaborador['id'] ?>"></label>
        <label>Hora Entrada: <input type="time" name="hora_entrada_<?= $colaborador['id'] ?>" class="entrada entrada_<?= $colaborador['id'] ?>" required></label>
        <label>Hora Saída: <input type="time" name="hora_saida_<?= $colaborador['id'] ?>" class="saida saida_<?= $colaborador['id'] ?>" required></label>
        <label>Observações: <input type="text" name="observacoes_<?= $colaborador['id'] ?>" maxlength="255"></label>
        <button type="button" onclick="removerColaborador(this, '<?= $colaborador['id'] ?>')">Remover</button>
        <button type="button" onclick="adicionarSuplente('<?= $colaborador['id'] ?>')">Suplente</button>
        <div id="suplente_<?= $colaborador['id'] ?>"></div>
      </div>
    <?php endforeach; ?>
  </div>

  <br>
  <button type="submit" onclick="return confirm('Tem certeza que deseja enviar?')">Enviar</button>
</form>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
  $(document).ready(function() {
    $('#colaboradorSelect').select2({
      placeholder: "Buscar por nome",
      allowClear: true
    });
  });

  const colaboradorSelect = document.getElementById('colaboradorSelect');
  const container = document.getElementById('colaboradoresCampos');
  let colaboradoresAdicionados = Array.from(document.querySelectorAll("input[name='colaborador_id[]']")).map(el => el.value);
  let agregadoCount = 0;

  function adicionarColaborador() {
    const id = colaboradorSelect.value;
    const nome = colaboradorSelect.options[colaboradorSelect.selectedIndex].text;

    if (!id || colaboradoresAdicionados.includes(id)) return;

    colaboradoresAdicionados.push(id);

    const bloco = document.createElement('div');
    bloco.className = 'colaborador-row';
    bloco.innerHTML = `
      <span class="colaborador-nome">${nome}</span>
      <input type="hidden" name="colaborador_id[]" value="${id}">
      <label>Falta? <input type="checkbox" name="falta_${id}" class="falta" data-id="${id}"></label>
      <label>Folga? <input type="checkbox" name="folga_${id}" class="folga" data-id="${id}"></label>
      <label>Hora Entrada: <input type="time" name="hora_entrada_${id}" class="entrada entrada_${id}" required></label>
      <label>Hora Saída: <input type="time" name="hora_saida_${id}" class="saida saida_${id}" required></label>
      <label>Observações: <input type="text" name="observacoes_${id}" maxlength="255"></label>
      <button type="button" onclick="removerColaborador(this, '${id}')">Remover</button>
    `;
    container.appendChild(bloco);
    aplicarListenersFaltas();
  }

  function adicionarAgregado() {
    agregadoCount++;
    const idTemp = 'agregado_' + agregadoCount;
    colaboradoresAdicionados.push(idTemp);

    const bloco = document.createElement('div');
    bloco.className = 'colaborador-row';
    bloco.innerHTML = `
      <span class="colaborador-nome"><input type="text" name="nome_${idTemp}" placeholder="Nome Completo Diarista" required></span>
      <input type="hidden" name="colaborador_id[]" value="${idTemp}">
      <input type="hidden" name="agregado_${idTemp}" value="Sim">
      <label>Falta? <input type="checkbox" name="falta_${idTemp}" class="falta" data-id="${idTemp}"></label>
      <label>Folga? <input type="checkbox" name="folga_${idTemp}" class="folga" data-id="${idTemp}"></label>
      <label>Hora Entrada: <input type="time" name="hora_entrada_${idTemp}" class="entrada entrada_${idTemp}" required></label>
      <label>Hora Saída: <input type="time" name="hora_saida_${idTemp}" class="saida saida_${idTemp}" required></label>
      <label>Observações: <input type="text" name="observacoes_${idTemp}" maxlength="255" required></label>
      <button type="button" onclick="removerColaborador(this, '${idTemp}')">Remover</button>
    `;
    container.appendChild(bloco);
    aplicarListenersFaltas();
}


  function removerColaborador(botao, id) {
    botao.parentElement.remove();
    colaboradoresAdicionados = colaboradoresAdicionados.filter(cid => cid !== id);
  }

  function aplicarListenersFaltas() {
    document.querySelectorAll(".falta, .folga").forEach(cb => {
      cb.addEventListener("change", () => {
        const id = cb.dataset.id;
        const falta = document.querySelector(`input[name='falta_${id}']`);
        const folga = document.querySelector(`input[name='folga_${id}']`);
        const entrada = document.querySelector(`.entrada_${id}`);
        const saida = document.querySelector(`.saida_${id}`);

        if (falta.checked) folga.checked = false;
        if (folga.checked) falta.checked = false;

        if (falta.checked || folga.checked) {
          entrada.disabled = true;
          saida.disabled = true;
          entrada.value = "";
          saida.value = "";
        } else {
          entrada.disabled = false;
          saida.disabled = false;
        }
      });
    });
  }

  function validarCampos() {
    const ids = document.querySelectorAll("input[name='colaborador_id[]']");
    for (let i = 0; i < ids.length; i++) {
      const id = ids[i].value;
      const falta = document.querySelector(`input[name='falta_${id}']`);
      const folga = document.querySelector(`input[name='folga_${id}']`);
      const entrada = document.querySelector(`input[name='hora_entrada_${id}']`);
      const saida = document.querySelector(`input[name='hora_saida_${id}']`);

      if (!falta.checked && !folga.checked && (entrada.value === "" || saida.value === "")) {
        alert("Preencha entrada e saída ou marque falta/folga para todos os colaboradores.");
        return false;
      }
    }
    return true;
  }

  function adicionarSuplente(id) {
  const divSuplente = document.getElementById('suplente_' + id);

  // Se os campos de suplente já estão visíveis, desative-os (remover)
  if (divSuplente.innerHTML.trim() !== "") {
    divSuplente.innerHTML = ''; // Remove os campos de suplente
    return; // Finaliza a execução para não adicionar novamente
  }

  // Marca o checkbox de falta automaticamente
  const faltaCheckbox = document.querySelector(`input[name='falta_${id}']`);
  if (faltaCheckbox) {
    faltaCheckbox.checked = true;
    faltaCheckbox.dispatchEvent(new Event('change')); // Dispara a mudança
  }

  // Adiciona os campos de suplente, se ainda não foram adicionados
  divSuplente.insertAdjacentHTML('beforeend', `
    <div class="suplente-row">
      <strong>Suplente de ${document.querySelector(`.entrada_${id}`).closest('.colaborador-row').querySelector('.colaborador-nome').innerText}</strong><br>
      <input type="hidden" name="suplente_de[]" value="${id}">
      <label>Nome: <input type="text" name="nome_suplente_${id}" required></label>
      <label>Hora Entrada: <input type="time" name="hora_entrada_suplente_${id}" required></label>
      <label>Hora Saída: <input type="time" name="hora_saida_suplente_${id}" required></label>
      <label>Observações: <input type="text" name="observacoes_suplente_${id}" maxlength="255"></label>
    </div>
  `);
}



  function carregarColaboradoresPorTurno() {
    const turno = document.getElementById('turnoSelect').value;
    const operacao = document.getElementById('operacaoSelect').value;

    fetch(`buscar_colaboradores_turno.php?turno=${encodeURIComponent(turno)}&operacao=${encodeURIComponent(operacao)}`)
      .then(res => res.json())
      .then(colaboradores => {
        container.innerHTML = '';  // Limpa o conteúdo do container
        colaboradoresAdicionados = []; // Reinicia a lista de colaboradores

        colaboradores.forEach(colaborador => {
          colaboradoresAdicionados.push(colaborador.id);

          const bloco = document.createElement('div');
          bloco.className = 'colaborador-row';
          bloco.innerHTML = `
            <span class="colaborador-nome">${colaborador.nome}</span>
            <input type="hidden" name="colaborador_id[]" value="${colaborador.id}">
            <label>Falta? <input type="checkbox" name="falta_${colaborador.id}" class="falta" data-id="${colaborador.id}"></label>
            <label>Folga? <input type="checkbox" name="folga_${colaborador.id}" class="folga" data-id="${colaborador.id}"></label>
            <label>Hora Entrada: <input type="time" name="hora_entrada_${colaborador.id}" class="entrada entrada_${colaborador.id}" required></label>
            <label>Hora Saída: <input type="time" name="hora_saida_${colaborador.id}" class="saida saida_${colaborador.id}" required></label>
            <label>Observações: <input type="text" name="observacoes_${colaborador.id}" maxlength="255"></label>
            <button type="button" onclick="removerColaborador(this, '${colaborador.id}')">Remover</button>
            <button type="button" onclick="adicionarSuplente('${colaborador.id}')">Suplente</button>
            <div id="suplente_${colaborador.id}"></div>
          `;
          container.appendChild(bloco);
        });

        aplicarListenersFaltas(); // Aplica os listeners de faltas
      })
      .catch(err => console.error('Erro ao carregar colaboradores por turno:', err));
  }

  function carregarColaboradoresPorOperacao() {
    const operacao = document.getElementById('operacaoSelect').value;
    const turno = document.getElementById('turnoSelect').value;

    fetch(`buscar_colaboradores_turno.php?turno=${encodeURIComponent(turno)}&operacao=${encodeURIComponent(operacao)}`)
      .then(res => res.json())
      .then(colaboradores => {
        container.innerHTML = '';  // Limpa o conteúdo do container
        colaboradoresAdicionados = []; // Reinicia a lista de colaboradores

        colaboradores.forEach(colaborador => {
          colaboradoresAdicionados.push(colaborador.id);

          const bloco = document.createElement('div');
          bloco.className = 'colaborador-row';
          bloco.innerHTML = `
            <span class="colaborador-nome">${colaborador.nome}</span>
            <input type="hidden" name="colaborador_id[]" value="${colaborador.id}">
            <label>Falta? <input type="checkbox" name="falta_${colaborador.id}" class="falta" data-id="${colaborador.id}"></label>
            <label>Folga? <input type="checkbox" name="folga_${colaborador.id}" class="folga" data-id="${colaborador.id}"></label>
            <label>Hora Entrada: <input type="time" name="hora_entrada_${colaborador.id}" class="entrada entrada_${colaborador.id}" required></label>
            <label>Hora Saída: <input type="time" name="hora_saida_${colaborador.id}" class="saida saida_${colaborador.id}" required></label>
            <label>Observações: <input type="text" name="observacoes_${colaborador.id}" maxlength="255"></label>
            <button type="button" onclick="removerColaborador(this, '${colaborador.id}')">Remover</button>
            <button type="button" onclick="adicionarSuplente('${colaborador.id}')">Suplente</button>
            <div id="suplente_${colaborador.id}"></div>
          `;
          container.appendChild(bloco);
        });

        aplicarListenersFaltas(); // Aplica os listeners de faltas
      })
      .catch(err => console.error('Erro ao carregar colaboradores por operação:', err));
  }

  // Aplica os listeners iniciais
  aplicarListenersFaltas();
</script>

</body>
</html>