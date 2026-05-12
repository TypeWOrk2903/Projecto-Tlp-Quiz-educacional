<?php
require "../suporte/utilitarios.php";
require "../suporte/Model.php";
requireAuth();
requireAdmin();

/**
 * ============================================================
 * QUIZZES – CRUD
 * ============================================================
 */

// ---------- CRIAR QUIZ ----------
if (!empty($_POST["criar_quiz"])) {
    $titulo    = sanitize((string)($_POST["titulo"]    ?? ''));
    $descricao = sanitize((string)($_POST["descricao"] ?? ''));
    $criado_por = requireAuth();

    if ($titulo === '') {
        echo json_encode(["erro" => "Título obrigatório."]);
        exit;
    }

    $stmt = $conectar->prepare(
        "INSERT INTO quizzes (titulo, descricao, criado_por) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("ssi", $titulo, $descricao, $criado_por);

    if ($stmt->execute()) {
        echo json_encode(["sucesso" => "Quiz criado.", "id" => $stmt->insert_id]);
    } else {
        echo json_encode(["erro" => "Erro ao criar quiz: " . $stmt->error]);
    }
    $stmt->close();
}

// ---------- ATUALIZAR QUIZ ----------
if (!empty($_POST["atualizar_quiz"])) {
    $quiz_id   = (int)($_POST["quiz_id"]   ?? 0);
    $titulo    = sanitize((string)($_POST["titulo"]    ?? ''));
    $descricao = sanitize((string)($_POST["descricao"] ?? ''));
    $ativo     = (int)($_POST["ativo"] ?? 1);

    if ($quiz_id === 0 || $titulo === '') {
        echo json_encode(["erro" => "quiz_id e título são obrigatórios."]);
        exit;
    }

    $stmt = $conectar->prepare(
        "UPDATE quizzes SET titulo=?, descricao=?, ativo=? WHERE id=?"
    );
    $stmt->bind_param("ssii", $titulo, $descricao, $ativo, $quiz_id);

    if ($stmt->execute()) {
        echo json_encode(["sucesso" => "Quiz atualizado."]);
    } else {
        echo json_encode(["erro" => "Erro ao atualizar quiz: " . $stmt->error]);
    }
    $stmt->close();
}

// ---------- EXCLUIR QUIZ ----------
if (!empty($_POST["excluir_quiz"])) {
    $quiz_id = (int)($_POST["quiz_id"] ?? 0);

    if ($quiz_id === 0) {
        echo json_encode(["erro" => "quiz_id obrigatório."]);
        exit;
    }

    $stmt = $conectar->prepare("DELETE FROM quizzes WHERE id=?");
    $stmt->bind_param("i", $quiz_id);

    if ($stmt->execute()) {
        echo json_encode(["sucesso" => "Quiz excluído."]);
    } else {
        echo json_encode(["erro" => "Erro ao excluir quiz: " . $stmt->error]);
    }
    $stmt->close();
}

// ---------- LISTAR QUIZZES ----------
if (!empty($_GET["listar_quizzes"])) {
    $stmt = $conectar->prepare(
        "SELECT q.id, q.titulo, q.descricao, q.ativo, q.criado_em,
                u.name AS criado_por,
                COUNT(qp.question_id) AS total_perguntas
         FROM quizzes q
         LEFT JOIN users u ON u.id = q.criado_por
         LEFT JOIN quiz_pergunta qp ON qp.quiz_id = q.id
         GROUP BY q.id
         ORDER BY q.criado_em DESC"
    );
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($resultado);
    $stmt->close();
}

/**
 * ============================================================
 * QUIZ_PERGUNTA – Vincular / Desvincular perguntas a um quiz
 * ============================================================
 */

// ---------- ADICIONAR PERGUNTA AO QUIZ ----------
if (!empty($_POST["adicionar_pergunta"])) {
    $quiz_id     = (int)($_POST["quiz_id"]     ?? 0);
    $question_id = (int)($_POST["question_id"] ?? 0);
    $ordem       = (int)($_POST["ordem"]       ?? 0);

    if ($quiz_id === 0 || $question_id === 0) {
        echo json_encode(["erro" => "quiz_id e question_id são obrigatórios."]);
        exit;
    }

    $stmt = $conectar->prepare(
        "INSERT INTO quiz_pergunta (quiz_id, question_id, ordem)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE ordem=VALUES(ordem)"
    );
    $stmt->bind_param("iii", $quiz_id, $question_id, $ordem);

    if ($stmt->execute()) {
        echo json_encode(["sucesso" => "Pergunta adicionada ao quiz."]);
    } else {
        echo json_encode(["erro" => "Erro ao adicionar pergunta: " . $stmt->error]);
    }
    $stmt->close();
}

// ---------- REMOVER PERGUNTA DO QUIZ ----------
if (!empty($_POST["remover_pergunta"])) {
    $quiz_id     = (int)($_POST["quiz_id"]     ?? 0);
    $question_id = (int)($_POST["question_id"] ?? 0);

    if ($quiz_id === 0 || $question_id === 0) {
        echo json_encode(["erro" => "quiz_id e question_id são obrigatórios."]);
        exit;
    }

    $stmt = $conectar->prepare(
        "DELETE FROM quiz_pergunta WHERE quiz_id=? AND question_id=?"
    );
    $stmt->bind_param("ii", $quiz_id, $question_id);

    if ($stmt->execute()) {
        echo json_encode(["sucesso" => "Pergunta removida do quiz."]);
    } else {
        echo json_encode(["erro" => "Erro ao remover pergunta: " . $stmt->error]);
    }
    $stmt->close();
}

// ---------- LISTAR PERGUNTAS DE UM QUIZ ----------
if (!empty($_GET["perguntas_quiz"])) {
    $quiz_id = (int)($_GET["quiz_id"] ?? 0);

    if ($quiz_id === 0) {
        echo json_encode(["erro" => "quiz_id obrigatório."]);
        exit;
    }

    $stmt = $conectar->prepare(
        "SELECT q.id, q.content, q.explain, q.diffcult_id, qp.ordem
         FROM quiz_pergunta qp
         INNER JOIN questions q ON q.id = qp.question_id
         WHERE qp.quiz_id = ?
         ORDER BY qp.ordem ASC"
    );
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($resultado);
    $stmt->close();
}
