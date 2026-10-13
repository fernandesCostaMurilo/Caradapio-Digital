<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

iniciarSessao();
exigirAdmin();

$mensagem = '';
$cadastroConcluido = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmacao = (string) ($_POST['csenha'] ?? '');

    if (!validarCsrf()) {
        $mensagem = 'A solicitação expirou. Atualize a página e tente novamente.';
    } elseif ($nome === '' || strlen($nome) > 80) {
        $mensagem = 'Informe um nome válido com até 80 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $mensagem = 'Informe um endereço de e-mail válido.';
    } elseif ($senha === '' || strlen($senha) > 255) {
        $mensagem = 'Informe uma senha válida.';
    } elseif (algoritmoSenha() === PASSWORD_DEFAULT && strlen($senha) > 72) {
        $mensagem = 'A senha pode ter no máximo 72 caracteres nesta instalação.';
    } elseif (!hash_equals($senha, $confirmacao)) {
        $mensagem = 'As senhas informadas não são iguais.';
    } else {
        try {
            $conexao = obterConexao();
            $hash = password_hash($senha, algoritmoSenha());
            $insercao = $conexao->prepare(
                "INSERT INTO users (NOME, EMAIL, SENHA, ROLE) VALUES (?, ?, ?, 'admin')"
            );
            $insercao->bind_param('sss', $nome, $email, $hash);
            $insercao->execute();
            $insercao->close();

            $mensagem = 'Usuário criado com sucesso.';
            $cadastroConcluido = true;
        } catch (mysqli_sql_exception $erro) {
            if ((int) $erro->getCode() === 1062) {
                $mensagem = 'Já existe uma conta com esse e-mail.';
            } else {
                error_log('Erro no cadastro do cardápio: ' . $erro->getMessage());
                http_response_code(500);
                $mensagem = 'Não foi possível criar a conta. Tente novamente mais tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de contas</title>
    <link rel="stylesheet" href="../CSS/cad.css">
</head>
<body>
    <header>
        <button type="button" class="btn-voltar" onclick="window.location.href='../HTML/admin.php'">Voltar</button>
    </header>
    <form action="" method="post">
        <input type="hidden" name="csrf_token" value="<?= escaparHtml(tokenCsrf()) ?>">
        <input type="text" maxlength="80" name="nome" placeholder="Nome do usuário" autocapitalize="on" required>
        <input type="email" maxlength="255" name="email" placeholder="E-mail" required>
        <input type="password" maxlength="255" name="senha" placeholder="Senha" autocomplete="new-password" required>
        <input type="password" maxlength="255" name="csenha" placeholder="Confirmar senha" autocomplete="new-password" required>
        <button type="submit">Criar conta</button>
        <?php if ($mensagem !== ''): ?>
            <p role="status"><?= escaparHtml($mensagem) ?></p>
        <?php endif; ?>
        <?php if ($cadastroConcluido): ?>
            <a href="../HTML/admin.php">Voltar ao painel</a>
        <?php endif; ?>
    </form>
</body>
</html>
