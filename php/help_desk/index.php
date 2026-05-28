<?php
    require_once __DIR__ . "/../conexao.php";
    require_once __DIR__ . "/funcoes.php";

    // Status aceitos pelo banco. Qualquer outro valor na URL e ignorado.
    $statusValidos = ["Aberto", "Em andamento", "Resolvido", "Fechado"];
    $statusSelecionado = $_GET["status"] ?? "";
    $temFiltroStatus = in_array($statusSelecionado, $statusValidos, true);

    // Comeca os cards zerados para todos os status aparecerem mesmo sem chamados.
    $totalPorStatus = [
        "Aberto" => 0,
        "Em andamento" => 0,
        "Resolvido" => 0,
        "Fechado" => 0,
    ];

    $consultaTotais = $pdo->query("SELECT status, COUNT(*) AS total FROM chamados GROUP BY status");

    // Preenche os totais que existem no banco.
    foreach ($consultaTotais->fetchAll() as $linha) {
        $totalPorStatus[$linha["status"]] = (int) $linha["total"];
    }

    // Consulta principal da tabela. O filtro por status entra somente se for valido.
    $sql = "
        SELECT
            c.id,
            c.titulo,
            c.prioridade,
            c.status,
            c.data_aberto,
            u.nome AS aberto_por
        FROM chamados c
        INNER JOIN usuarios u ON u.id = c.aberto_por_id
    ";

    if ($temFiltroStatus) {
        $sql .= " WHERE c.status = :status";
    }

    $sql .= " ORDER BY c.data_aberto DESC";

    $consultaChamados = $pdo->prepare($sql);

    if ($temFiltroStatus) {
        $consultaChamados->bindValue(":status", $statusSelecionado);
    }

    $consultaChamados->execute();
    $chamados = $consultaChamados->fetchAll();

    // A partir daqui comeca a renderizacao da tela.
    cabecalho("Help Desk");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-headset"></i> Help Desk
        </h2>
        <p class="text-muted mb-0">Acompanhamento de chamados internos da farmacia.</p>
    </div>

    <a href="novo_chamado.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Novo chamado
    </a>
</div>

<?php mostrar_mensagem(); ?>

<!-- Cards de resumo e atalho por status -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <a href="index.php?status=Aberto" class="text-decoration-none">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <i class="bi bi-folder2-open text-primary-custom"></i>
                    <h3><?php echo $totalPorStatus["Aberto"]; ?></h3>
                    <p class="text-muted mb-0">Abertos</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-3 mb-3">
        <a href="index.php?status=Em%20andamento" class="text-decoration-none">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <i class="bi bi-hourglass-split text-warning"></i>
                    <h3><?php echo $totalPorStatus["Em andamento"]; ?></h3>
                    <p class="text-muted mb-0">Em andamento</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-3 mb-3">
        <a href="index.php?status=Resolvido" class="text-decoration-none">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <i class="bi bi-check-circle text-secondary-custom"></i>
                    <h3><?php echo $totalPorStatus["Resolvido"]; ?></h3>
                    <p class="text-muted mb-0">Resolvidos</p>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-3 mb-3">
        <a href="index.php?status=Fechado" class="text-decoration-none">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <i class="bi bi-archive text-muted"></i>
                    <h3><?php echo $totalPorStatus["Fechado"]; ?></h3>
                    <p class="text-muted mb-0">Fechados</p>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Lista principal de chamados -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-list-task"></i>
            Chamados
            <?php if ($temFiltroStatus): ?>
                - <?php echo escapar($statusSelecionado); ?>
            <?php endif; ?>
        </span>

        <?php if ($temFiltroStatus): ?>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">Limpar filtro</a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <?php if (empty($chamados)): ?>
            <div class="alert alert-info mb-0">
                <i class="bi bi-info-circle"></i>
                Nenhum chamado encontrado.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Titulo</th>
                            <th>Prioridade</th>
                            <th>Status</th>
                            <th>Aberto por</th>
                            <th>Data</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chamados as $chamado): ?>
                            <tr>
                                <td><strong>#<?php echo escapar($chamado["id"]); ?></strong></td>
                                <td><?php echo escapar($chamado["titulo"]); ?></td>
                                <td><?php echo badge_prioridade($chamado["prioridade"]); ?></td>
                                <td><?php echo badge_status($chamado["status"]); ?></td>
                                <td><?php echo escapar($chamado["aberto_por"]); ?></td>
                                <td><?php echo formatar_data($chamado["data_aberto"]); ?></td>
                                <td class="text-end">
                                    <a href="detalhes_chamado.php?id=<?php echo escapar($chamado["id"]); ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php rodape(); ?>
