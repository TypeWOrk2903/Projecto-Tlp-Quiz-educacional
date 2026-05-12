<?php
require "../suporte/utilitarios.php";
require "../suporte/Model.php";

requireAuth();
requireAdmin();

header('Content-Type: application/json; charset=utf-8');

// ---------- CRIAR USUÁRIO ----------
if (!empty($_POST["submit"])) {
    $nome     = sanitize((string)($_POST["name"]  ?? ''));
    $email    = trim((string)($_POST["email"]     ?? ''));
    $password = (string)($_POST["senha"]          ?? '');

    if ($nome === '' || $email === '' || $password === '') {
        http_response_code(400);
        echo json_encode(["erro" => "Preencha todos os campos obrigatórios."]);
        exit;
    }

    if (!verify_email($email)) {
        http_response_code(400);
        echo json_encode(["erro" => "E-mail fora do formato válido."]);
        exit;
    }

    if (verify_Bd_email($email)) {
        http_response_code(409);
        echo json_encode(["erro" => "E-mail já cadastrado."]);
        exit;
    }

    $senha_segura = hash_generate($password);
    $stmt = $conectar->prepare(
        "INSERT INTO users (`name`, email, `password`) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("sss", $nome, $email, $senha_segura);

    if ($stmt->execute()) {
        echo json_encode(["sucesso" => "Usuário criado com sucesso.", "id" => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao criar usuário."]);
    }
    $stmt->close();
    exit;
}

// ---------- ATUALIZAR USUÁRIO ----------
if (!empty($_POST["update"])) {
    $user_id   = (int)($_POST["user_id"] ?? 0);
    $nome      = sanitize((string)($_POST["name"]  ?? ''));
    $email     = trim((string)($_POST["email"]     ?? ''));
    $tipo_user = sanitize((string)($_POST["tipo_user"] ?? ''));

    if ($user_id === 0 || $nome === '' || $email === '') {
        http_response_code(400);
        echo json_encode(["erro" => "user_id, nome e e-mail são obrigatórios."]);
        exit;
    }

    if (!verify_email($email)) {
        http_response_code(400);
        echo json_encode(["erro" => "E-mail fora do formato válido."]);
        exit;
    }

    $stmt = $conectar->prepare(
        "UPDATE users SET `name`=?, email=?, tipo_user=? WHERE id=?"
    );
    $stmt->bind_param("sssi", $nome, $email, $tipo_user, $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows === 0) {
            http_response_code(404);
            echo json_encode(["erro" => "Usuário não encontrado."]);
        } else {
            echo json_encode(["sucesso" => "Usuário atualizado com sucesso."]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao atualizar usuário."]);
    }
    $stmt->close();
    exit;
}

// ---------- EXCLUIR USUÁRIO ----------
if (!empty($_POST["delete"])) {
    $user_id = (int)($_POST["user_id"] ?? 0);

    if ($user_id === 0) {
        http_response_code(400);
        echo json_encode(["erro" => "user_id obrigatório."]);
        exit;
    }

    $stmt = $conectar->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows === 0) {
            http_response_code(404);
            echo json_encode(["erro" => "Usuário não encontrado."]);
        } else {
            echo json_encode(["sucesso" => "Usuário excluído com sucesso."]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao excluir usuário."]);
    }
    $stmt->close();
    exit;
}

// ---------- LISTAR USUÁRIOS ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conectar->prepare(
        "SELECT id, `name`, email, tipo_user, xp_total FROM users ORDER BY id DESC"
    );
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode($resultado);
    exit;
}

