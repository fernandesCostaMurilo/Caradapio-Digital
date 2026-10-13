<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

iniciarSessao();

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrf()) {
        $mensagem = 'A solicitação expirou. Atualize a página e tente novamente.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');

        if ($email === '' || $senha === '') {
            $mensagem = 'Informe o e-mail e a senha.';
        } else {
            try {
                $conexao = obterConexao();
                $consulta = $conexao->prepare(
                    'SELECT ID, NOME, EMAIL, SENHA, ROLE FROM users WHERE EMAIL = ? LIMIT 1'
                );
                $consulta->bind_param('s', $email);
                $consulta->execute();
                $usuario = $consulta->get_result()->fetch_assoc();
                $consulta->close();

                $senhaValida = false;
                $senhaLegada = false;
                if ($usuario !== null) {
                    $senhaGuardada = (string) $usuario['SENHA'];
                    $senhaValida = password_verify($senha, $senhaGuardada);

                    if (
                        !$senhaValida
                        && preg_match('/\A[a-f0-9]{32}\z/i', $senhaGuardada) === 1
                        && hash_equals(strtolower($senhaGuardada), md5($senha))
                    ) {
                        $senhaValida = true;
                        $senhaLegada = true;
                    }
                }

                if (!$senhaValida || $usuario === null || $usuario['ROLE'] !== 'admin') {
                    $mensagem = 'E-mail ou senha incorretos.';
                } else {
                    if ($senhaLegada || password_needs_rehash($senhaGuardada, algoritmoSenha())) {
                        $novoHash = password_hash($senha, algoritmoSenha());
                        $atualizacao = $conexao->prepare('UPDATE users SET SENHA = ? WHERE ID = ?');
                        $idUsuario = (int) $usuario['ID'];
                        $atualizacao->bind_param('si', $novoHash, $idUsuario);
                        $atualizacao->execute();
                        $atualizacao->close();
                    }

                    session_regenerate_id(true);
                    $_SESSION['usuario'] = [
                        'id' => (int) $usuario['ID'],
                        'nome' => (string) $usuario['NOME'],
                        'email' => (string) $usuario['EMAIL'],
                        'role' => (string) $usuario['ROLE'],
                    ];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    header('Location: ../HTML/admin.php');
                    exit;
                }
            } catch (mysqli_sql_exception $erro) {
                error_log('Erro no login do cardápio: ' . $erro->getMessage());
                http_response_code(500);
                $mensagem = 'Não foi possível entrar agora. Tente novamente mais tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../CSS/log.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <header>
        <button type="button" class="btn-voltar" onclick="window.location.href='../HTML/index.html'">Voltar</button>
    </header>
    <div class="area-form">
        <form action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?= escaparHtml(tokenCsrf()) ?>">
            <input type="email" name="email" placeholder="Seu E-mail" autocomplete="username" required>
            <input type="password" name="senha" placeholder="Sua senha" autocomplete="current-password" required>
            <button type="submit">Entrar</button>
            <?php if ($mensagem !== ''): ?>
                <p role="alert"><?= escaparHtml($mensagem) ?></p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
