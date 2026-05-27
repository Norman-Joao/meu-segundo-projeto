<?php
/**
 * Configuração do Banco de Dados e Utilitários
 * Hospital do Ramiros - Sistema de Gestão Hospitalar
 */

// --- Configurações do Banco ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'hospital_urgencia');
define('DB_USER', 'usuario');
define('DB_PASS', 'senha');
define('DB_CHARSET', 'utf8mb4');

// --- Configurações da Sessão ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Configurações de Erro (ambiente dev) ---
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

/**
 * Obtém conexão PDO com o banco de dados
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log("Falha na conexão com BD: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'erro' => 'Erro na conexão com o banco de dados.',
                'dica' => 'Verifique se o MySQL está rodando e se o banco hospital_urgencia foi criado (importe database.sql)'
            ]);
            exit;
        }
    }
    return $pdo;
}

/**
 * Define cabeçalhos CORS e Content-Type JSON
 */
function jsonHeader(): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    header('Access-Control-Expose-Headers: X-CSRF-Token');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * Retorna resposta JSON e encerra
 */
function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Retorna resposta de erro JSON
 */
function jsonError(string $mensagem, int $status = 400): void {
    jsonResponse(['erro' => $mensagem], $status);
}

/**
 * Valida se campos obrigatórios existem no array
 */
function validarCampos(array $dados, array $obrigatorios): ?string {
    foreach ($obrigatorios as $campo) {
        if (!isset($dados[$campo]) || trim($dados[$campo]) === '') {
            return "O campo '$campo' é obrigatório.";
        }
    }
    return null;
}

/**
 * Sanitiza string de input
 */
function sanitizar(string $valor): string {
    return htmlspecialchars(strip_tags(trim($valor)), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida BI angolano (9 dígitos + 2 letras + 3 dígitos)
 */
function validarBI(string $bi): bool {
    $val = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', $bi));
    if (strlen($val) < 10 || strlen($val) > 14) return false;
    return (bool)preg_match('/^\d{9}[A-Z]{2}\d{3}$/', $val);
}

/**
 * Alias mantendo compatibilidade
 */
function validarCPF(string $cpf): bool {
    return validarBI($cpf);
}

/**
 * Verifica se usuário está autenticado (para APIs - retorna JSON)
 */
function verificarAuth(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        jsonError('Não autorizado. Faça login primeiro.', 401);
    }
}

/**
 * Verifica autenticação para páginas (redireciona para login)
 */
function verificarAuthPagina(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
        header('Location: login.html');
        exit;
    }
}

/**
 * Gera e armazena token CSRF na sessão
 */
function gerarTokenCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida token CSRF da requisição
 */
function validarTokenCSRF(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        jsonError('Token de segurança inválido. Recarregue a página.', 403);
    }
}

/**
 * Verifica CSRF em métodos de escrita (POST, PUT, DELETE)
 */
function verificarCSRF(): void {
    if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
        validarTokenCSRF();
    }
}

/**
 * Rate limiting - controla tentativas de login
 */
function verificarRateLimit(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $arquivo = sys_get_temp_dir() . '/login_attempts_' . md5($ip) . '.json';
    $maxTentativas = 5;
    $intervalo = 900; // 15 minutos

    $dados = ['tentativas' => 0, 'primeira' => time()];
    if (file_exists($arquivo)) {
        $dados = json_decode(file_get_contents($arquivo), true) ?: $dados;
    }

    // Reset se já passou o intervalo
    if (time() - $dados['primeira'] > $intervalo) {
        $dados = ['tentativas' => 0, 'primeira' => time()];
    }

    $dados['tentativas']++;

    if ($dados['tentativas'] > $maxTentativas) {
        $restante = $intervalo - (time() - $dados['primeira']);
        jsonResponse([
            'success' => false,
            'erro' => "Muitas tentativas. Tente novamente em " . ceil($restante / 60) . " minuto(s)."
        ]);
    }

    file_put_contents($arquivo, json_encode($dados), LOCK_EX);
}

/**
 * Limpa rate limit após login bem-sucedido
 */
function limparRateLimit(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $arquivo = sys_get_temp_dir() . '/login_attempts_' . md5($ip) . '.json';
    if (file_exists($arquivo)) {
        @unlink($arquivo);
    }
}

/**
 * Verifica se usuário tem nível de acesso permitido
 */
function verificarNivel(array $niveisPermitidos): void {
    verificarAuth();
    $nivel = $_SESSION['nivel'] ?? 'recepcionista';
    if (!in_array($nivel, $niveisPermitidos)) {
        jsonError('Acesso negado. Permissão insuficiente.', 403);
    }
}

/**
 * Registra evento no audit log
 */
function auditoria(string $acao, string $entidade, ?int $registroId = null, ?array $dadosAntigos = null, ?array $dadosNovos = null): void {
    try {
        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO audit_log (usuario, acao, entidade, registro_id, dados_antigos, dados_novos, ip)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $_SESSION['usuario'] ?? 'sistema',
            $acao,
            $entidade,
            $registroId,
            $dadosAntigos ? json_encode($dadosAntigos) : null,
            $dadosNovos ? json_encode($dadosNovos) : null,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);
    } catch (PDOException $e) {
        error_log("Falha ao registrar auditoria: " . $e->getMessage());
    }
}

/**
 * Lê o corpo da requisição JSON
 */
function lerCorpoRequisicao(): array {
    $json = file_get_contents('php://input');
    $dados = json_decode($json, true);
    return is_array($dados) ? $dados : [];
}

/**
 * Calcula prioridade baseada nos sinais vitais
 */
function calcularPrioridade(
    ?int $fc,
    ?float $temp,
    ?int $satO2
): string {
    // Emergência
    if (($fc !== null && $fc > 140) || ($satO2 !== null && $satO2 < 85)) {
        return 'emergencia';
    }
    // Alta
    if (($fc !== null && $fc > 120) || ($satO2 !== null && $satO2 < 90) || ($temp !== null && $temp > 39)) {
        return 'alta';
    }
    // Média
    if (($fc !== null && $fc >= 100 && $fc <= 120) || ($temp !== null && $temp >= 38 && $temp <= 39)) {
        return 'media';
    }
    // Baixa
    return 'baixa';
}
