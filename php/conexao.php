<?php
    /*
     * Conexao central do modulo PHP.
     *
     * O Help Desk nao tem um banco separado: ele usa o mesmo farmacia.db
     * do sistema Flask. Por isso qualquer tela PHP que precise do banco
     * inclui este arquivo e passa a usar a variavel $pdo.
     */

    // __DIR__ aqui aponta para a pasta /php.
    // O banco fica uma pasta acima, na raiz do projeto.
    $caminhoBanco = __DIR__ . "/../farmacia.db";

    try {
        // No SQLite a "conexao" aponta para um arquivo, nao para host/usuario/senha.
        $pdo = new PDO("sqlite:" . $caminhoBanco);

        // Faz o PDO lancar excecoes quando uma consulta falhar.
        // Isso deixa o erro mais claro durante o desenvolvimento.
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Retorna resultados como array associativo.
        // Exemplo: $usuario["nome"] em vez de $usuario[0].
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // SQLite so respeita FOREIGN KEY se isso estiver ativado por conexao.
        $pdo->exec("PRAGMA foreign_keys = ON");
    } catch (PDOException $e) {
        // Se o banco nao abrir, nenhuma pagina do Help Desk consegue continuar.
        die("Erro na conexao: " . $e->getMessage());
    }
?>
