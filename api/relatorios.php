<?php
require_once __DIR__ . '/config.php';
jsonHeader();
verificarAuth();
verificarCSRF();

$db = getDB();
$period = isset($_GET['period']) ? (int)$_GET['period'] : null;
$inicio = $_GET['inicio'] ?? null;
$fim = $_GET['fim'] ?? null;
$tipo = $_GET['tipo'] ?? '';

if ($tipo) {
    switch ($tipo) {
        case 'pacientes':
            $pacientes = $db->query(
                "SELECT p.*, (SELECT MAX(data_hora_atendimento) FROM atendimentos a JOIN triagem t ON a.triagem_id = t.id WHERE t.paciente_id = p.id) as ultimo_atendimento FROM pacientes p ORDER BY p.nome_completo"
            )->fetchAll();
            jsonResponse(['success' => true, 'pacientes' => $pacientes]);
            break;

        case 'leitos':
            $total = $db->query("SELECT COUNT(*) FROM leitos_urgencia")->fetchColumn();
            $disponiveis = $db->query("SELECT COUNT(*) FROM leitos_urgencia WHERE status='disponivel'")->fetchColumn();
            $ocupados = $db->query("SELECT COUNT(*) FROM leitos_urgencia WHERE status='ocupado'")->fetchColumn();
            $manutencao = $db->query("SELECT COUNT(*) FROM leitos_urgencia WHERE status='manutencao'")->fetchColumn();
            $leitos = $db->query(
                "SELECT l.*, p.nome_completo as paciente_nome
                 FROM leitos_urgencia l
                 LEFT JOIN pacientes p ON l.paciente_id = p.id
                 ORDER BY l.ala, l.numero_leito"
            )->fetchAll();
            $taxa = $total > 0 ? round(($ocupados / $total) * 100, 1) : 0;
            jsonResponse([
                'success' => true, 'disponiveis' => (int)$disponiveis, 'ocupados' => (int)$ocupados,
                'manutencao' => (int)$manutencao, 'taxa_ocupacao' => $taxa, 'leitos' => $leitos
            ]);
            break;

        case 'triagem':
            $total = $db->query("SELECT COUNT(*) FROM triagem")->fetchColumn();
            $tempo = $db->query(
                "SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, data_hora_chegada, COALESCE(
                    (SELECT MIN(a.data_hora_atendimento) FROM atendimentos a WHERE a.triagem_id = triagem.id), NOW()
                ))), 0) FROM triagem WHERE status = 'finalizado'"
            )->fetchColumn();
            $triagens = $db->query(
                "SELECT t.*, p.nome_completo as paciente_nome
                 FROM triagem t JOIN pacientes p ON t.paciente_id = p.id
                 ORDER BY t.data_hora_chegada DESC LIMIT 200"
            )->fetchAll();
            jsonResponse([
                'success' => true, 'total' => (int)$total, 'tempo_medio' => round((float)$tempo),
                'triagens' => $triagens
            ]);
            break;

        case 'financeiro':
            $totalAtt = $db->query("SELECT COUNT(*) FROM atendimentos")->fetchColumn();
            $stmt = $db->query(
                "SELECT p.convenio, COUNT(*) as total
                 FROM atendimentos a
                 JOIN triagem t ON a.triagem_id = t.id
                 JOIN pacientes p ON t.paciente_id = p.id
                 GROUP BY p.convenio"
            );
            $convenios = $stmt->fetchAll();
            $detalhe = [];
            $convTotal = 0;
            $particular = 0;
            foreach ($convenios as $row) {
                $detalhe[$row['convenio']] = (int)$row['total'];
                if (strtolower($row['convenio']) === 'particular') {
                    $particular = (int)$row['total'];
                } else {
                    $convTotal += (int)$row['total'];
                }
            }
            $total = (int)$totalAtt;
            jsonResponse([
                'success' => true, 'total_atendimentos' => $total,
                'particular' => $particular, 'percent_particular' => $total > 0 ? round(($particular/$total)*100, 1) : 0,
                'convenios_total' => $convTotal, 'percent_convenios' => $total > 0 ? round(($convTotal/$total)*100, 1) : 0,
                'detalhe_convenios' => $detalhe
            ]);
            break;

        default:
            jsonResponse(['success' => false, 'erro' => 'Tipo de relatório inválido.']);
    }
    exit;
}

// Monta WHERE por período
$where = '';
$params = [];
if ($inicio && $fim) {
    $where = "WHERE a.data_hora_atendimento >= ? AND a.data_hora_atendimento <= ?";
    $params = [$inicio . ' 00:00:00', $fim . ' 23:59:59'];
} elseif ($period) {
    $where = "WHERE a.data_hora_atendimento >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $params = [$period];
}

// Summary
$summary = [];
$stmt = $db->prepare("SELECT COUNT(*) FROM atendimentos a $where");
$stmt->execute($params);
$summary['total_atendimentos'] = (int)$stmt->fetchColumn();

$stmt = $db->prepare(
    "SELECT COUNT(DISTINCT t.paciente_id) FROM atendimentos a
     JOIN triagem t ON a.triagem_id = t.id $where"
);
$stmt->execute($params);
$summary['total_pacientes'] = (int)$stmt->fetchColumn();

$totalAtt = $summary['total_atendimentos'];
if ($inicio && $fim) {
    $dias = max(1, (int)ceil((strtotime($fim) - strtotime($inicio)) / 86400));
} else {
    $dias = $period ?: 30;
}
$summary['media_diaria'] = $dias > 0 ? round($totalAtt / $dias, 1) : 0;

$stmt = $db->prepare(
    "SELECT COALESCE(AVG(TIMESTAMPDIFF(MINUTE, t.data_hora_chegada, a.data_hora_atendimento)), 0)
     FROM atendimentos a JOIN triagem t ON a.triagem_id = t.id $where"
);
$stmt->execute($params);
$summary['tempo_medio_espera'] = round((float)$stmt->fetchColumn());

$summary['trend_atendimentos'] = 0;
$summary['trend_pacientes'] = 0;
$summary['trend_media'] = 0;
$summary['trend_tempo'] = 0;

// Atendimentos por dia
$stmt = $db->prepare(
    "SELECT DATE(a.data_hora_atendimento) as data, COUNT(*) as total
     FROM atendimentos a $where
     GROUP BY DATE(a.data_hora_atendimento) ORDER BY data"
);
$stmt->execute($params);
$atendimentosPorDia = $stmt->fetchAll();

// Prioridades
$stmt = $db->prepare(
    "SELECT t.prioridade, COUNT(*) as total
     FROM atendimentos a JOIN triagem t ON a.triagem_id = t.id $where
     GROUP BY t.prioridade"
);
$stmt->execute($params);
$prioridades = ['emergencia' => 0, 'alta' => 0, 'media' => 0, 'baixa' => 0];
foreach ($stmt->fetchAll() as $row) {
    $prioridades[$row['prioridade']] = (int)$row['total'];
}

// Convênios
$stmt = $db->prepare(
    "SELECT p.convenio, COUNT(*) as total
     FROM atendimentos a
     JOIN triagem t ON a.triagem_id = t.id
     JOIN pacientes p ON t.paciente_id = p.id $where
     GROUP BY p.convenio"
);
$stmt->execute($params);
$convenios = [];
foreach ($stmt->fetchAll() as $row) {
    $convenios[$row['convenio']] = (int)$row['total'];
}

// Horários (24h)
$stmt = $db->prepare(
    "SELECT HOUR(a.data_hora_atendimento) as hora, COUNT(*) as total
     FROM atendimentos a $where
     GROUP BY HOUR(a.data_hora_atendimento) ORDER BY hora"
);
$stmt->execute($params);
$horarios = array_fill(0, 24, 0);
foreach ($stmt->fetchAll() as $row) {
    $horarios[(int)$row['hora']] = (int)$row['total'];
}

// Atendimentos detalhados
$stmt = $db->prepare(
    "SELECT a.*, t.prioridade, t.status as triagem_status, p.nome_completo as paciente_nome
     FROM atendimentos a
     JOIN triagem t ON a.triagem_id = t.id
     JOIN pacientes p ON t.paciente_id = p.id $where
     ORDER BY a.data_hora_atendimento DESC LIMIT 500"
);
$stmt->execute($params);
$atendimentos = $stmt->fetchAll();

jsonResponse([
    'success' => true,
    'summary' => $summary,
    'atendimentos_por_dia' => $atendimentosPorDia,
    'prioridades' => $prioridades,
    'convenios' => $convenios,
    'horarios' => $horarios,
    'atendimentos' => $atendimentos,
]);
