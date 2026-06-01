<?php
    /*
     * Logout do Help Desk.
     *
     * A sessao PHP e separada da sessao Flask. Por isso este arquivo limpa
     * primeiro a sessao PHP e depois manda o usuario para /logout no Flask.
     */

    require_once __DIR__ . "/funcoes.php";

    // Remove os dados salvos em $_SESSION.
    $_SESSION = [];

    // Se a sessao PHP usa cookie, tambem apaga o cookie no navegador.
    if (ini_get("session.use_cookies")) {
        $parametrosCookie = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $parametrosCookie["path"],
            $parametrosCookie["domain"],
            $parametrosCookie["secure"],
            $parametrosCookie["httponly"]
        );
    }

    session_destroy();

    // Finaliza tambem a sessao Flask para nao deixar login "meio aberto".
    header("Location: " . URL_SISTEMA_FLASK . "/logout");
    exit;
?>
