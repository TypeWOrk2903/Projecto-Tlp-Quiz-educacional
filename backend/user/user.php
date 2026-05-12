<?php

/**
 * Função para detectar trapaças nos jogadores em relação às perguntas
 * @param mysqli $conn - Conexão com banco de dados
 * @param int $player_id - ID do jogador
 * @return array - Resultado da análise com indicadores de trapaça
 */
function detectarTrapaças($conn, $player_id) {
    $indicadores_trapaça = array(
        'respostas_muito_rapidas' => 0,
        'taxa_acerto_anormalmente_alta' => 0,
        'mesmo_padrão_respostas' => 0,
        'score_final' => 0,
        'risco_trapaça' => 'Baixo'
    );

    // Verificar respostas muito rápidas
    $query_tempo = "SELECT COUNT(*) as respostas_rapidas 
                    FROM respostas 
                    WHERE player_id = ? AND tempo_resposta < 2";
    
    $stmt_tempo = $conn->prepare($query_tempo);
    $stmt_tempo->bind_param("i", $player_id);
    $stmt_tempo->execute();
    $result_tempo = $stmt_tempo->get_result();
    $row_tempo = $result_tempo->fetch_assoc();
    
    if ($row_tempo['respostas_rapidas'] > 5) {
        $indicadores_trapaça['respostas_muito_rapidas'] = $row_tempo['respostas_rapidas'];
        $indicadores_trapaça['score_final'] += 25;
    }

    // Verificar taxa de acerto anormalmente alta
    $query_acerto = "SELECT 
                     (SUM(CASE WHEN resposta_correta = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as taxa_acerto
                     FROM respostas 
                     WHERE player_id = ?";
    
    $stmt_acerto = $conn->prepare($query_acerto);
    $stmt_acerto->bind_param("i", $player_id);
    $stmt_acerto->execute();
    $result_acerto = $stmt_acerto->get_result();
    $row_acerto = $result_acerto->fetch_assoc();
    
    if ($row_acerto['taxa_acerto'] > 95) {
        $indicadores_trapaça['taxa_acerto_anormalmente_alta'] = round($row_acerto['taxa_acerto'], 2);
        $indicadores_trapaça['score_final'] += 30;
    }

    // Verificar padrão repetitivo de respostas
    $query_padrao = "SELECT resposta, COUNT(*) as frequencia 
                     FROM respostas 
                     WHERE player_id = ? 
                     GROUP BY resposta 
                     HAVING COUNT(*) > (SELECT COUNT(*) FROM respostas WHERE player_id = ?) * 0.6";
    
    $stmt_padrao = $conn->prepare($query_padrao);
    $stmt_padrao->bind_param("ii", $player_id, $player_id);
    $stmt_padrao->execute();
    $result_padrao = $stmt_padrao->get_result();
    
    if ($result_padrao->num_rows > 0) {
        $indicadores_trapaça['mesmo_padrão_respostas'] = $result_padrao->num_rows;
        $indicadores_trapaça['score_final'] += 25;
    }

    // Determinar nível de risco
    if ($indicadores_trapaça['score_final'] >= 60) {
        $indicadores_trapaça['risco_trapaça'] = 'Alto';
    } elseif ($indicadores_trapaça['score_final'] >= 30) {
        $indicadores_trapaça['risco_trapaça'] = 'Médio';
    }

    return $indicadores_trapaça;
}

/**
 * Retorna o ranking geral ou por quiz
 * @param mysqli $conn       - Conexão com banco de dados
 * @param int    $limite     - Quantidade de posições (padrão 10)
 * @param int    $quiz_id    - 0 = ranking geral, >0 = ranking do quiz específico
 * @return array             - Lista ordenada com posição, jogador, acertos, total, taxa e XP
 */
function getRanking(mysqli $conn, int $limite = 10, int $quiz_id = 0): array {
    if ($quiz_id > 0) {
        $sql = "SELECT
                    u.id              AS player_id,
                    u.name            AS nome,
                    SUM(r.resposta_correta)                              AS acertos,
                    COUNT(r.id)                                          AS total_respostas,
                    ROUND((SUM(r.resposta_correta) / COUNT(r.id)) * 100, 1) AS taxa_acerto,
                    ROUND(AVG(r.tempo_resposta), 2)                      AS tempo_medio,
                    u.xp_total
                FROM respostas r
                INNER JOIN users u ON u.id = r.player_id
                WHERE r.quiz_id = ?
                GROUP BY u.id, u.name, u.xp_total
                ORDER BY acertos DESC, taxa_acerto DESC, tempo_medio ASC
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $quiz_id, $limite);
    } else {
        $sql = "SELECT
                    u.id              AS player_id,
                    u.name            AS nome,
                    SUM(r.resposta_correta)                              AS acertos,
                    COUNT(r.id)                                          AS total_respostas,
                    ROUND((SUM(r.resposta_correta) / COUNT(r.id)) * 100, 1) AS taxa_acerto,
                    ROUND(AVG(r.tempo_resposta), 2)                      AS tempo_medio,
                    u.xp_total
                FROM respostas r
                INNER JOIN users u ON u.id = r.player_id
                GROUP BY u.id, u.name, u.xp_total
                ORDER BY u.xp_total DESC, acertos DESC, taxa_acerto DESC
                LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limite);
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Adicionar posição no ranking
    $ranking = [];
    foreach ($rows as $pos => $jogador) {
        $ranking[] = array_merge(['posicao' => $pos + 1], $jogador);
    }

    return $ranking;
}

/**
 * Retorna a posição de um jogador específico no ranking
 * @param mysqli $conn      - Conexão com banco de dados
 * @param int    $player_id - ID do jogador
 * @param int    $quiz_id   - 0 = ranking geral, >0 = ranking do quiz específico
 * @return array            - Posição e estatísticas do jogador
 */
function getPosicaoRanking(mysqli $conn, int $player_id, int $quiz_id = 0): array {
    $ranking_completo = getRanking($conn, PHP_INT_MAX, $quiz_id);

    foreach ($ranking_completo as $entrada) {
        if ((int)$entrada['player_id'] === $player_id) {
            return $entrada;
        }
    }

    return ['posicao' => null, 'mensagem' => 'Jogador não encontrado no ranking.'];
}

?>
