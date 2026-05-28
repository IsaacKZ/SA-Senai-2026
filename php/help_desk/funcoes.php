<?php
    // Endereco do sistema Flask. O modulo PHP usa esses links para voltar ao sistema principal.
    const URL_SISTEMA_FLASK = "http://localhost:5000";

    // Evita que textos vindos do banco sejam interpretados como HTML na tela.
    function escapar($valor) {
        return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
    }

    // Padroniza a exibicao das datas do SQLite.
    function formatar_data($data) {
        if (empty($data)) {
            return "-";
        }

        return date("d/m/Y H:i", strtotime($data));
    }

    // Define a cor visual de cada status na listagem e nos detalhes.
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

    // Define a cor visual de cada prioridade.
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

    // Cabecalho reaproveitado nas telas do Help Desk.
    // Mantem o visual parecido com o Flask, mas sem depender dos templates Jinja.
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

                <span class="text-white me-3">
                    <i class="bi bi-code-slash"></i>
                    Modulo PHP
                </span>
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
