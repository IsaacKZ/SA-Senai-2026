<?php
    /*
     * Funcoes compartilhadas do Help Desk.
     *
     * Este arquivo evita repetir codigo de sessao, layout, escape de HTML,
     * formatacao de datas e badges em todas as paginas PHP.
     */

    // As telas do Help Desk usam sessao PHP propria.
    // Ela e sincronizada com a sessao Flask em sincronizar_sessao.php.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Endereco do sistema Flask. Usado nos links da navbar e no logout.
    const URL_SISTEMA_FLASK = "http://localhost:5000";

    // Escape padrao para qualquer texto exibido na tela.
    // Pense nisso como "mostrar como texto", nao "executar como HTML".
    function escapar($valor) {
        return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
    }

    // O PHP considera o usuario logado quando a sincronizacao ja copiou
    // o ID do usuario Flask para $_SESSION.
    function usuario_logado_php() {
        return !empty($_SESSION["user_id"]);
    }

    // Protege paginas internas do Help Desk.
    // Se ainda nao existir sessao PHP, tenta sincronizar com o Flask.
    function exigir_login_php() {
        if (!usuario_logado_php()) {
            header("Location: sincronizar_sessao.php");
            exit;
        }
    }

    // Padroniza a exibicao das datas vindas do SQLite.
    function formatar_data($data) {
        if (empty($data)) {
            return "-";
        }

        return date("d/m/Y H:i", strtotime($data));
    }

    // Cria o badge visual de status.
    // O valor ainda e escapado antes de ir para a tela.
    function badge_status($status) {
        $cores = [
            "Aberto" => "bg-primary",
            "Em andamento" => "bg-warning text-dark",
            "Resolvido" => "bg-success",
            "Fechado" => "bg-secondary",
        ];

        $classe = $cores[$status] ?? "bg-secondary";
        return "<span class=\"badge {$classe}\">" . escapar($status) . "</span>";
    }

    // Cria o badge visual de prioridade.
    function badge_prioridade($prioridade) {
        $cores = [
            "Baixa" => "bg-success",
            "Media" => "bg-warning text-dark",
            "Alta" => "bg-danger",
        ];

        $classe = $cores[$prioridade] ?? "bg-secondary";
        return "<span class=\"badge {$classe}\">" . escapar($prioridade) . "</span>";
    }

    // Mostra mensagens simples apos criar, atualizar ou falhar em alguma operacao.
    // A pagina so passa um codigo curto pela URL, por exemplo: ?msg=criado.
    function mostrar_mensagem() {
        $mensagens = [
            "criado" => ["success", "Chamado aberto com sucesso!"],
            "atualizado" => ["success", "Chamado atualizado com sucesso!"],
            "erro" => ["danger", "Nao foi possivel concluir a operacao."],
        ];

        $codigo = $_GET["msg"] ?? "";

        if (!array_key_exists($codigo, $mensagens)) {
            return;
        }

        [$tipo, $texto] = $mensagens[$codigo];
?>
        <div class="alert alert-<?php echo escapar($tipo); ?> alert-dismissible fade show slide-up" role="alert">
            <strong><?php echo escapar($texto); ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
<?php
    }

    /*
     * Cabecalho reaproveitado nas telas do Help Desk.
     *
     * O Flask usa Jinja, mas o PHP nao entende templates Jinja.
     * Por isso este cabecalho replica a navbar e inclui o mesmo CSS.
     */
    function cabecalho($tituloPagina) {
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escapar($tituloPagina); ?> - Help Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../static/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo URL_SISTEMA_FLASK; ?>/dashboard">
                <i class="bi bi-heart-pulse-fill"></i>
                <strong>Farmacia Vida Saudavel</strong>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarHelpDesk">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarHelpDesk">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URL_SISTEMA_FLASK; ?>/dashboard">
                            <i class="bi bi-house-fill"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URL_SISTEMA_FLASK; ?>/pdv">
                            <i class="bi bi-cart-fill"></i> Vendas (PDV)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URL_SISTEMA_FLASK; ?>/produtos">
                            <i class="bi bi-box-seam"></i> Estoque
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo URL_SISTEMA_FLASK; ?>/relatorios">
                            <i class="bi bi-file-earmark-bar-graph"></i> Relatorios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-headset"></i> Help Desk
                        </a>
                    </li>
                </ul>

                <?php if (usuario_logado_php()): ?>
                    <div class="d-flex align-items-center">
                        <span class="text-white me-3">
                            <i class="bi bi-person-circle"></i>
                            Ola, <strong><?php echo escapar($_SESSION["user_nome"]); ?></strong>
                            <span class="badge bg-light text-dark ms-1"><?php echo escapar($_SESSION["user_cargo"]); ?></span>
                        </span>
                        <a href="sair.php" class="btn btn-logout">
                            <i class="bi bi-box-arrow-right"></i> Sair
                        </a>
                    </div>
                <?php else: ?>
                    <span class="text-white me-3">Sincronizando sessao...</span>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container-fluid mt-4 fade-in">
<?php
    }

    // Rodape comum das paginas PHP.
    function rodape() {
?>
    </main>

    <footer class="footer mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2026 Farmacia Vida Saudavel - Help Desk</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
    }
?>
