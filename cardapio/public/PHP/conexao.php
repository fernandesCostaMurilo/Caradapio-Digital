<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function obterConexao(): mysqli
{
    static $conexao = null;

    if ($conexao instanceof mysqli) {
        return $conexao;
    }

    $host = getenv('CARDAPIO_DB_HOST') ?: 'localhost';
    $usuario = getenv('CARDAPIO_DB_USER') ?: 'root';
    $senha = getenv('CARDAPIO_DB_PASSWORD') ?: '';
    $banco = getenv('CARDAPIO_DB_NAME') ?: 'admins';

    $conexao = new mysqli($host, $usuario, $senha, $banco);
    $conexao->set_charset('utf8mb4');

    return $conexao;
}
