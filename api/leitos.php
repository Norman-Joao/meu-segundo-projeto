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

        if ($action === 'historico') {
            // Retorna histórico simples (os leitos atuais já mostram o status)
            $stmt = $db->query(
                "SELECT l.id, l.numero_leito, l.ala, l.status, l.data_ocupacao,
                        p.nome_completo as paciente_nome,
                        TIMESTAMPDIFF(HOUR, l.data_ocupacao, NOW()) as horas_ocupado
                 FROM leitos_urgencia l
                 LEFT JOIN pacientes p ON l.paciente_id = p.id
                 WHERE l.status = 'ocupado'
                 ORDER BY l.data_ocupacao DESC"
            );
            $historico = $stmt->fetchAll();
            // Mapear para formato esperado
            $result = array_map(function($h) {
                return [
                    'data_hora' => $h['data_ocupacao'],
                    'numero_leito' => $h['numero_leito'],
                    'acao' => 'ocupar',
                    'paciente_nome' => $h['paciente_nome'],
                    'responsavel' => 'Sistema',
                ];
            }, $historico);
            jsonResponse(['success' => true, 'historico' => $result]);
            break;
        }

        // Listagem principal
        $stmt = $db->query(
            "SELECT l.*, p.nome_completo as paciente_nome,
                    TIMESTAMPDIFF(HOUR, l.data_ocupacao, NOW()) as horas_ocupado
             FROM leitos_urgencia l
             LEFT JOIN pacientes p ON l.paciente_id = p.id
             ORDER BY l.ala, l.numero_leito"
        );
        $leitos = $stmt->fetchAll();

        $total = count($leitos);
        $disponiveis = 0;
        $ocupados = 0;
        $manutencao = 0;

        foreach ($leitos as &$l) {
            $l['tempo_ocupacao'] = $l['horas_ocupado'] ? $l['horas_ocupado'] . 'h' : null;
            if ($l['status'] === 'disponivel') $disponiveis++;
            elseif ($l['status'] === 'ocupado') $ocupados++;
            else $manutencao++;
        }

        jsonResponse([
            'success' => true,
            'leitos' => $leitos,
            'total_leitos' => $total,
            'disponiveis' => $disponiveis,
            'ocupados' => $ocupados,
            'manutencao' => $manutencao,
        ]);
        break;

    case 'POST':
        $dados = lerCorpoRequisicao();
        $actionPost = $dados['action'] ?? '';

        try {
            if ($actionPost === 'ocupar') {
                $erro = validarCampos($dados, ['leito_id', 'paciente_id']);
                if ($erro) { jsonResponse(['success' => false, 'erro' => $erro]); break; }

                $stmt = $db->prepare("UPDATE leitos_urgencia SET status = 'ocupado', paciente_id = ?, data_ocupacao = NOW() WHERE id = ? AND status = 'disponivel'");
                $stmt->execute([$dados['paciente_id'], $dados['leito_id']]);
                if ($stmt->rowCount() === 0) { jsonResponse(['success' => false, 'erro' => 'Leito não disponível ou não encontrado.']); break; }

                $db->prepare("UPDATE pacientes SET status_paciente = 'internado' WHERE id = ?")->execute([$dados['paciente_id']]);
                auditoria('ocupar', 'leitos', $dados['leito_id'], null, ['paciente_id' => $dados['paciente_id']]);
                jsonResponse(['success' => true, 'mensagem' => 'Leito ocupado com sucesso!']);
            } elseif ($actionPost === 'liberar') {
                $stmt = $db->prepare("SELECT paciente_id FROM leitos_urgencia WHERE id = ? AND status = 'ocupado'");
                $stmt->execute([$dados['leito_id']]);
                $leito = $stmt->fetch();

                $stmt = $db->prepare("UPDATE leitos_urgencia SET status = 'disponivel', paciente_id = NULL, data_ocupacao = NULL WHERE id = ? AND status = 'ocupado'");
                $stmt->execute([$dados['leito_id']]);
                if ($stmt->rowCount() === 0) { jsonResponse(['success' => false, 'erro' => 'Leito não está ocupado.']); break; }

                if ($leito && $leito['paciente_id']) {
                    $db->prepare("UPDATE pacientes SET status_paciente = 'alta' WHERE id = ?")->execute([$leito['paciente_id']]);
                }
                auditoria('liberar', 'leitos', $dados['leito_id'], null, ['leito_id' => $dados['leito_id']]);
                jsonResponse(['success' => true, 'mensagem' => 'Leito liberado com sucesso!']);
            } elseif ($actionPost === 'criar') {
                $erro = validarCampos($dados, ['numero_leito', 'ala']);
                if ($erro) { jsonResponse(['success' => false, 'erro' => $erro]); break; }

                $stmt = $db->prepare("SELECT id FROM leitos_urgencia WHERE numero_leito = ?");
                $stmt->execute([$dados['numero_leito']]);
                if ($stmt->fetch()) {
                    jsonResponse(['success' => false, 'erro' => 'Já existe um leito com este número.']);
                    break;
                }

                $stmt = $db->prepare("INSERT INTO leitos_urgencia (numero_leito, ala, status) VALUES (?, ?, 'disponivel')");
                $stmt->execute([$dados['numero_leito'], $dados['ala']]);
                auditoria('criar', 'leitos', $db->lastInsertId(), null, ['numero_leito' => $dados['numero_leito'], 'ala' => $dados['ala']]);
                jsonResponse(['success' => true, 'mensagem' => 'Leito criado com sucesso!']);
            } elseif ($actionPost === 'manutencao') {
                $novoStatus = $dados['status'] ?? 'manutencao';
                $stmt = $db->prepare("UPDATE leitos_urgencia SET status = ?, paciente_id = NULL, data_ocupacao = NULL WHERE id = ?");
                $stmt->execute([$novoStatus, $dados['leito_id']]);
                jsonResponse(['success' => true, 'mensagem' => 'Status do leito atualizado!']);
            } else {
                jsonResponse(['success' => false, 'erro' => 'Ação inválida.']);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'erro' => 'Erro na operação.'], 500);
        }
        break;

    default:
        jsonResponse(['success' => false, 'erro' => 'Método não permitido.'], 405);
}
