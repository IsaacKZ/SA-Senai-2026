<?php
    /*
     * Ponte de sessao Flask -> PHP.
     *
     * O login principal esta no Flask, mas o Help Desk esta em PHP.
     * O problema: a sessao do Flask nao vira $_SESSION do PHP sozinha.
     *
     * Entao este arquivo faz o meio de campo:
     * 1. pergunta ao Flask quem esta logado;
     * 2. manda esses dados para salvar_sessao.php;
     * 3. deixa o PHP criar a propria $_SESSION.
     */

    // Carrega funcoes comuns do Help Desk, como cabecalho(), rodape()
    // e a constante URL_SISTEMA_FLASK.
    require_once __DIR__ . "/funcoes.php";

    // Se a URL tiver ?erro=1, a pagina mostra erro em vez de tentar de novo.
    // Isso evita ficar preso num loop tentando sincronizar uma sessao quebrada.
    $houveErro = isset($_GET["erro"]);

    // Imprime o comeco do HTML: head, navbar, CSS e abertura do <main>.
    cabecalho("Sincronizando sessao");
?>

<div class="card">
    <div class="card-body text-center py-5">
        <?php if ($houveErro): ?>
            <h2 class="h4">Nao foi possivel sincronizar a sessao</h2>
            <p class="text-muted">Entre novamente pelo sistema principal e acesse o Help Desk outra vez.</p>
            <a href="<?php echo URL_SISTEMA_FLASK; ?>/login" class="btn btn-primary">Ir para o login</a>
        <?php else: ?>
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <h2 class="h4">Sincronizando sessao</h2>
            <p class="text-muted mb-0">Aguarde enquanto o Help Desk identifica o usuario logado no sistema.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (!$houveErro): ?>
<script>
    // Esta funcao roda no navegador, nao no PHP.
    // Ela precisa ser async porque usa await.
    // await significa: "espera esta resposta chegar antes de continuar".
    async function sincronizarSessao() {
        try {
            // 1. Pergunta ao Flask se existe usuario logado.
            // fetch faz uma requisicao HTTP, tipo o navegador acessando uma URL.
            // credentials: "include" manda junto o cookie de login do Flask.
            // Sem esse cookie, o Flask nao teria como saber quem e o usuario.
            const respostaFlask = await fetch("<?php echo URL_SISTEMA_FLASK; ?>/api/sessao", {
                credentials: "include"
            });

            // Se o Flask respondeu com erro HTTP, nem tenta continuar.
            // Manda o usuario para o login principal.
            if (!respostaFlask.ok) {
                window.location.href = "<?php echo URL_SISTEMA_FLASK; ?>/login";
                return;
            }

            // A resposta do Flask vem em JSON.
            // .json() transforma esse texto JSON
            const sessao = await respostaFlask.json();

            // Se nao tiver logado no Flask, manda de volta pra tela de login.
            if (!sessao.logado) {
                window.location.href = "<?php echo URL_SISTEMA_FLASK; ?>/login";
                return;
            }

            // 2. Agora que o Flask confirmou o usuario, manda esses dados para o PHP.
            // salvar_sessao.php vai validar o usuario no banco e criar $_SESSION.
            const respostaPhp = await fetch("salvar_sessao.php", {
                // POST e usado quando estamos enviando dados para o servidor processar.
                method: "POST",
                headers: {
                    // Avisa o PHP que o corpo da requisicao esta em JSON.
                    "Content-Type": "application/json"
                },
                // Requisicao HTTP envia texto.
                // Por isso transformamos o JavaScript "sessao" em texto JSON.
                body: JSON.stringify(sessao)
            });

            // Se o PHP nao conseguiu salvar a sessao, mostra a tela de erro.
            if (!respostaPhp.ok) {
                window.location.href = "sincronizar_sessao.php?erro=1";
                return;
            }

            // 3. Neste ponto a $_SESSION do PHP ja foi criada.
            // Agora o usuario pode entrar no Help Desk.
            window.location.href = "index.php";
        } catch (erro) {
            window.location.href = "sincronizar_sessao.php?erro=1";
        }
    }

    sincronizarSessao();
    
    </script>
<?php endif; ?>

<?php // Imprime o fim do HTML: fechamento do <main>, rodape e scripts. ?>
<?php rodape(); ?>
