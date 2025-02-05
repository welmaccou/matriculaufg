<?php
// Ativa a exibição de erros para depuração
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --------------------------
// Função para listar os checklists salvos
// --------------------------
function listarChecklists() {
    // Utiliza o caminho absoluto para garantir que os arquivos serão encontrados na mesma pasta do script
    $files = glob(__DIR__ . "/checklist_*.txt");
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="utf-8">
      <title>Listar Checklists Salvos - Cadastro Único UFG</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; color: #007BFF; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
        a { text-decoration: none; color: #007BFF; }
        a:hover { text-decoration: underline; }
        .btn { padding: 6px 12px; background: #007BFF; color: #fff; border-radius: 4px; }
      </style>
    </head>
    <body>
      <h1>Checklists Salvos</h1>
      <?php if(count($files) == 0): ?>
        <p>Nenhum checklist salvo.</p>
      <?php else: ?>
        <table>
          <tr>
            <th>Estudante</th>
            <th>Arquivo</th>
            <th>Ações</th>
          </tr>
          <?php foreach($files as $file): 
                  $fileName = basename($file);
                  // Tenta extrair o nome do estudante a partir da segunda linha
                  $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                  $studentName = $fileName; // fallback
                  if(count($lines) >= 2) {
                      // A segunda linha deve estar no formato: "Estudante: Nome"
                      if(preg_match('/^Estudante:\s*(.+)$/i', $lines[1], $matches)) {
                          $studentName = htmlspecialchars(trim($matches[1]));
                      }
                  }
          ?>
          <tr>
            <td><?php echo $studentName; ?></td>
            <td><?php echo htmlspecialchars($fileName); ?></td>
            <td>
              <a class="btn" href="?action=edit&file=<?php echo urlencode($fileName); ?>">Editar</a> 
              <a class="btn" href="?action=delete&file=<?php echo urlencode($fileName); ?>" onclick="return confirm('Tem certeza que deseja excluir este checklist?');">Excluir</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
      <p><a href="index.php">Voltar à página principal</a></p>
    </body>
    </html>
    <?php
    exit;
}

// --------------------------
// Função para exibir formulário de edição de um checklist salvo
// --------------------------
function editarChecklist($filename) {
    $filepath = __DIR__ . "/" . $filename;
    if (!file_exists($filepath)) {
        echo "<p>Arquivo não encontrado.</p>";
        echo '<p><a href="?action=list">Voltar à listagem</a></p>';
        exit;
    }
    $content = file_get_contents($filepath);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
      <meta charset="utf-8">
      <title>Editar Checklist - <?php echo htmlspecialchars($filename); ?></title>
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; color: #007BFF; }
        textarea { width: 100%; height: 300px; }
        input[type="submit"] { padding: 10px 20px; background: #007BFF; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background: #0056b3; }
        a { text-decoration: none; color: #007BFF; }
      </style>
    </head>
    <body>
      <h1>Editar Checklist</h1>
      <form method="POST" action="">
        <input type="hidden" name="update_file" value="<?php echo htmlspecialchars($filename); ?>">
        <textarea name="updated_content"><?php echo htmlspecialchars($content); ?></textarea>
        <br><br>
        <input type="submit" name="update_checklist" value="Salvar Alterações">
      </form>
      <p><a href="?action=list">Voltar à listagem</a></p>
    </body>
    </html>
    <?php
    exit;
}

// --------------------------
// Ação de atualização do checklist editado
// --------------------------
if (isset($_POST['update_checklist'])) {
    $filename = $_POST['update_file'];
    $filepath = __DIR__ . "/" . $filename;
    $updatedContent = $_POST['updated_content'];
    if(file_put_contents($filepath, $updatedContent) !== false) {
        echo "<p style='color: green;'>Checklist atualizado com sucesso.</p>";
    } else {
        echo "<p style='color: red;'>Erro ao atualizar o checklist.</p>";
    }
    echo '<p><a href="?action=list">Voltar à listagem</a></p>';
    exit;
}

// --------------------------
// Ação de exclusão de um checklist salvo
// --------------------------
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['file'])) {
    $filename = $_GET['file'];
    $filepath = __DIR__ . "/" . $filename;
    if (file_exists($filepath)) {
        unlink($filepath);
        echo "<p style='color: green;'>Checklist excluído com sucesso.</p>";
    } else {
        echo "<p style='color: red;'>Arquivo não encontrado.</p>";
    }
    echo '<p><a href="?action=list">Voltar à listagem</a></p>';
    exit;
}

// --------------------------
// Roteamento para listagem ou edição
// --------------------------
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action == 'list') {
        listarChecklists();
    }
    if ($action == 'edit' && isset($_GET['file'])) {
        editarChecklist($_GET['file']);
    }
}

// --------------------------
// Processamento do formulário de checklist (salva o arquivo)
// --------------------------
if (isset($_POST['save_checklist'])) {
    $studentName = $_POST['student_name'];
    $checklistItems = $_POST['checklist_items'];
    $checkedDocs = isset($_POST['checked_docs']) ? $_POST['checked_docs'] : array();

    $output  = "Checklist de Documentos - Cadastro Único UFG\n";
    $output .= "Estudante: " . $studentName . "\n\n";

    foreach ($checklistItems as $index => $doc) {
        if (strpos($doc, "###") === 0) {
            if (substr($doc, 0, 13) == "###ESTUDANTE###") {
                $output .= "Documentos do Estudante:\n";
            } elseif (substr($doc, 0, 10) == "###MEMBRO:") {
                // Formato: "###MEMBRO: Nome###"
                $memberName = trim(substr($doc, 10, -3));
                $output .= "Documentos do Membro ($memberName):\n";
            } elseif ($doc === "###OUTROS###") {
                $output .= "Outros Documentos:\n";
            }
        } else {
            $status = in_array($index, $checkedDocs) ? "[X]" : "[ ]";
            $output .= $status . " " . $doc . "\n";
        }
    }
    $filename = "checklist_" . time() . ".txt";
    file_put_contents(__DIR__ . "/" . $filename, $output);
    echo "<p style='color: green; font-weight: bold;'>Checklist salvo com sucesso como <code>$filename</code>.</p>";
    echo '<p><a href="?action=list">Listar Checklists Salvos</a></p>';
    // Continua para exibir o formulário principal.
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checklist de Documentos - Cadastro Único UFG</title>
  <style>
    body {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f9f9f9;
      line-height: 1.8;
      background-image: linear-gradient(135deg, #f8f9fa 0%, #f1f3f5 100%);
      min-height: 100vh;
    }
    .container {
      max-width: 900px;
      margin: 40px auto;
      padding: 30px;
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08), 0 4px 8px rgba(0, 0, 0, 0.04);
      border-radius: 16px;
      backdrop-filter: blur(8px);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: 1px solid rgba(255, 255, 255, 0.3);
    }
    .container:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 32px rgba(0, 0, 0, 0.1), 0 8px 16px rgba(0, 0, 0, 0.06);
    }
    h1 {
      text-align: center;
      color: #007BFF;
      margin-bottom: 10px;
      font-size: 1.7rem;
      letter-spacing: -0.5px;
      background: linear-gradient(45deg, #007BFF, #00C2FF);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 0 2px 4px rgba(0, 123, 255, 0.1);
      text-transform: uppercase;
    }
    p {
      margin: 10px 0;
      color: #555;
      font-size: 1.05rem;
    }
    .form-group, .form-group2 {
      margin-bottom: 0px;
      transition: opacity 0.3s ease;
    }
    label {
      font-weight: 600;
      display: block;
      margin-bottom: 0px;
      margin-top: 20px;
      color: #2d3436;
      font-size: 0.95rem;
      letter-spacing: 0.3px;
    }
    input, select, button {
      width: 100%;
      padding: 12px;
      margin-top: 0px;
      border: 2px solid #e9ecef;
      border-radius: 8px;
      font-size: 16px;
      box-sizing: border-box;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    input:focus, select:focus, button:focus {
      border-color: #007BFF;
      outline: none;
      box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.15);
    }
    button {
      background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%);
      color: #fff;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
      padding: 14px;
      font-weight: 600;
      letter-spacing: 0.5px;
      border-radius: 8px;
      margin-top: 15px;
    }
    button:hover {
      background: linear-gradient(135deg, #0056b3 0%, #003d80 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 123, 255, 0.25);
    }
    .instructions {
      margin-top: 20px;
      background: rgba(240, 248, 255, 0.95);
      padding: 20px;
      border-radius: 8px;
      border-left: 6px solid #007BFF;
      backdrop-filter: blur(4px);
    }
    .instructionsh2 {
      margin: 0;
      color: #007BFF;
      font-size: 18px;
    }
    .member-section h3 {
      background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%);
      color: white;
      padding: 14px;
      margin: 0 0 15px 0;
      border-radius: 8px;
      box-shadow: 0 4px 6px rgba(0, 123, 255, 0.1);
      font-size: 1.2rem;
    }
    .member-section {
      margin-top: 30px;
    }
    ul li {
      background: rgba(248, 249, 250, 0.95);
      margin: 8px 0;
      padding: 14px;
      border-left: 4px solid #007BFF;
      border-radius: 8px;
      transition: transform 0.2s ease;
    }
    ul li:hover {
      transform: translateX(4px);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    .section-title {
      margin-top: 30px;
      font-size: 1.5rem;
      font-weight: 700;
      color: #007BFF;
      border-bottom: 3px solid;
      border-image: linear-gradient(90deg, #007BFF 0%, rgba(0, 123, 255, 0) 95%) 1;
      padding-bottom: 8px;
    }
    .custom-select {
      border: 2px solid #e9ecef;
      border-radius: 8px;
      padding: 8px;
      background: white;
      margin-top: 5px;
      transition: all 0.3s ease;
    }
    .custom-option {
      padding: 10px;
      border-radius: 6px;
      margin: 4px 0;
      border: 2px solid rgba(0, 86, 179, 0.1);
      cursor: pointer;
    }
    .custom-option:hover {
      background: rgba(0, 123, 255, 0.08);
      transform: scale(1.01);
      border: 2px solid rgba(0, 123, 255, 0.08);
    }
    .custom-option.selected {
      background: linear-gradient(135deg, #007BFF 0%, #0056b3 100%);
      color: white;
      box-shadow: 0 2px 6px rgba(0, 123, 255, 0.2);
      border: 2px solid rgba(0, 123, 255, 1);
    }
    #student-info input {
      padding: 10px;
      border-radius: 6px;
      transition: all 0.2s ease;
    }
    #student-info input:focus {
      box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }
    * {
      scroll-behavior: smooth;
    }
    .hidden-select {
      display: none;
    }
    .select-label {
      margin-bottom: 5px;
      display: block;
      color: #555;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Checklist de Documentos - Cadastro Único UFG</h1>
    <div class="instructions">
      <h2 class="instructionsh2"><strong>Atenção</strong></h2>
      <p>Este gerador de checklist visa facilitar a identificação dos documentos necessários para inscrição no Cadastro Único de Bolsas da UFG, considerando a realidade socioeconômica de cada estudante; entretanto, <strong>não substitui a leitura cuidadosa da Relação de Documentos do edital.</strong></p>
    </div>
    <p><strong>Definição de Grupo Familiar:</strong> é considerado grupo familiar todas as pessoas que moram com você, que dividem ou não a mesma renda, eventualmente ampliada por outras pessoas que contribuam para o rendimento ou tenham suas despesas atendidas por aquela unidade familiar, todas moradoras em um mesmo domicílio.</p>
    <p><strong>Importante:</strong> o/a estudante que se define como único membro do grupo familiar e não possua rendimento próprio suficiente para a sua subsistência deverá informar o grupo familiar de origem, ainda que residente em local diverso do seu domicílio.</p>
    
    <div class="form-group2">
      <label for="family-size">Quantidade de pessoas no grupo familiar (além do estudante):</label>
      <input type="number" id="family-size" name="family-size" min="0" placeholder="Digite a quantidade" required aria-label="Quantidade de pessoas no grupo familiar (além do estudante)">
    </div>
    <button onclick="generateFamilyInputs()" aria-label="Confirmar quantidade de membros da família">Confirmar</button>
    
    <div id="family-members"></div>
    <div id="checklist-container"></div>
    <p><a href="?action=list">Listar Checklists Salvos</a></p>
  </div>

  <script>
    // Função para criar um select customizado
    function createCustomSelect(originalSelect) {
      const wrapper = document.createElement('div');
      wrapper.className = 'custom-select';
      wrapper.setAttribute('role', 'listbox');
      
      const label = document.createElement('div');
      label.className = 'select-label';
      label.textContent = originalSelect.dataset.label || 'Selecione uma opção';
      wrapper.appendChild(label);
      
      originalSelect.querySelectorAll('option').forEach((option, index) => {
        if (index === 0 && option.value === '') return;
        const div = document.createElement('div');
        div.className = 'custom-option';
        div.textContent = option.textContent;
        div.dataset.value = option.value;
        div.setAttribute('role', 'option');
        div.setAttribute('tabindex', '0');
        if (option.selected) {
          div.classList.add('selected');
        }
        div.addEventListener('click', () => {
          div.classList.toggle('selected');
          option.selected = !option.selected;
        });
        div.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            div.click();
          }
        });
        wrapper.appendChild(div);
      });
      
      originalSelect.parentNode.insertBefore(wrapper, originalSelect);
      originalSelect.classList.add('hidden-select');
    }

    // Validação dos campos obrigatórios
    function validateForm() {
      const familySizeInput = document.getElementById('family-size');
      if (!familySizeInput.value || parseInt(familySizeInput.value) < 0) {
        familySizeInput.scrollIntoView({ behavior: 'smooth' });
        familySizeInput.focus();
        alert("Campo 'Quantidade de pessoas no grupo familiar (além do estudante)' é obrigatório.");
        return false;
      }
      const studentNameInput = document.getElementById('student-name');
      if (!studentNameInput || !studentNameInput.value.trim()) {
        studentNameInput.scrollIntoView({ behavior: 'smooth' });
        studentNameInput.focus();
        alert("Campo 'Nome do Estudante' é obrigatório.");
        return false;
      }
      const studentSituationSelect = document.getElementById('student-situation');
      const studentSituationOptions = Array.from(studentSituationSelect.options)
                                          .filter(opt => opt.selected && opt.value !== '');
      if (studentSituationOptions.length === 0) {
        studentSituationSelect.scrollIntoView({ behavior: 'smooth' });
        studentSituationSelect.focus();
        alert("Campo 'Situação do Estudante' é obrigatório.");
        return false;
      }
      const familySize = parseInt(familySizeInput.value);
      for (let i = 0; i < familySize; i++) {
        const memberNameInput = document.getElementById(`member-name-${i}`);
        if (!memberNameInput || !memberNameInput.value.trim()) {
          memberNameInput.scrollIntoView({ behavior: 'smooth' });
          memberNameInput.focus();
          alert(`Campo 'Nome' do Membro ${i + 1} é obrigatório.`);
          return false;
        }
        const memberSituationSelect = document.getElementById(`member-situation-${i}`);
        const memberSituationOptions = Array.from(memberSituationSelect.options)
                                            .filter(opt => opt.selected && opt.value !== '');
        if (memberSituationOptions.length === 0) {
          memberSituationSelect.scrollIntoView({ behavior: 'smooth' });
          memberSituationSelect.focus();
          alert(`Campo 'Situação' do Membro ${i + 1} é obrigatório.`);
          return false;
        }
      }
      return true;
    }

    // Gera os campos para informações do estudante e membros da família
    function generateFamilyInputs() {
      const familySize = parseInt(document.getElementById('family-size').value);
      const familyMembersDiv = document.getElementById('family-members');

      if (isNaN(familySize) || familySize < 0) {
        alert('Por favor, insira um número válido para a quantidade de pessoas na família. Caso o estudante seja o único membro do grupo familiar, coloque o valor 0 (zero).');
        return;
      }

      familyMembersDiv.innerHTML = '';

      const studentDiv = document.createElement('div');
      studentDiv.classList.add('form-group');
      studentDiv.innerHTML = `
        <h3 class="section-title">Informações do Estudante</h3>
        <div id="student-info">
          <div class="input-group">
            <label for="student-name">Nome do Estudante:</label>
            <input type="text" id="student-name" name="student-name" placeholder="Digite o nome do estudante" required aria-label="Nome do Estudante">
          </div>
          <div class="input-group">
            <label for="student-situation">Situação do Estudante:</label>
            <select id="student-situation" multiple required data-label="Selecione todas as situações aplicáveis (clique para selecionar)" aria-label="Situação do Estudante">
              <option value="">-- Selecione --</option>
              <option value="assalariado">Assalariado</option>
              <option value="autonomo">Autônomo/profissional liberal/trabalhador informal</option>
              <option value="aposentado">Aposentado ou pensionista</option>
              <option value="mei">Microempreendedor Individual (MEI)</option>
              <option value="desempregado">Desempregado</option>
              <option value="beneficiario">Beneficiário de Programas Sociais (Bolsa Família, BPC, outros)</option>
              <option value="estagiario">Estagiário</option>
              <option value="produtor">Produtor rural/lavrador</option>
              <option value="socio">Sócio ou dirigente de empresas (Microempresário)</option>
            </select>
          </div>
          <div class="input-group">
            <label for="student-health-expenses">Possui despesas com saúde?</label>
            <select id="student-health-expenses" name="student-health-expenses" required aria-label="Possui despesas com saúde?">
              <option value="">-- Escolha uma opção --</option>
              <option value="sim">Sim</option>
              <option value="nao">Não</option>
            </select>
          </div>
          <div class="input-group">
            <label for="student-education-expenses">Possui despesas com educação?</label>
            <select id="student-education-expenses" name="student-education-expenses" required aria-label="Possui despesas com educação?">
              <option value="">-- Escolha uma opção --</option>
              <option value="sim">Sim</option>
              <option value="nao">Não</option>
            </select>
          </div>
          <div class="input-group">
            <label for="student-housing">Moradia:</label>
            <select id="student-housing" required aria-label="Tipo de moradia">
              <option value="">-- Escolha uma opção --</option>
              <option value="alugada">Alugada</option>
              <option value="cedida">Cedida</option>
              <option value="financiada">Financiada</option>
              <option value="propria">Própria</option>
            </select>
          </div>
          <div class="input-group">
            <label for="student-single-orphans">É solteiro(a) com pais falecidos?</label>
            <select id="student-single-orphans" required aria-label="É solteiro(a) com pais falecidos?">
              <option value="">-- Escolha uma opção --</option>
              <option value="sim">Sim</option>
              <option value="nao">Não</option>
            </select>
          </div>
          <div class="input-group">
            <label for="student-canguru-scholarship">Pretende solicitar a bolsa Canguru?</label>
            <select id="student-canguru-scholarship" required aria-label="Pretende solicitar a bolsa Canguru?">
              <option value="">-- Escolha uma opção --</option>
              <option value="sim">Sim</option>
              <option value="nao">Não</option>
            </select>
          </div>
          <div class="input-group">
            <label for="student-transport-scholarship">Pretende solicitar a bolsa Transporte?</label>
            <select id="student-transport-scholarship" required aria-label="Pretende solicitar a bolsa Transporte?">
              <option value="">-- Escolha uma opção --</option>
              <option value="sim">Sim</option>
              <option value="nao">Não</option>
            </select>
          </div>
          <div class="input-group">
            <label for="locacao-imoveis">Você ou alguém do núcleo familiar possui renda proveniente de locação de imóveis?</label>
            <select id="locacao-imoveis" required aria-label="Renda proveniente de locação de imóveis?">
              <option value="">-- Escolha uma opção --</option>
              <option value="sim">Sim</option>
              <option value="nao">Não</option>
            </select>
          </div>
          <div class="input-group">
            <label for="sitio-chacara">Você ou alguém do núcleo familiar é proprietário de sítio(s), chácaras ou fazenda(s)?</label>
            <select id="sitio-chacara" required aria-label="Proprietário de sítio(s), chácaras ou fazenda(s)?">
              <option value="">-- Escolha uma opção --</option>
              <option value="sim">Sim</option>
              <option value="nao">Não</option>
            </select>
          </div>
        </div>
      `;
      createCustomSelect(studentDiv.querySelector('#student-situation'));
      familyMembersDiv.appendChild(studentDiv);

      const spacingDiv = document.createElement('div');
      spacingDiv.style.marginBottom = "20px";
      familyMembersDiv.appendChild(spacingDiv);

      for (let i = 0; i < familySize; i++) {
        const memberDiv = document.createElement('div');
        memberDiv.classList.add('form-group');
        memberDiv.innerHTML = `
          <h3 class="section-title">Membro ${i + 1}</h3>
          <div class="input-group">
            <label for="member-name-${i}">Nome:</label>
            <input type="text" placeholder="Nome do membro da família" id="member-name-${i}" name="member-name-${i}" required aria-label="Nome do membro ${i + 1}">
          </div>
          <div class="input-group">
            <label for="member-situation-${i}">Situação:</label>
            <select id="member-situation-${i}" multiple required data-label="Selecione todas as situações aplicáveis (clique para selecionar)" aria-label="Situação do membro ${i + 1}">
              <option value="">-- Selecione --</option>
              <option value="assalariado">Assalariado</option>
              <option value="autonomo">Autônomo/profissional liberal/trabalhador informal</option>
              <option value="aposentado">Aposentado ou pensionista</option>
              <option value="mei">Microempreendedor Individual (MEI)</option>
              <option value="desempregado">Desempregado</option>
              <option value="beneficiario">Beneficiário de Programas Sociais (Bolsa Família, BPC, outros)</option>
              <option value="estagiario">Estagiário</option>
              <option value="produtor">Produtor rural/lavrador</option>
              <option value="socio">Sócio ou dirigente de empresas (Microempresário)</option>
            </select>
          </div>
        `;
        createCustomSelect(memberDiv.querySelector(`#member-situation-${i}`));
        familyMembersDiv.appendChild(memberDiv);

        const spacingDivMember = document.createElement('div');
        spacingDivMember.style.marginBottom = "20px";
        familyMembersDiv.appendChild(spacingDivMember);
      }

      const generateChecklistButton = document.createElement('button');
      generateChecklistButton.textContent = 'Gerar Checklist';
      generateChecklistButton.setAttribute('aria-label', 'Gerar checklist de documentos');
      generateChecklistButton.onclick = generateChecklist;
      familyMembersDiv.appendChild(generateChecklistButton);
    }

    // Objeto com os documentos necessários por situação
    const documentsBySituation = {
      assalariado: [
        '(  ) Documentos de identificação e CPF.',
        '(  ) Se for o caso, certidão de casamento, Certidão de Averbação de Divórcio ou declaração de separação.',
        '(  ) Se maior de 18 anos, Carteira de Trabalho e Previdência Social das páginas de identificação e dos contratos de trabalho, inclusive Carteira de Trabalho Digital e Extrato CAGED.',
        '(  ) Último contracheque.',
        '(  ) Declaração de Imposto de Renda Pessoa Física (se aplicável).',
        '(  ) Relatório de Contas e Relacionamentos em Bancos (CSS).',
        '(  ) Extratos dos 03 últimos meses.'
      ],
      autonomo: [
        '(  ) Documentos de identificação e CPF.',
        '(  ) Certidão de casamento ou declaração de separação (se for o caso).',
        '(  ) Carteira de Trabalho (se aplicável).',
        '(  ) Declaração de trabalhador autônomo.',
        '(  ) Declaração de Imposto de Renda Pessoa Física (se aplicável).',
        '(  ) Relatório de Contas e Relacionamentos em Bancos (CSS).',
        '(  ) Extratos dos 03 últimos meses.'
      ],
      aposentado: [
        '(  ) Documentos de identificação e CPF.',
        '(  ) Certidão de casamento ou declaração de separação (se for o caso).',
        '(  ) Carteira de Trabalho (se aplicável).',
        '(  ) Extrato do pagamento de benefício.',
        '(  ) Declaração de pensão alimentícia (se aplicável).',
        '(  ) Declaração de Imposto de Renda Pessoa Física (se aplicável).',
        '(  ) Relatório de Contas e Relacionamentos em Bancos (CSS).',
        '(  ) Extratos dos 03 últimos meses.'
      ],
      mei: [
        '(  ) Documentos de identificação e CPF.',
        '(  ) Certidão de casamento ou declaração de separação (se for o caso).',
        '(  ) Carteira de Trabalho e/ou documentos do MEI.',
        '(  ) Declaração Anual do Simples Nacional (DASN).',
        '(  ) Declaração de MEI.',
        '(  ) Declaração de Imposto de Renda Pessoa Física (se aplicável).',
        '(  ) Relatório de Contas e Relacionamentos em Bancos (CSS).',
        '(  ) Extratos dos 03 últimos meses.'
      ],
      desempregado: [
        '(  ) Documentos de identificação e CPF.',
        '(  ) Certidão de casamento ou declaração de separação (se for o caso).',
        '(  ) Carteira de Trabalho (se aplicável).',
        '(  ) Comprovante de seguro-desemprego.',
        '(  ) Termo de rescisão de contrato.',
        '(  ) Declaração de desempregado.',
        '(  ) Declaração de Imposto de Renda Pessoa Física (se aplicável).',
        '(  ) Relatório de Contas e Relacionamentos em Bancos (CSS).',
        '(  ) Extratos dos 03 últimos meses.'
      ],
      beneficiario: [
        '(  ) Comprovante atual de recebimento do benefício.',
        '(  ) Relatório de Contas e Relacionamentos em Bancos (CSS).',
        '(  ) Extratos dos 03 últimos meses.'
      ],
      estagiario: [
        '(  ) Contrato de estágio ou termo de compromisso de bolsa.',
        '(  ) Relatório de Contas e Relacionamentos em Bancos (CSS).',
        '(  ) Extratos dos 03 últimos meses.'
      ],
      produtor: [
        '(  ) Declaração de Produtor Rural.',
        '(  ) Declaração de Imposto de Renda Pessoa Física (se aplicável).',
        '(  ) Relatório de Contas e Relacionamentos em Bancos (CSS).',
        '(  ) Extratos dos 03 últimos meses.'
      ],
      socio: [
        '(  ) Último contracheque ou Pró-Labore.',
        '(  ) Declaração Anual do Simples Nacional de 2024.',
        '(  ) Declaração de Imposto de Renda Pessoa Jurídica.',
        '(  ) Declaração de Imposto de Renda Pessoa Física (se aplicável).',
        '(  ) Relatório de Contas e Relacionamentos em Bancos (CSS).',
        '(  ) Extratos dos 03 últimos meses.'
      ]
    };

    // Gera o checklist com caixas de seleção e insere em um formulário para salvamento
    function generateChecklist() {
      if (!validateForm()) return;

      const familySize = parseInt(document.getElementById('family-size').value);
      const studentName = document.getElementById('student-name').value.trim();
      const studentSituationSelect = document.getElementById('student-situation');
      const studentSituations = Array.from(studentSituationSelect.options)
                                    .filter(opt => opt.selected && opt.value !== '')
                                    .map(opt => opt.value);
      const studentHealthExpenses = document.getElementById('student-health-expenses').value;
      const studentEducationExpenses = document.getElementById('student-education-expenses').value;
      const studentHousing = document.getElementById('student-housing').value;
      const studentSingleOrphans = document.getElementById('student-single-orphans').value;
      const studentCanguruScholarship = document.getElementById('student-canguru-scholarship').value;
      const studentTransportScholarship = document.getElementById('student-transport-scholarship').value;
      const locacaoImoveis = document.getElementById('locacao-imoveis').value;
      const sitioChacara = document.getElementById('sitio-chacara').value;

      let docCounter = 0;
      let checklistHTML = `<h1>Checklist de Documentos - Cadastro Único UFG</h1>`;
      // Insere o nome do estudante (campo oculto)
      checklistHTML += `<input type="hidden" name="student_name" value="`+ studentName +`">`;

      // --- Seção do estudante ---
      // Marca início da seção do estudante
      checklistHTML += `<input type="hidden" name="checklist_items[]" value="###ESTUDANTE###">`;
      if (studentName !== '' && studentSituations.length > 0) {
        checklistHTML += `<div class="member-section">
                            <h3>Documentos de ${studentName}</h3>
                            <ul>`;
        let studentDocs = [];
        studentSituations.forEach(situation => {
          if (documentsBySituation[situation]) {
            studentDocs = studentDocs.concat(documentsBySituation[situation]);
          }
        });
        const uniqueStudentDocs = [...new Set(studentDocs)];
        uniqueStudentDocs.forEach(doc => {
          checklistHTML += `<li>
                              <input type="checkbox" name="checked_docs[]" value="`+ docCounter +`"> `+ doc +
                              `<input type="hidden" name="checklist_items[]" value="`+ doc +`">
                            </li>`;
          docCounter++;
        });
        if (studentHealthExpenses === 'sim') {
          let doc = '(  ) Relatório médico com diagnóstico de doença crônica e comprovante de despesas.';
          checklistHTML += `<li>
                              <input type="checkbox" name="checked_docs[]" value="`+ docCounter +`"> `+ doc +
                              `<input type="hidden" name="checklist_items[]" value="`+ doc +`">
                            </li>`;
          docCounter++;
        }
        if (studentEducationExpenses === 'sim') {
          let doc = '(  ) Comprovante de mensalidade(s) escolar(es).';
          checklistHTML += `<li>
                              <input type="checkbox" name="checked_docs[]" value="`+ docCounter +`"> `+ doc +
                              `<input type="hidden" name="checklist_items[]" value="`+ doc +`">
                            </li>`;
          docCounter++;
        }
        if (studentHousing === 'alugada') {
          let doc = '(  ) Contrato de locação ou declaração do locador.';
          checklistHTML += `<li>
                              <input type="checkbox" name="checked_docs[]" value="`+ docCounter +`"> `+ doc +
                              `<input type="hidden" name="checklist_items[]" value="`+ doc +`">
                            </li>`;
          docCounter++;
        } else if (studentHousing === 'financiada') {
          let doc = '(  ) Comprovante de financiamento da casa própria.';
          checklistHTML += `<li>
                              <input type="checkbox" name="checked_docs[]" value="`+ docCounter +`"> `+ doc +
                              `<input type="hidden" name="checklist_items[]" value="`+ doc +`">
                            </li>`;
          docCounter++;
        } else if (studentHousing === 'cedida') {
          let doc = '(  ) Declaração de imóvel cedido.';
          checklistHTML += `<li>
                              <input type="checkbox" name="checked_docs[]" value="`+ docCounter +`"> `+ doc +
                              `<input type="hidden" name="checklist_items[]" value="`+ doc +`">
                            </li>`;
          docCounter++;
        }
        if (studentSingleOrphans === 'sim') {
          let doc = '(  ) Certidão de óbito do pai/mãe.';
          checklistHTML += `<li>
                              <input type="checkbox" name="checked_docs[]" value="`+ docCounter +`"> `+ doc +
                              `<input type="hidden" name="checklist_items[]" value="`+ doc +`">
                            </li>`;
          docCounter++;
        }
        if (studentCanguruScholarship === 'sim') {
          let doc = '(  ) Exame que comprove o tempo de gestação.';
          checklistHTML += `<li>
                              <input type="checkbox" name="checked_docs[]" value="`+ docCounter +`"> `+ doc +
                              `<input type="hidden" name="checklist_items[]" value="`+ doc +`">
                            </li>`;
          docCounter++;
        }
        if (studentTransportScholarship === 'sim') {
          let doc = '(  ) Declaração de gastos com transporte.';
          checklistHTML += `<li>
                              <input type="checkbox" name="checked_docs[]" value="`+ docCounter +`"> `+ doc +
                              `<input type="hidden" name="checklist_items[]" value="`+ doc +`">
                            </li>`;
          docCounter++;
        }
        checklistHTML += `</ul></div>`;
      }

      // --- Seção dos membros da família ---
      for (let i = 0; i < familySize; i++) {
        const memberName = document.getElementById(`member-name-${i}`).value.trim();
        const memberSituationSelect = document.getElementById(`member-situation-${i}`);
        const memberSituations = Array.from(memberSituationSelect.options)
                                      .filter(opt => opt.selected && opt.value !== '')
                                      .map(opt => opt.value);
        if (memberName !== '' && memberSituations.length > 0) {
          // Insere marcador de seção para o membro
          checklistHTML += `<input type="hidden" name="checklist_items[]" value="###MEMBRO: ${memberName}###">`;
          checklistHTML += `<div class="member-section">
                              <h3>Documentos de ${memberName}</h3>
                              <ul>`;
          let memberDocs = [];
          memberSituations.forEach(situation => {
            if (documentsBySituation[situation]) {
              memberDocs = memberDocs.concat(documentsBySituation[situation]);
            }
          });
          const uniqueMemberDocs = [...new Set(memberDocs)];
          uniqueMemberDocs.forEach(doc => {
            checklistHTML += `<li>
                                <input type="checkbox" name="checked_docs[]" value="`+ docCounter +`"> `+ doc +
                                `<input type="hidden" name="checklist_items[]" value="`+ doc +`">
                              </li>`;
            docCounter++;
          });
          checklistHTML += `</ul></div>`;
        }
      }

      // --- Seção de Outros Documentos ---
      if (locacaoImoveis === 'sim' || sitioChacara === 'sim') {
        // Insere marcador de seção para "Outros Documentos"
        checklistHTML += `<input type="hidden" name="checklist_items[]" value="###OUTROS###">`;
        checklistHTML += `<div class="member-section">
                            <h3>Outros Documentos</h3>
                            <ul>`;
        if (locacaoImoveis === 'sim') {
          let doc = '(  ) Cópia do contrato de locação e/ou recibos dos imóveis alugados.';
          checklistHTML += `<li>
                              <input type="checkbox" name="checked_docs[]" value="`+ docCounter +`"> `+ doc +
                              `<input type="hidden" name="checklist_items[]" value="`+ doc +`">
                            </li>`;
          docCounter++;
        }
        if (sitioChacara === 'sim') {
          let doc = '(  ) Escritura ou termo de uso emitido pelo INCRA para sítio/chácara/fazenda.';
          checklistHTML += `<li>
                              <input type="checkbox" name="checked_docs[]" value="`+ docCounter +`"> `+ doc +
                              `<input type="hidden" name="checklist_items[]" value="`+ doc +`">
                            </li>`;
          docCounter++;
        }
        checklistHTML += `</ul></div>`;
      }

      // Botão para enviar (salvar) o checklist
      checklistHTML += `<input type="submit" name="save_checklist" value="Salvar Checklist">`;

      document.getElementById("checklist-container").innerHTML = `<form method="POST" action="">` + checklistHTML + `</form>`;
    }
  </script>
</body>
</html>
