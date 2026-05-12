<?php
require "../suporte/utilitarios.php";
require "../suporte/Model.php";

requireAuth();
requireAdmin();

header('Content-Type: application/json; charset=utf-8');

// ---------- CRIAR QUESTÃO ----------
if (!empty($_POST["submit"])) {
    $category_id = (int)($_POST["category_id"] ?? 0);
    $dificuldade  = (int)($_POST["diffcult_id"]  ?? 1);
    $descricao    = sanitize((string)($_POST["content"]  ?? ''));
    $resposta     = sanitize((string)($_POST["explain"]  ?? ''));

    if ($category_id === 0 || $descricao === '' || $resposta === '') {
        http_response_code(400);
        echo json_encode(["erro" => "Preencha todos os campos obrigatórios."]);
        exit;
    }

    $niveis = dificulty();
    if (!isset($niveis[$dificuldade])) {
        http_response_code(400);
        echo json_encode(["erro" => "Nível de dificuldade inválido."]);
        exit;
    }

    $completo = $niveis[$dificuldade];

    if (verify_Bd_registro("questions", "explain", $resposta)) {
        http_response_code(409);
        echo json_encode(["erro" => "Esta questão já foi adicionada."]);
        exit;
    }

    $stmt = $conectar->prepare(
        "INSERT INTO questions (category_id, diffcult_id, content, explain) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("isss", $category_id, $completo, $descricao, $resposta);

    if ($stmt->execute()) {
        echo json_encode(["sucesso" => "Questão criada com sucesso.", "id" => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao criar questão."]);
    }
    $stmt->close();
    exit;
}

// ---------- ATUALIZAR QUESTÃO ----------
if (!empty($_POST["update"])) {
    $question_id  = (int)($_POST["question_id"]  ?? 0);
    $category_id  = (int)($_POST["category_id"]  ?? 0);
    $dificuldade  = (int)($_POST["diffcult_id"]   ?? 1);
    $descricao    = sanitize((string)($_POST["content"]  ?? ''));
    $resposta     = sanitize((string)($_POST["explain"]  ?? ''));

    if ($question_id === 0 || $category_id === 0 || $descricao === '' || $resposta === '') {
        http_response_code(400);
        echo json_encode(["erro" => "Preencha todos os campos obrigatórios."]);
        exit;
    }

    $niveis = dificulty();
    if (!isset($niveis[$dificuldade])) {
        http_response_code(400);
        echo json_encode(["erro" => "Nível de dificuldade inválido."]);
        exit;
    }

    $completo = $niveis[$dificuldade];

    $stmt = $conectar->prepare(
        "UPDATE questions SET category_id=?, diffcult_id=?, content=?, explain=? WHERE id=?"
    );
    $stmt->bind_param("isssi", $category_id, $completo, $descricao, $resposta, $question_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows === 0) {
            http_response_code(404);
            echo json_encode(["erro" => "Questão não encontrada."]);
        } else {
            echo json_encode(["sucesso" => "Questão atualizada com sucesso."]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao atualizar questão."]);
    }
    $stmt->close();
    exit;
}

// ---------- EXCLUIR QUESTÃO ----------
if (!empty($_POST["delete"])) {
    $question_id = (int)($_POST["question_id"] ?? 0);

    if ($question_id === 0) {
        http_response_code(400);
        echo json_encode(["erro" => "question_id obrigatório."]);
        exit;
    }

    $stmt = $conectar->prepare("DELETE FROM questions WHERE id=?");
    $stmt->bind_param("i", $question_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows === 0) {
            http_response_code(404);
            echo json_encode(["erro" => "Questão não encontrada."]);
        } else {
            echo json_encode(["sucesso" => "Questão excluída com sucesso."]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["erro" => "Erro ao excluir questão."]);
    }
    $stmt->close();
    exit;
}

// ---------- LISTAR QUESTÕES ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $category_id = (int)($_GET["category_id"] ?? 0);
    $dificuldade  = (int)($_GET["diffcult_id"]  ?? 0);

    $where  = [];
    $params = [];
    $types  = '';

    if ($category_id > 0) {
        $where[]  = "category_id = ?";
        $params[] = $category_id;
        $types   .= 'i';
    }

    if ($dificuldade > 0 && isset(dificulty()[$dificuldade])) {
        $nivel    = dificulty()[$dificuldade];
        $where[]  = "diffcult_id = ?";
        $params[] = $nivel;
        $types   .= 's';
    }

    $sql = "SELECT id, category_id, diffcult_id, content, explain FROM questions";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY id DESC";

    $stmt = $conectar->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode($resultado);
    exit;
}

