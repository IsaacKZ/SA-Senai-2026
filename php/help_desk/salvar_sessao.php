<?php
    require_once __DIR__ . "/../conexao.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header("Content-Type: application/json; charset=utf-8");

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo json_encode(["ok" => false, "erro" => "Metodo nao permitido"]);
        exit;
    }

    $dados = json_decode(file_get_contents("php://input"), true);

    $usuarioId = isset($dados["id"]) ? (int) $dados["id"] : 0;
    $nome = trim($dados["nome"] ?? "");
    $cargo = trim($dados["cargo"] ?? "");

    if ($usuarioId <= 0 || $nome === "" || $cargo === "") {
        http_response_code(400);
        echo json_encode(["ok" => false, "erro" => "Dados de sessao invalidos"]);
        exit;
    }

    // Confere se o usuario recebido do Flask tambem existe no banco usado pelo PHP.
    $consulta = $pdo->prepare("
        SELECT id, nome, cargo
        FROM usuarios
        WHERE id = ? AND nome = ? AND cargo = ?
    ");
    $consulta->execute([$usuarioId, $nome, $cargo]);
    $usuario = $consulta->fetch();

    if (!$usuario) {
        http_response_code(403);
        echo json_encode(["ok" => false, "erro" => "Usuario nao encontrado"]);
        exit;
    }

    $_SESSION["user_id"] = $usuario["id"];
    $_SESSION["user_nome"] = $usuario["nome"];
    $_SESSION["user_cargo"] = $usuario["cargo"];

    echo json_encode(["ok" => true]);
?>
