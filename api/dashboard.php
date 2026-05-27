<?php
require_once __DIR__ . '/config.php';
jsonHeader();
verificarAuth();
verificarCSRF();

$db = getDB();
$period = max(1, min(365, (int)($_GET['period'] ?? 7)));

// Total de pacientes em espera
$em_espera = (int)$db->query("SELECT COUNT(*) FROM triagem WHERE status = 'aguardando'")->fetchColumn();
$em_atendimento = (int)$db->query("SELECT COUNT(*) FROM triagem WHERE status = 'em_atendimento'")->fetchColumn();

// Leitos
$total_leitos = (int)$db->query("SELECT COUNT(*) FROM leitos_urgencia")->fetchColumn();
$leitos_disponiveis = (int)$db->query("SELECT COUNT(*) FROM leitos_urgencia WHERE status = 'disponivel'")->fetchColumn();
$leitos_ocupados = (int)$db->query("SELECT COUNT(*) FROM leitos_urgencia WHERE status = 'ocupado'")->fetchColumn();
$leitos_manutencao = (int)$db->query("SELECT COUNT(*) FROM leitos_urgencia WHERE status = 'manutencao'")->fetchColumn();

// Atendimentos 24h
$atendimentos_24h = (int)$db->query(
    "SELECT COUNT(*) FROM atendimentos WHERE data_hora_atendimento >= NOW() - INTERVAL 24 HOUR"
)->fetchColumn();

// Tempo médio de espera (minutos)
$stmt = $db->query(
    "SELECT AVG(TIMESTAMPDIFF(MINUTE, t.data_hora_chegada, a.data_hora_atendimento)) as tempo_medio
     FROM atendimentos a JOIN triagem t ON a.triagem_id = t.id
     WHERE a.data_hora_atendimento >= NOW() - INTERVAL 30 DAY"
);
$tempo_medio_espera = round($stmt->fetchColumn() ?? 0);

// Atendimentos por prioridade (período selecionado)
$stmt = $db->prepare(
    "SELECT t.prioridade, COUNT(*) as total
     FROM atendimentos a JOIN triagem t ON a.triagem_id = t.id
     WHERE a.data_hora_atendimento >= NOW() - INTERVAL ? DAY
     GROUP BY t.prioridade"
);
$stmt->execute([$period]);
$prioridades = ['emergencia' => 0, 'alta' => 0, 'media' => 0, 'baixa' => 0];
while ($row = $stmt->fetch()) {
    $prioridades[$row['prioridade']] = (int)$row['total'];
}

// Fluxo horário (últimas 24h - mock com dados reais se disponíveis)
$fluxo_horario = array_fill(0, 24, 0);
$stmt = $db->query(
    "SELECT HOUR(data_hora_atendimento) as hora, COUNT(*) as total
     FROM atendimentos WHERE data_hora_atendimento >= NOW() - INTERVAL 24 HOUR
     GROUP BY HOUR(data_hora_atendimento) ORDER BY hora"
);
while ($row = $stmt->fetch()) {
    $fluxo_horario[(int)$row['hora']] = (int)$row['total'];
}

// Convênios
$stmt = $db->query("SELECT convenio, COUNT(*) as total FROM pacientes GROUP BY convenio ORDER BY total DESC");
$convenios = [];
while ($row = $stmt->fetch()) {
    $convenios[$row['convenio'] ?: 'Particular'] = (int)$row['total'];
}

// Últimos atendimentos
$stmt = $db->query(
    "SELECT a.*, t.paciente_id, p.nome_completo as paciente_nome, t.prioridade
     FROM atendimentos a JOIN triagem t ON a.triagem_id = t.id
     JOIN pacientes p ON t.paciente_id = p.id
     ORDER BY a.data_hora_atendimento DESC LIMIT 10"
);
$ultimos_atendimentos = $stmt->fetchAll();

jsonResponse([
    'success' => true,
    'em_espera' => $em_espera,
    'em_atendimento' => $em_atendimento,
    'leitos_disponiveis' => $leitos_disponiveis,
    'leitos_ocupados' => $leitos_ocupados,
    'leitos_manutencao' => $leitos_manutencao,
    'total_leitos' => $total_leitos,
    'atendimentos_24h' => $atendimentos_24h,
    'tempo_medio_espera' => $tempo_medio_espera,
    'prioridades' => $prioridades,
    'fluxo_horario' => $fluxo_horario,
    'convenios' => $convenios,
    'ultimos_atendimentos' => $ultimos_atendimentos,
]);
