<?php
require  "bd_config.php";
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 86400 * 7, // 7 dias
            'path'     => '/',
            'secure'   => false,     // true em produção com HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
/**Validação de Usuario */
function requireAuth(): int
{
    startSession();
    if (empty($_SESSION['user_id'])) {
        json_encode('Não autenticado. Faça login primeiro.', 401);
    }
    return (int) $_SESSION['user_id'];
}

function requireAdmin(): int
{
    $userId = requireAuth();
    startSession();
    if (($_SESSION['tipo_user'] ?? '') !== 'admin') {
        json_encode('Acesso negado. Apenas administradores.', 403);
    }
    return $userId;
}

/**VALIDAÇão */
function verify_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}
function sanitize(string $valor): string
{
    return htmlspecialchars(trim($valor), ENT_QUOTES, "UTF-8");
}
function hash_generate(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}
function dificulty() : array {
    return [1=>"facil",2=>"medio",3=>"dificil"];
}
