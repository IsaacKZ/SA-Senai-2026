<?php
    // __DIR__ representa a pasta atual
    $caminhoBanco = __DIR__ . "/../farmacia.db";

    try {
        // Cria a conexao com o banco SQLite.
        // Diferente do MySQL, o SQLite nao usa host, usuario ou senha.
        $pdo = new PDO("sqlite:" . $caminhoBanco);

        // Se der erro em alguma consulta SQL, o PDO lanca uma excecao.
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Faz os resultados das consultas retornarem como array associativo.
        // Exemplo: $usuario["nome"] em vez de $usuario[0].
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Ativa o uso de chaves estrangeiras no SQLite.
        $pdo->exec("PRAGMA foreign_keys = ON");
    } catch (PDOException $e) {
        // Encerra a execucao e mostra o erro caso a conexao falhe.
        die("Erro na conexao: " . $e->getMessage());
    }
?>
