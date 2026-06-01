<?php
    /*
     * Tela de detalhes de um chamado.
     *
     * Mostra as informacoes completas e permite atualizar status/prioridade.
     * A atualizacao em si fica no arquivo atualizar_chamado.php.
     */

    require_once __DIR__ . "/../conexao.php";
    require_once __DIR__ . "/funcoes.php";

    exigir_login_php();

    // ID do chamado vindo pela URL.
    // filter_input evita usar uma string qualquer como ID.
    $chamadoId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

    if (!$chamadoId) {
        header("Location: index.php?msg=erro");
        exit;
    }

    // Busca o chamado junto com o nome de quem abriu e de quem fechou.
    // LEFT JOIN no fechado porque um chamado aberto ainda nao tem fechador.
    $consultaChamado = $pdo->prepare("
        SELECT
            c.*,
            aberto.nome AS aberto_por,
            fechado.nome AS fechado_por
        FROM chamados c
        INNER JOIN usuarios aberto ON aberto.id = c.aberto_por_id
        LEFT JOIN usuarios fechado ON fechado.id = c.fechado_por_id
        WHERE c.id = ?
    ");
    $consultaChamado->execute([$chamadoId]);
    $chamado = $consultaChamado->fetch();

    if (!$chamado) {
        header("Location: index.php?msg=erro");
        exit;
    }

    $statusPermitidos = ["Aberto", "Em andamento", "Resolvido", "Fechado"];
    $prioridadesPermitidas = ["Baixa", "Media", "Alta"];

    // Renderiza os detalhes e o formulario de atualizacao.
    cabecalho("Chamado #" . $chamado["id"]);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-ticket-detailed"></i> Chamado #<?php echo escapar($chamado["id"]); ?>
        </h2>
        <p class="text-muted mb-0">Detalhes e atualizacao do atendimento.</p>
    </div>

    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<?php mostrar_mensagem(); ?>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-card-text"></i> Informacoes do chamado
            </div>

            <div class="card-body">
                <h3><?php echo escapar($chamado["titulo"]); ?></h3>

                <div class="mb-3">
                    <?php echo badge_prioridade($chamado["prioridade"]); ?>
                    <?php echo badge_status($chamado["status"]); ?>
                </div>

                <p style="white-space: pre-wrap;"><?php echo escapar($chamado["descricao"]); ?></p>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Aberto por:</strong> <?php echo escapar($chamado["aberto_por"]); ?></p>
                        <p class="mb-1"><strong>Data de abertura:</strong> <?php echo formatar_data($chamado["data_aberto"]); ?></p>
                    </div>

                    <div class="col-md-6">
                        <p class="mb-1"><strong>Fechado por:</strong> <?php echo escapar($chamado["fechado_por"] ?? "-"); ?></p>
                        <p class="mb-1"><strong>Data de fechamento:</strong> <?php echo formatar_data($chamado["data_fechado"]); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-arrow-repeat"></i> Atualizar chamado
            </div>

            <div class="card-body">
                <form method="POST" action="atualizar_chamado.php">
                    <!-- ID oculto: informa ao processador qual chamado sera atualizado. -->
                    <input type="hidden" name="id" value="<?php echo escapar($chamado["id"]); ?>">

                    <!-- As opcoes precisam bater com os valores permitidos no banco. -->
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <?php foreach ($statusPermitidos as $status): ?>
                                <option value="<?php echo escapar($status); ?>" <?php echo $status === $chamado["status"] ? "selected" : ""; ?>>
                                    <?php echo escapar($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Prioridade atual do chamado. -->
                    <div class="mb-3">
                        <label for="prioridade" class="form-label">Prioridade</label>
                        <select class="form-select" id="prioridade" name="prioridade" required>
                            <?php foreach ($prioridadesPermitidas as $prioridade): ?>
                                <option value="<?php echo escapar($prioridade); ?>" <?php echo $prioridade === $chamado["prioridade"] ? "selected" : ""; ?>>
                                    <?php echo escapar($prioridade); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Se o status virar Fechado, este usuario sera salvo como responsavel. -->
                    <div class="mb-3">
                        <label class="form-label">Usuario atual</label>
                        <div class="form-control bg-light">
                            <?php echo escapar($_SESSION["user_nome"]); ?> (<?php echo escapar($_SESSION["user_cargo"]); ?>)
                        </div>
                        <small class="text-muted">Ao fechar o chamado, este usuario sera registrado como responsavel.</small>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle"></i> Salvar alteracoes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php rodape(); ?>
