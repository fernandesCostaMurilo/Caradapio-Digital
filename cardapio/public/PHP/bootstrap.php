<?php
declare(strict_types=1);

require_once __DIR__ . '/conexao.php';

function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('cardapio_session');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function tokenCsrf(): string
{
    iniciarSessao();
    return $_SESSION['csrf_token'];
}

function validarCsrf(): bool
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function responderJson(int $status, array $dados): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
    exit;
}

function exigirAdmin(bool $respostaJson = false): void
{
    iniciarSessao();

    if (($_SESSION['usuario']['role'] ?? null) === 'admin') {
        return;
    }

    if ($respostaJson) {
        responderJson(401, [
            'sucesso' => false,
            'mensagem' => 'Você precisa entrar como administrador.',
        ]);
    }

    header('Location: ../PHP/login.php');
    exit;
}

function escaparHtml(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function algoritmoSenha(): string|int
{
    if (
        defined('PASSWORD_ARGON2ID')
        && in_array(PASSWORD_ARGON2ID, password_algos(), true)
    ) {
        return PASSWORD_ARGON2ID;
    }

    return PASSWORD_DEFAULT;
}
