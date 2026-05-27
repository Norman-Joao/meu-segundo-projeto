<?php
require_once __DIR__ . '/config.php';
jsonHeader();

try {
    verificarRateLimit();

    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($usuario) || empty($senha)) {
        jsonResponse(['success' => false, 'erro' => 'Usuário e senha são obrigatórios.']);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($senha, $user['senha'])) {
        jsonResponse(['success' => false, 'erro' => 'Usuário ou senha inválidos.']);
    }

    limparRateLimit();
    session_regenerate_id(true);

    $_SESSION['logado'] = true;
    $_SESSION['usuario'] = $user['usuario'];
    $_SESSION['nome_exibicao'] = $user['nome_exibicao'];
    $_SESSION['nivel'] = $user['nivel'] ?? 'recepcionista';
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    auditoria('login', 'usuarios', $user['id']);

    jsonResponse([
        'success' => true,
        'mensagem' => 'Login realizado com sucesso!',
        'usuario'  => $user['nome_exibicao'],
        'nivel'    => $user['nivel'],
        'csrf_token' => $_SESSION['csrf_token'],
    ]);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'erro' => 'Erro interno no servidor.',
    ], 500);
}
