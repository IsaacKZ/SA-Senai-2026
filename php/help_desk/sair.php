<?php
    require_once __DIR__ . "/funcoes.php";

    // A sessao do PHP e separada da sessao do Flask.
    // Por isso limpamos primeiro o modulo PHP e depois enviamos o usuario
    // para o logout do sistema principal.
    $_SESSION = [];

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

    header("Location: " . URL_SISTEMA_FLASK . "/logout");
    exit;
?>
