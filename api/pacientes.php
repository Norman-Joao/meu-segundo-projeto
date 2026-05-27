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
        if ($action === 'stats') {
            $total = $db->query("SELECT COUNT(*) FROM pacientes WHERE deleted_at IS NULL")->fetchColumn();
            $hoje = $db->query("SELECT COUNT(*) FROM pacientes WHERE DATE(created_at) = CURDATE() AND deleted_at IS NULL")->fetchColumn();
            $internados = $db->query("SELECT COUNT(*) FROM leitos_urgencia WHERE status='ocupado'")->fetchColumn();
            jsonResponse(['success' => true, 'total' => (int)$total, 'hoje' => (int)$hoje, 'internados' => (int)$internados]);
            break;
        }

        if ($action === 'list') {
            $pacientes = $db->query("SELECT id, nome_completo, cpf FROM pacientes WHERE deleted_at IS NULL ORDER BY nome_completo LIMIT 1000")->fetchAll();
            jsonResponse(['success' => true, 'pacientes' => $pacientes]);
            break;
        }

        if ($action === 'export') {
            $pacientes = $db->query("SELECT *, status_paciente AS status FROM pacientes WHERE deleted_at IS NULL ORDER BY nome_completo")->fetchAll();
            jsonResponse(['success' => true, 'pacientes' => $pacientes]);
            break;
        }

        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $db->prepare("SELECT *, status_paciente AS status FROM pacientes WHERE id = ? AND deleted_at IS NULL");
            $stmt->execute([$id]);
            $paciente = $stmt->fetch();
            if ($paciente) {
                $stmt2 = $db->prepare("SELECT MAX(data_hora_chegada) as ultimo FROM triagem WHERE paciente_id = ?");
                $stmt2->execute([$id]);
                $triagem = $stmt2->fetch();
                $paciente['ultimo_atendimento'] = $triagem['ultimo'] ?? null;
                jsonResponse(['success' => true, 'paciente' => $paciente]);
            } else {
                jsonResponse(['success' => false, 'erro' => 'Paciente não encontrado'], 404);
            }
            break;
        }

        $search = $_GET['search'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $where = 'WHERE p.deleted_at IS NULL';
        $params = [];
        if ($search) {
            $where .= " AND (p.nome_completo LIKE ? OR p.cpf LIKE ? OR p.numero_convenio LIKE ?)";
            $term = "%$search%";
            $params = [$term, $term, $term];
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM pacientes p $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT p.*, p.status_paciente AS status, (SELECT MAX(data_hora_chegada) FROM triagem WHERE paciente_id = p.id) as ultimo_atendimento FROM pacientes p $where ORDER BY p.nome_completo LIMIT ? OFFSET ?");
        $stmt->execute([...$params, $limit, $offset]);
        $pacientes = $stmt->fetchAll();

        jsonResponse(['success' => true, 'pacientes' => $pacientes, 'total' => $total, 'page' => $page]);
        break;

    case 'POST':
        $dados = lerCorpoRequisicao();
        $erro = validarCampos($dados, ['nome_completo', 'data_nascimento', 'cpf', 'telefone', 'endereco']);
        if ($erro) { jsonResponse(['success' => false, 'erro' => $erro]); break; }

        $cpf = preg_replace('/[^0-9A-Z]/i', '', strtoupper($dados['cpf']));
        if (!validarCPF($cpf)) { jsonResponse(['success' => false, 'erro' => 'BI inválido.']); break; }

        $stmt = $db->prepare("SELECT id FROM pacientes WHERE cpf = ?");
        $stmt->execute([$cpf]);
        if ($stmt->fetch()) { jsonResponse(['success' => false, 'erro' => 'BI já cadastrado.']); break; }

        $stmt = $db->prepare("INSERT INTO pacientes (nome_completo, data_nascimento, cpf, telefone, contato_emergencia, telefone_emergencia, endereco, observacoes, email, convenio, plano, numero_convenio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            sanitizar($dados['nome_completo']),
            $dados['data_nascimento'],
            $cpf,
            sanitizar($dados['telefone'] ?? ''),
            sanitizar($dados['contato_emergencia'] ?? ''),
            sanitizar($dados['telefone_emergencia'] ?? ''),
            sanitizar($dados['endereco']),
            sanitizar($dados['observacoes'] ?? ''),
            sanitizar($dados['email'] ?? ''),
            sanitizar($dados['convenio'] ?? 'Particular'),
            sanitizar($dados['plano'] ?? ''),
            sanitizar($dados['numero_convenio'] ?? ''),
        ]);
        $id = $db->lastInsertId();
        auditoria('criar', 'pacientes', $id, null, ['nome' => $dados['nome_completo'], 'cpf' => $cpf]);
        jsonResponse(['success' => true, 'id' => $id, 'mensagem' => 'Paciente cadastrado com sucesso!']);
        break;

    case 'PUT':
        $dados = lerCorpoRequisicao();
        $id = $_GET['id'] ?? $dados['id'] ?? null;
        if (!$id) { jsonResponse(['success' => false, 'erro' => 'ID do paciente é obrigatório.']); break; }

        $erro = validarCampos($dados, ['nome_completo', 'data_nascimento', 'cpf', 'telefone', 'endereco']);
        if ($erro) { jsonResponse(['success' => false, 'erro' => $erro]); break; }

        $cpf = preg_replace('/[^0-9A-Z]/i', '', strtoupper($dados['cpf']));
        if (!validarCPF($cpf)) { jsonResponse(['success' => false, 'erro' => 'BI inválido.']); break; }

        $stmt = $db->prepare("SELECT id, nome_completo FROM pacientes WHERE cpf = ? AND id != ?");
        $stmt->execute([$cpf, $id]);
        if ($stmt->fetch()) { jsonResponse(['success' => false, 'erro' => 'BI já cadastrado para outro paciente.']); break; }

        $stmt = $db->prepare("SELECT * FROM pacientes WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch();
        if (!$old) { jsonResponse(['success' => false, 'erro' => 'Paciente não encontrado.']); break; }

        $stmt = $db->prepare("UPDATE pacientes SET nome_completo=?, data_nascimento=?, cpf=?, telefone=?, contato_emergencia=?, telefone_emergencia=?, endereco=?, observacoes=?, email=?, convenio=?, plano=?, numero_convenio=? WHERE id=?");
        $stmt->execute([
            sanitizar($dados['nome_completo']),
            $dados['data_nascimento'],
            $cpf,
            sanitizar($dados['telefone'] ?? ''),
            sanitizar($dados['contato_emergencia'] ?? ''),
            sanitizar($dados['telefone_emergencia'] ?? ''),
            sanitizar($dados['endereco']),
            sanitizar($dados['observacoes'] ?? ''),
            sanitizar($dados['email'] ?? ''),
            sanitizar($dados['convenio'] ?? 'Particular'),
            sanitizar($dados['plano'] ?? ''),
            sanitizar($dados['numero_convenio'] ?? ''),
            $id,
        ]);
        auditoria('atualizar', 'pacientes', $id, ['nome' => $old['nome_completo']], ['nome' => $dados['nome_completo']]);
        jsonResponse(['success' => true, 'mensagem' => 'Paciente atualizado com sucesso!']);
        break;

    case 'DELETE':
        verificarNivel(['admin']);
        $id = $_GET['id'] ?? null;
        if (!$id) { jsonResponse(['success' => false, 'erro' => 'ID do paciente é obrigatório.']); break; }

        $stmt = $db->prepare("SELECT nome_completo FROM pacientes WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $paciente = $stmt->fetch();
        if (!$paciente) { jsonResponse(['success' => false, 'erro' => 'Paciente não encontrado.']); break; }

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE triagem SET deleted_at = NOW() WHERE paciente_id = ?")->execute([$id]);
            $db->prepare("UPDATE atendimentos a JOIN triagem t ON a.triagem_id = t.id SET a.deleted_at = NOW() WHERE t.paciente_id = ?")->execute([$id]);
            $db->prepare("UPDATE pacientes SET deleted_at = NOW(), status_paciente = 'inativo' WHERE id = ?")->execute([$id]);
            $db->commit();
            auditoria('excluir', 'pacientes', $id, ['nome' => $paciente['nome_completo']]);
            jsonResponse(['success' => true, 'mensagem' => 'Paciente removido com sucesso!']);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['success' => false, 'erro' => 'Erro ao remover paciente.'], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'erro' => 'Método não permitido.'], 405);
}
