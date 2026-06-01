<?php
    /*
     * Processa o formulario de novo chamado.
     *
     * A tela novo_chamado.php envia titulo, descricao e prioridade para ca.
     * Este arquivo valida os dados e grava o chamado ligado ao usuario logado.
     */

    require_once __DIR__ . "/../conexao.php";
    require_once __DIR__ . "/funcoes.php";

    exigir_login_php();

    // Centraliza o redirecionamento de erro para manter as validacoes limpas.
    function voltar_com_erro() {
        header("Location: index.php?msg=erro");
        exit;
    }

    // Garante que o chamado fique ligado a um usuario real da tabela usuarios.
    // Isso protege contra sessao quebrada ou ID inexistente.
    function usuario_existe($pdo, $usuarioId) {
        $consulta = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE id = ?");
        $consulta->execute([$usuarioId]);

        return (int) $consulta->fetchColumn() > 0;
    }

    // Dados enviados pelo formulario de novo chamado.
    $titulo = trim($_POST["titulo"] ?? "");
    $descricao = trim($_POST["descricao"] ?? "");
    $prioridade = $_POST["prioridade"] ?? "";
    $abertoPorId = (int) $_SESSION["user_id"];

    $prioridadesValidas = ["Baixa", "Media", "Alta"];
    $tituloMuitoGrande = strlen($titulo) > 100;

    // Validacoes basicas antes de gravar no banco.
    // O banco tambem tem CHECK/NOT NULL, mas validar aqui melhora o fluxo da tela.
    if ($titulo === "" || $descricao === "" || $tituloMuitoGrande) {
        voltar_com_erro();
    }

    // O chamado sempre precisa pertencer ao usuario logado.
    if (!$abertoPorId || !usuario_existe($pdo, $abertoPorId)) {
        voltar_com_erro();
    }

    // A prioridade precisa ser uma das opcoes aceitas pelo banco.
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
