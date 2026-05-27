<?php
/**
 * Diagnóstico de conexão com o banco de dados (requer autenticação)
 */
require_once __DIR__ . '/config.php';
jsonHeader();
verificarAuth();
verificarCSRF();

try {
    $db = getDB();
    $stmt = $db->query("SELECT DATABASE() as db");
    $info = $stmt->fetch();

    $stmt = $db->query("SHOW TABLES");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    jsonResponse([
        'status' => 'ok',
        'mensagem' => 'Conexão com o banco de dados OK.',
        'banco' => $info['db'],
        'total_tabelas' => count($tabelas),
    ]);
} catch (Exception $e) {
    jsonResponse([
        'status' => 'erro',
        'mensagem' => 'Falha na conexão com o banco de dados.',
    ], 500);
}
