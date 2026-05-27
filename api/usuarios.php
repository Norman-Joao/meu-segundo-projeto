<?php
require_once __DIR__ . '/config.php';
jsonHeader();

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

switch ($method) {
    case 'GET':
        verificarNivel(['admin']);
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT id, usuario, nome_exibicao, nivel FROM usuarios WHERE id = ?");
            $stmt->execute([(int)$_GET['id']]);
            $usuario = $stmt->fetch();
            if (!$usuario) jsonResponse(['success' => false, 'erro' => 'Usuário não encontrado.']);
            jsonResponse(['success' => true, 'usuario' => $usuario]);
        } else {
            $stmt = $db->query("SELECT id, usuario, nome_exibicao, nivel, created_at FROM usuarios ORDER BY nome_exibicao");
            jsonResponse(['success' => true, 'usuarios' => $stmt->fetchAll()]);
        }
        break;

    case 'POST':
        verificarNivel(['admin']);
        $dados = lerCorpoRequisicao();
        $erro = validarCampos($dados, ['usuario', 'senha', 'nome_exibicao', 'nivel']);
        if ($erro) jsonResponse(['success' => false, 'erro' => $erro]);

        $niveisValidos = ['admin', 'medico', 'enfermeiro', 'recepcionista'];
        if (!in_array($dados['nivel'], $niveisValidos)) {
            jsonResponse(['success' => false, 'erro' => 'Nível inválido.']);
        }

        $stmt = $db->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt->execute([$dados['usuario']]);
        if ($stmt->fetch()) jsonResponse(['success' => false, 'erro' => 'Usuário já existe.']);

        $hash = password_hash($dados['senha'], PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO usuarios (usuario, senha, nome_exibicao, nivel) VALUES (?, ?, ?, ?)");
        $stmt->execute([$dados['usuario'], $hash, sanitizar($dados['nome_exibicao']), $dados['nivel']]);
        $id = $db->lastInsertId();
        auditoria('criar', 'usuarios', $id, null, ['usuario' => $dados['usuario'], 'nivel' => $dados['nivel']]);
        jsonResponse(['success' => true, 'id' => $id, 'mensagem' => 'Usuário criado com sucesso!']);
        break;

    case 'PUT':
        verificarAuth();
        verificarCSRF();
        $dados = lerCorpoRequisicao();
        $id = $dados['id'] ?? $_GET['id'] ?? null;
        if (!$id) jsonResponse(['success' => false, 'erro' => 'ID do usuário é obrigatório.']);

        $stmt = $db->prepare("SELECT id, usuario, nivel FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch();
        if (!$usuario) jsonResponse(['success' => false, 'erro' => 'Usuário não encontrado.']);

        if (isset($dados['senha_atual']) && isset($dados['senha_nova'])) {
            $stmt = $db->prepare("SELECT senha FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!password_verify($dados['senha_atual'], $row['senha'])) {
                jsonResponse(['success' => false, 'erro' => 'Senha atual incorreta.']);
            }
            if (strlen($dados['senha_nova']) < 4) {
                jsonResponse(['success' => false, 'erro' => 'Nova senha deve ter no mínimo 4 caracteres.']);
            }
            $hash = password_hash($dados['senha_nova'], PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
            auditoria('alterar_senha', 'usuarios', $id);
            jsonResponse(['success' => true, 'mensagem' => 'Senha alterada com sucesso!']);
        }

        if (isset($dados['nivel'])) {
            verificarNivel(['admin']);
            $niveisValidos = ['admin', 'medico', 'enfermeiro', 'recepcionista'];
            if (!in_array($dados['nivel'], $niveisValidos)) {
                jsonResponse(['success' => false, 'erro' => 'Nível inválido.']);
            }
            $stmt = $db->prepare("UPDATE usuarios SET nivel = ? WHERE id = ?");
            $stmt->execute([$dados['nivel'], $id]);
            auditoria('alterar_nivel', 'usuarios', $id, ['nivel' => $usuario['nivel']], ['nivel' => $dados['nivel']]);
            jsonResponse(['success' => true, 'mensagem' => 'Nível alterado com sucesso!']);
        }

        if (isset($dados['nome_exibicao'])) {
            verificarNivel(['admin']);
            $stmt = $db->prepare("UPDATE usuarios SET nome_exibicao = ? WHERE id = ?");
            $stmt->execute([sanitizar($dados['nome_exibicao']), $id]);
            auditoria('alterar_nome', 'usuarios', $id, null, ['nome_exibicao' => $dados['nome_exibicao']]);
            jsonResponse(['success' => true, 'mensagem' => 'Nome atualizado com sucesso!']);
        }

        jsonResponse(['success' => false, 'erro' => 'Nenhum dado para alterar.']);
        break;

    default:
        jsonResponse(['success' => false, 'erro' => 'Método não permitido.'], 405);
}
