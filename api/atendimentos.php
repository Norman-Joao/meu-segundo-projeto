<?php
require_once __DIR__ . '/config.php';
jsonHeader();
verificarAuth();
verificarCSRF();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? '';

        if ($action === 'ultimos') {
            $stmt = $db->query(
                "SELECT a.*, t.paciente_id, p.nome_completo as paciente_nome, t.prioridade
                 FROM atendimentos a
                 JOIN triagem t ON a.triagem_id = t.id
                 JOIN pacientes p ON t.paciente_id = p.id
                 ORDER BY a.data_hora_atendimento DESC LIMIT 10"
            );
            jsonResponse(['success' => true, 'atendimentos' => $stmt->fetchAll()]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare(
                "SELECT a.*, t.paciente_id, p.nome_completo as paciente_nome, t.prioridade, t.status as triagem_status
                 FROM atendimentos a
                 JOIN triagem t ON a.triagem_id = t.id
                 JOIN pacientes p ON t.paciente_id = p.id
                 WHERE a.id = ?"
            );
            $stmt->execute([$id]);
            $atendimento = $stmt->fetch();
            if ($atendimento) {
                jsonResponse(['success' => true, 'atendimento' => $atendimento]);
            } else {
                jsonResponse(['success' => false, 'erro' => 'Atendimento não encontrado'], 404);
            }
            break;
        }

        $triagem_id = $_GET['triagem_id'] ?? null;
        $paciente_id = $_GET['paciente_id'] ?? null;

        if ($triagem_id) {
            $stmt = $db->prepare(
                "SELECT a.*, t.paciente_id, p.nome_completo as paciente_nome
                 FROM atendimentos a
                 JOIN triagem t ON a.triagem_id = t.id
                 JOIN pacientes p ON t.paciente_id = p.id
                 WHERE a.triagem_id = ?"
            );
            $stmt->execute([$triagem_id]);
            jsonResponse(['success' => true, 'atendimentos' => $stmt->fetchAll()]);
        } elseif ($paciente_id) {
            $stmt = $db->prepare(
                "SELECT a.*, t.paciente_id, p.nome_completo as paciente_nome
                 FROM atendimentos a
                 JOIN triagem t ON a.triagem_id = t.id
                 JOIN pacientes p ON t.paciente_id = p.id
                 WHERE t.paciente_id = ?
                 ORDER BY a.data_hora_atendimento DESC"
            );
            $stmt->execute([$paciente_id]);
            jsonResponse(['success' => true, 'atendimentos' => $stmt->fetchAll()]);
        } else {
            $stmt = $db->query(
                "SELECT a.*, t.paciente_id, p.nome_completo as paciente_nome, t.prioridade
                 FROM atendimentos a
                 JOIN triagem t ON a.triagem_id = t.id
                 JOIN pacientes p ON t.paciente_id = p.id
                 ORDER BY a.data_hora_atendimento DESC LIMIT 50"
            );
            jsonResponse(['success' => true, 'atendimentos' => $stmt->fetchAll()]);
        }
        break;

    case 'POST':
        $dados = lerCorpoRequisicao();
        $erro = validarCampos($dados, ['triagem_id', 'medico_responsavel', 'diagnostico']);
        if ($erro) { jsonResponse(['success' => false, 'erro' => $erro]); break; }

        $stmt = $db->prepare("SELECT paciente_id FROM triagem WHERE id = ?");
        $stmt->execute([$dados['triagem_id']]);
        $triagem = $stmt->fetch();
        if (!$triagem) { jsonResponse(['success' => false, 'erro' => 'Triagem não encontrada.']); break; }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "INSERT INTO atendimentos (triagem_id, paciente_id, medico_responsavel, diagnostico, prescricao, exames_solicitados, data_hora_atendimento, encaminhamento, pressao_arterial, frequencia_cardiaca, temperatura, saturacao_oxigenio, cid_code, observacoes)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $dados['triagem_id'],
                $triagem['paciente_id'],
                sanitizar($dados['medico_responsavel']),
                sanitizar($dados['diagnostico']),
                sanitizar($dados['prescricao'] ?? ''),
                sanitizar($dados['exames_solicitados'] ?? ''),
                sanitizar($dados['encaminhamento'] ?? ''),
                sanitizar($dados['pressao_arterial'] ?? ''),
                ($dados['frequencia_cardiaca'] ?? '') !== '' ? (int)$dados['frequencia_cardiaca'] : null,
                ($dados['temperatura'] ?? '') !== '' ? (float)$dados['temperatura'] : null,
                ($dados['saturacao_oxigenio'] ?? '') !== '' ? (int)$dados['saturacao_oxigenio'] : null,
                sanitizar($dados['cid_code'] ?? ''),
                sanitizar($dados['observacoes'] ?? ''),
            ]);

            $stmt2 = $db->prepare("UPDATE triagem SET status = 'finalizado' WHERE id = ? AND status IN ('aguardando', 'em_atendimento')");
            $stmt2->execute([$dados['triagem_id']]);

            $id = $db->lastInsertId();
            $db->commit();
            auditoria('criar', 'atendimentos', $id, null, ['triagem_id' => $dados['triagem_id']]);
            jsonResponse(['success' => true, 'id' => $id, 'mensagem' => 'Atendimento registrado com sucesso!']);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['success' => false, 'erro' => 'Erro ao registrar atendimento.'], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'erro' => 'Método não permitido.'], 405);
}
