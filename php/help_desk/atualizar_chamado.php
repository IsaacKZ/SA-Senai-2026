<?php
    require_once __DIR__ . "/../conexao.php";
    require_once __DIR__ . "/funcoes.php";

    exigir_login_php();

    // Quando falta o ID do chamado, nao ha como voltar para a tela correta.
    function voltar_para_lista() {
        header("Location: index.php?msg=erro");
        exit;
    }

    // Erros de validacao voltam para o proprio chamado.
    function voltar_para_chamado($chamadoId) {
        header("Location: detalhes_chamado.php?id=" . $chamadoId . "&msg=erro");
        exit;
    }

    // Usado quando o chamado esta sendo fechado.
    function usuario_existe($pdo, $usuarioId) {
        $consulta = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ?");
        $consulta->execute([$usuarioId]);

        return (int) $consulta->fetchColumn() > 0;
    }

    // Dados enviados pelo formulario de detalhes.
    $chamadoId = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $status = $_POST["status"] ?? "";
    $prioridade = $_POST["prioridade"] ?? "";
    $fechadoPorId = (int) $_SESSION["user_id"];

    $statusValidos = ["Aberto", "Em andamento", "Resolvido", "Fechado"];
    $prioridadesValidas = ["Baixa", "Media", "Alta"];

    // Validacoes de entrada antes do UPDATE.
    if (!$chamadoId) {
        voltar_para_lista();
    }

    if (!in_array($status, $statusValidos, true) || !in_array($prioridade, $prioridadesValidas, true)) {
        voltar_para_chamado($chamadoId);
    }

    // Status "Fechado" grava usuario responsavel e data de fechamento.
    if ($status === "Fechado") {
        if (!usuario_existe($pdo, $fechadoPorId)) {
            voltar_para_chamado($chamadoId);
        }

        $consulta = $pdo->prepare("
            UPDATE chamados
            SET status = ?,
                prioridade = ?,
                fechado_por_id = ?,
                data_fechado = COALESCE(data_fechado, CURRENT_TIMESTAMP)
            WHERE id = ?
        ");
        $consulta->execute([$status, $prioridade, $fechadoPorId, $chamadoId]);
    } else {
        // Ao reabrir ou mover o chamado, remove dados de fechamento.
        $consulta = $pdo->prepare("
            UPDATE chamados
            SET status = ?,
                prioridade = ?,
                fechado_por_id = NULL,
                data_fechado = NULL
            WHERE id = ?
        ");
        $consulta->execute([$status, $prioridade, $chamadoId]);
    }

    header("Location: detalhes_chamado.php?id=" . $chamadoId . "&msg=atualizado");
    exit;
?>
