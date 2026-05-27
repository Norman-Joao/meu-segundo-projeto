<?php
require_once __DIR__ . '/config.php';
jsonHeader();
verificarAuth();
verificarCSRF();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'fila') {
            $stmt = $db->query(
                "SELECT t.*, p.nome_completo as paciente_nome, p.cpf,
                        TIMESTAMPDIFF(MINUTE, t.data_hora_chegada, NOW()) as tempo_espera_minutos
                 FROM triagem t
                 JOIN pacientes p ON t.paciente_id = p.id
                 WHERE t.status IN ('aguardando', 'em_atendimento')
                 ORDER BY
                    CASE t.prioridade
                        WHEN 'emergencia' THEN 1
                        WHEN 'alta' THEN 2
                        WHEN 'media' THEN 3
                        WHEN 'baixa' THEN 4
                    END,
                    t.data_hora_chegada ASC"
            );
            $fila = $stmt->fetchAll();
            jsonResponse(['success' => true, 'fila' => $fila]);
            break;
        }

        if ($action === 'historico') {
            $stmt = $db->query(
                "SELECT t.*, p.nome_completo as paciente_nome
                 FROM triagem t
                 JOIN pacientes p ON t.paciente_id = p.id
                 WHERE t.status = 'finalizado'
                 ORDER BY t.data_hora_chegada DESC LIMIT 50"
            );
            jsonResponse(['success' => true, 'historico' => $stmt->fetchAll()]);
            break;
        }

        jsonResponse(['success' => false, 'erro' => 'Ação inválida.']);
        break;

    case 'POST':
        $dados = lerCorpoRequisicao();
        $erro = validarCampos($dados, ['paciente_id', 'sintomas']);
        if ($erro) { jsonResponse(['success' => false, 'erro' => $erro]); break; }

        $fc = $dados['frequencia_cardiaca'] !== null ? (int)$dados['frequencia_cardiaca'] : null;
        $temp = $dados['temperatura'] !== null ? (float)$dados['temperatura'] : null;
        $sat = $dados['saturacao_oxigenio'] !== null ? (int)$dados['saturacao_oxigenio'] : null;
        $prioridade = $fc !== null || $temp !== null || $sat !== null
            ? calcularPrioridade($fc, $temp, $sat)
            : 'baixa';

        try {
            $stmt = $db->prepare(
                "INSERT INTO triagem (paciente_id, data_hora_chegada, pressao_arterial, frequencia_cardiaca, temperatura, saturacao_oxigenio, frequencia_respiratoria, glicemia, dor, sintomas, prioridade)
                 VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $dados['paciente_id'],
                sanitizar($dados['pressao_arterial']),
                $fc, $temp, $sat,
                isset($dados['frequencia_respiratoria']) ? (int)$dados['frequencia_respiratoria'] : null,
                isset($dados['glicemia']) ? (int)$dados['glicemia'] : null,
                isset($dados['dor']) ? (int)$dados['dor'] : null,
                sanitizar($dados['sintomas']),
                $prioridade,
            ]);
            jsonResponse([
                'success' => true,
                'id' => $db->lastInsertId(),
                'prioridade' => $prioridade,
                'mensagem' => "Triagem registrada com prioridade: " . strtoupper($prioridade) . "!",
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao registrar triagem: " . $e->getMessage());
            jsonResponse(['success' => false, 'erro' => 'Erro ao registrar triagem.']);
        }
        break;

    case 'PUT':
        $dados = lerCorpoRequisicao();
        $actionPut = $dados['action'] ?? $_GET['action'] ?? '';

        if ($actionPut === 'iniciar') {
            $id = $dados['triagem_id'] ?? $dados['id'] ?? null;
            if (!$id) { jsonResponse(['success' => false, 'erro' => 'ID da triagem é obrigatório.']); break; }
            $stmt = $db->prepare("UPDATE triagem SET status = 'em_atendimento' WHERE id = ? AND status = 'aguardando'");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) { jsonResponse(['success' => false, 'erro' => 'Triagem não encontrada ou já em atendimento.']); break; }
            jsonResponse(['success' => true, 'mensagem' => 'Atendimento iniciado!']);
        } elseif ($actionPut === 'finalizar') {
            $id = $dados['triagem_id'] ?? $dados['id'] ?? null;
            if (!$id) { jsonResponse(['success' => false, 'erro' => 'ID da triagem é obrigatório.']); break; }
            $stmt = $db->prepare("UPDATE triagem SET status = 'finalizado' WHERE id = ? AND status = 'em_atendimento'");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) { jsonResponse(['success' => false, 'erro' => 'Triagem não encontrada ou não está em atendimento.']); break; }
            jsonResponse(['success' => true, 'mensagem' => 'Atendimento finalizado!']);
        } elseif ($actionPut === 'reclassificar') {
            $id = $dados['triagem_id'] ?? null;
            $novaPrioridade = $dados['prioridade'] ?? null;
            if (!$id || !$novaPrioridade) { jsonResponse(['success' => false, 'erro' => 'Dados incompletos para reclassificação.']); break; }
            $stmt = $db->prepare("UPDATE triagem SET prioridade = ? WHERE id = ?");
            $stmt->execute([$novaPrioridade, $id]);
            jsonResponse(['success' => true, 'mensagem' => 'Prioridade reclassificada com sucesso!']);
        } else {
            jsonResponse(['success' => false, 'erro' => 'Ação inválida.']);
        }
        break;

    default:
        jsonResponse(['success' => false, 'erro' => 'Método não permitido.'], 405);
}
