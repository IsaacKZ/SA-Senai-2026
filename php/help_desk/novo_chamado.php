<?php
    /*
     * Formulario para abrir um novo chamado.
     *
     * Esta tela so monta o formulario. A gravacao no banco acontece em
     * salvar_chamado.php, para manter exibicao e processamento separados.
     */

    require_once __DIR__ . "/../conexao.php";
    require_once __DIR__ . "/funcoes.php";

    exigir_login_php();

    // Renderiza o formulario de abertura.
    cabecalho("Novo chamado");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">
            <i class="bi bi-plus-circle"></i> Novo chamado
        </h2>
        <p class="text-muted mb-0">Registre um problema ou solicitacao interna.</p>
    </div>

    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-pencil-square"></i> Dados do chamado
    </div>

    <div class="card-body">
        <form method="POST" action="salvar_chamado.php">
            <!-- Titulo curto para aparecer bem na tabela de chamados. -->
            <div class="mb-3">
                <label for="titulo" class="form-label">Titulo *</label>
                <input type="text" class="form-control" id="titulo" name="titulo" maxlength="100" required>
            </div>

            <!-- Descricao livre do problema ou solicitacao. -->
            <div class="mb-3">
                <label for="descricao" class="form-label">Descricao *</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="5" required></textarea>
            </div>

            <div class="row">
                <!-- As prioridades abaixo precisam bater com o CHECK da tabela chamados. -->
                <div class="col-md-6 mb-3">
                    <label for="prioridade" class="form-label">Prioridade *</label>
                    <select class="form-select" id="prioridade" name="prioridade" required>
                        <option value="" disabled selected>Selecione...</option>
                        <option value="Baixa">Baixa</option>
                        <option value="Media">Media</option>
                        <option value="Alta">Alta</option>
                    </select>
                </div>

                <!-- O usuario vem da sessao PHP sincronizada com o Flask. -->
                <div class="col-md-6 mb-3">
                    <label class="form-label">Aberto por</label>
                    <div class="form-control bg-light">
                        <?php echo escapar($_SESSION["user_nome"]); ?> (<?php echo escapar($_SESSION["user_cargo"]); ?>)
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Abrir chamado
                </button>
            </div>
        </form>
    </div>
</div>

<?php rodape(); ?>
