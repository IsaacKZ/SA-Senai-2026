<?php
    require_once __DIR__ . "/../conexao.php";

    // Centraliza o redirecionamento de erro para manter as validacoes mais limpas.
    function voltar_com_erro() {
        header("Location: index.php?msg=erro");
        exit;
    }

    // Garante que o chamado fique ligado a um usuario real da tabela usuarios.
    function usuario_existe($pdo, $usuarioId) {
        $consulta = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ?");
        $consulta->execute([$usuarioId]);

        return (int) $consulta->fetchColumn() > 0;
    }

    // Dados enviados pelo formulario de novo chamado.
    $titulo = trim($_POST["titulo"] ?? "");
    $descricao = trim($_POST["descricao"] ?? "");
    $prioridade = $_POST["prioridade"] ?? "";
    $abertoPorId = filter_input(INPUT_POST, "aberto_por_id", FILTER_VALIDATE_INT);

    $prioridadesValidas = ["Baixa", "Media", "Alta"];
    $tituloMuitoGrande = strlen($titulo) > 100;

    // Validacoes basicas antes de gravar no banco.
    if ($titulo === "" || $descricao === "" || $tituloMuitoGrande) {
        voltar_com_erro();
    }

    if (!$abertoPorId || !usuario_existe($pdo, $abertoPorId)) {
        voltar_com_erro();
    }

    if (!in_array($prioridade, $prioridadesValidas, true)) {
        voltar_com_erro();
    }

    // Insert com placeholders para evitar SQL Injection.
    $consulta = $pdo->prepare("
        INSERT INTO chamados (titulo, descricao, prioridade, aberto_por_id)
        VALUES (?, ?, ?, ?)
    ");

    $consulta->execute([$titulo, $descricao, $prioridade, $abertoPorId]);

    header("Location: index.php?msg=criado");
    exit;
?>
