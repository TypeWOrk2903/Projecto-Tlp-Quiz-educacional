<?php
// process_attempt.php
require "../suporte/utilitarios.php";
require "../suporte/Model.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["erro" => "Método não permitido."]);
    exit;
}

startSession();

$player_id     = (int)($_SESSION['user_id']   ?? 0);
$question_id   = (int)($_POST['question_id']  ?? 0);
$option_id     = (int)($_POST['option_id']    ?? 0);
$quiz_id       = (int)($_POST['quiz_id']      ?? 0);
$tempo_resposta = (float)($_POST['tempo_resposta'] ?? 0);

if ($player_id === 0 || $question_id === 0 || $option_id === 0 || $quiz_id === 0) {
    http_response_code(400);
    echo json_encode(["erro" => "Parâmetros incompletos."]);
    exit;
}

// 1. Verificar se a opção escolhida é a correta
$stmt = $conectar->prepare("SELECT is_correct FROM options WHERE id = ? AND question_id = ?");
$stmt->bind_param("ii", $option_id, $question_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($res === null) {
    http_response_code(400);
    echo json_encode(["erro" => "Opção inválida."]);
    exit;
}

$acertou       = (int)$res['is_correct'];
$resposta_texto = (string)$option_id; // identificador da opção escolhida

$save = $conectar->prepare(
    "INSERT INTO respostas (player_id, quiz_id, question_id, resposta, resposta_correta, tempo_resposta)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$save->bind_param("iiisid", $player_id, $quiz_id, $question_id, $resposta_texto, $acertou, $tempo_resposta);
$save->execute();
$save->close();

$attempt = $conectar->prepare(
    "INSERT INTO attempts (user_id, question_id, option_id, is_correct) VALUES (?, ?, ?, ?)"
);
$attempt->bind_param("iiii", $player_id, $question_id, $option_id, $acertou);
$attempt->execute();
$attempt->close();

if ($acertou) {
    $diff_stmt = $conectar->prepare("SELECT diffcult_id FROM questions WHERE id = ?");
    $diff_stmt->bind_param("i", $question_id);
    $diff_stmt->execute();
    $diff_row = $diff_stmt->get_result()->fetch_assoc();
    $diff_stmt->close();

    $xp_por_dificuldade = ["facil" => 5, "medio" => 10, "dificil" => 20];
    $xp_ganho = $xp_por_dificuldade[$diff_row['diffcult_id'] ?? 'facil'] ?? 10;

    $xp_stmt = $conectar->prepare("UPDATE users SET xp_total = xp_total + ? WHERE id = ?");
    $xp_stmt->bind_param("ii", $xp_ganho, $player_id);
    $xp_stmt->execute();
    $xp_stmt->close();

    echo json_encode(["sucesso" => "Resposta correta!", "xp_ganho" => $xp_ganho]);
} else {
    echo json_encode(["sucesso" => "Resposta registada.", "correto" => false]);
}
