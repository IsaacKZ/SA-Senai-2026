<?php
    /*
     * Inicializacao da sessao PHP do Help Desk.
     *
     * Alguns ambientes deixam session.save_path apontando para uma pasta sem
     * permissao, como C:\xampp\tmp. Quando isso acontece, session_start()
     * gera warning e quebra respostas JSON.
     *
     * Para evitar depender do php.ini, o Help Desk usa uma subpasta dentro da
     * pasta temporaria do sistema.
     */

    function iniciar_sessao_help_desk() {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $pastaSessao = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "sa_senai_2026_php_sessions";

        if (!is_dir($pastaSessao)) {
            mkdir($pastaSessao, 0777, true);
        }

        session_save_path($pastaSessao);
        session_start();
    }
