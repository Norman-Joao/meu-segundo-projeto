<?php
require_once __DIR__ . '/config.php';
jsonHeader();
$_SESSION = [];
session_destroy();
jsonResponse(['mensagem' => 'Sessão encerrada.']);
