<?php
require_once __DIR__ . '/config.php';
jsonHeader();

if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    jsonResponse([
        'logado' => true,
        'usuario' => $_SESSION['usuario'] ?? '',
        'nome_exibicao' => $_SESSION['nome_exibicao'] ?? 'Usuário',
        'nivel' => $_SESSION['nivel'] ?? 'recepcionista',
        'csrf_token' => gerarTokenCSRF(),
    ]);
} else {
    jsonResponse(['logado' => false], 401);
}
