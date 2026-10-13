<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

iniciarSessao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responderJson(405, [
        'sucesso' => false,
        'mensagem' => 'Método não permitido.',
    ]);
}

if (!validarCsrf()) {
    responderJson(403, [
        'sucesso' => false,
        'mensagem' => 'A solicitação expirou. Atualize a página e tente novamente.',
    ]);
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $parametros = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $parametros['path'],
        'domain' => $parametros['domain'],
        'secure' => $parametros['secure'],
        'httponly' => $parametros['httponly'],
        'samesite' => $parametros['samesite'] ?? 'Lax',
    ]);
}

session_destroy();
responderJson(200, [
    'sucesso' => true,
    'mensagem' => 'Logout realizado.',
]);
