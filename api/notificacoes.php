<?php
require_once __DIR__ . '/config.php';
jsonHeader();
verificarAuth();
verificarCSRF();

$db = getDB();

$em_espera = $db->query("SELECT COUNT(*) FROM triagem WHERE status = 'aguardando'")->fetchColumn();
$em_atendimento = $db->query("SELECT COUNT(*) FROM triagem WHERE status = 'em_atendimento'")->fetchColumn();
$leitos_disponiveis = $db->query("SELECT COUNT(*) FROM leitos_urgencia WHERE status = 'disponivel'")->fetchColumn();
$total_leitos = $db->query("SELECT COUNT(*) FROM leitos_urgencia")->fetchColumn();

$ultimas_triagens = $db->query(
    "SELECT t.id, t.prioridade, t.data_hora_chegada, p.nome_completo
     FROM triagem t JOIN pacientes p ON t.paciente_id = p.id
     WHERE t.status = 'aguardando'
     ORDER BY FIELD(t.prioridade, 'emergencia','alta','media','baixa'), t.data_hora_chegada
     LIMIT 5"
)->fetchAll();

$notificacoes = [];

foreach ($ultimas_triagens as $t) {
    $icones = [
        'emergencia' => 'fa-exclamation-triangle',
        'alta' => 'fa-circle-exclamation',
        'media' => 'fa-clock',
        'baixa' => 'fa-circle-info'
    ];
    $notificacoes[] = [
        'tipo' => 'triagem',
        'icone' => $icones[$t['prioridade']] ?? 'fa-bell',
        'cor' => $t['prioridade'] === 'emergencia' ? '#dc2626' : ($t['prioridade'] === 'alta' ? '#ea580c' : ($t['prioridade'] === 'media' ? '#ca8a04' : '#16a34a')),
        'mensagem' => $t['nome_completo'] . ' aguardando triagem',
        'prioridade' => strtoupper($t['prioridade']),
        'data' => $t['data_hora_chegada']
    ];
}

if ((int)$leitos_disponiveis <= 2 && (int)$total_leitos > 0) {
    $notificacoes[] = [
        'tipo' => 'leitos',
        'icone' => 'fa-bed',
        'cor' => '#dc2626',
        'mensagem' => "Apenas {$leitos_disponiveis} leitos disponíveis",
        'prioridade' => 'ATENÇÃO',
        'data' => date('Y-m-d H:i:s')
    ];
}

$total = (int)$em_espera + (int)$em_atendimento;

jsonResponse([
    'success' => true,
    'total' => $total,
    'notificacoes' => $notificacoes
]);
