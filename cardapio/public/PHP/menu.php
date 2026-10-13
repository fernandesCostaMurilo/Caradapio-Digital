<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

header('Cache-Control: no-store');

function caminhoMenu(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'menu.json';
}

function lerMenu(): array
{
    $caminho = caminhoMenu();
    if (!is_file($caminho) || !is_readable($caminho)) {
        throw new RuntimeException('O arquivo do cardápio não está disponível para leitura.');
    }

    $conteudo = @file_get_contents($caminho);
    if ($conteudo === false) {
        throw new RuntimeException('Não foi possível ler o arquivo do cardápio.');
    }

    if (!str_starts_with(ltrim($conteudo), '[')) {
        throw new UnexpectedValueException('O arquivo do cardápio precisa conter uma lista JSON.');
    }

    $menu = json_decode($conteudo, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($menu) || ($menu !== [] && array_keys($menu) !== range(0, count($menu) - 1))) {
        throw new UnexpectedValueException('O arquivo do cardápio não contém uma lista JSON válida.');
    }

    return $menu;
}

function validarMenu(mixed $menu): bool
{
    if (!is_array($menu) || ($menu !== [] && array_keys($menu) !== range(0, count($menu) - 1))) {
        return false;
    }

    $camposTexto = ['dia', 'titulo', 'descricao', 'calorias', 'proteinas', 'carboidratos', 'alerta'];

    foreach ($menu as $item) {
        if (!is_array($item) || !isset($item['id']) || !is_int($item['id'])) {
            return false;
        }

        foreach ($camposTexto as $campo) {
            if (!isset($item[$campo]) || !is_string($item[$campo])) {
                return false;
            }
        }
    }

    return true;
}

function salvarMenu(array $menu): void
{
    $caminho = caminhoMenu();
    $temporario = $caminho . '.' . bin2hex(random_bytes(8)) . '.tmp';
    $json = json_encode(
        $menu,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . PHP_EOL;

    if (@file_put_contents($temporario, $json, LOCK_EX) === false) {
        if (is_file($temporario) && !@unlink($temporario)) {
            error_log('Não foi possível remover o arquivo temporário do cardápio.');
        }
        throw new RuntimeException('Não foi possível gravar o arquivo temporário do cardápio.');
    }

    if (!@rename($temporario, $caminho)) {
        if (is_file($temporario) && !@unlink($temporario)) {
            error_log('Não foi possível remover o arquivo temporário do cardápio.');
        }
        throw new RuntimeException('Não foi possível substituir o arquivo do cardápio.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        responderJson(200, lerMenu());
    } catch (JsonException | RuntimeException | UnexpectedValueException $erro) {
        error_log('Erro ao ler cardápio: ' . $erro->getMessage());
        responderJson(500, [
            'sucesso' => false,
            'mensagem' => 'Não foi possível carregar o cardápio.',
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    exigirAdmin(true);

    if (!validarCsrf()) {
        responderJson(403, [
            'sucesso' => false,
            'mensagem' => 'A solicitação expirou. Atualize a página e tente novamente.',
        ]);
    }

    $entrada = file_get_contents('php://input');
    if ($entrada === false || !str_starts_with(ltrim($entrada), '[')) {
        responderJson(400, [
            'sucesso' => false,
            'mensagem' => 'Formato de cardápio inválido.',
        ]);
    }

    try {
        $novoMenu = json_decode($entrada, true, 512, JSON_THROW_ON_ERROR);
        if (!validarMenu($novoMenu)) {
            responderJson(400, [
                'sucesso' => false,
                'mensagem' => 'Formato de cardápio inválido.',
            ]);
        }

        salvarMenu($novoMenu);
        responderJson(200, [
            'sucesso' => true,
            'mensagem' => 'Cardápio atualizado com sucesso.',
            'menu' => $novoMenu,
        ]);
    } catch (JsonException $erro) {
        responderJson(400, [
            'sucesso' => false,
            'mensagem' => 'O conteúdo enviado não é um JSON válido.',
        ]);
    } catch (RuntimeException $erro) {
        error_log('Erro ao salvar cardápio: ' . $erro->getMessage());
        responderJson(500, [
            'sucesso' => false,
            'mensagem' => 'Não foi possível salvar o cardápio.',
        ]);
    }
}

header('Allow: GET, PUT');
responderJson(405, [
    'sucesso' => false,
    'mensagem' => 'Método não permitido.',
]);
