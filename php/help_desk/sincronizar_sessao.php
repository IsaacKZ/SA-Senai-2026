<?php
    /*
     * Sincronizacao de sessao Flask -> PHP.
     *
     * Flask e PHP nao compartilham sessao automaticamente. Esta pagina
     * pergunta ao Flask quem esta logado e, se o Flask confirmar, manda
     * esses dados para salvar_sessao.php criar a sessao PHP.
     */

    require_once __DIR__ . "/funcoes.php";

    $houveErro = isset($_GET["erro"]);

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
    async function sincronizarSessao() {
        try {
            // 1. Pergunta ao Flask se existe usuario logado.
            // credentials: "include" envia o cookie da sessao Flask junto.
            const respostaFlask = await fetch("<?php echo URL_SISTEMA_FLASK; ?>/api/sessao", {
                credentials: "include"
            });

            if (!respostaFlask.ok) {
                window.location.href = "<?php echo URL_SISTEMA_FLASK; ?>/login";
                return;
            }

            const sessao = await respostaFlask.json();

            // Defesa simples: mesmo com HTTP 200, a resposta precisa dizer que logou.
            if (!sessao.logado) {
                window.location.href = "<?php echo URL_SISTEMA_FLASK; ?>/login";
                return;
            }

            // 2. Envia os dados confirmados pelo Flask para o PHP criar $_SESSION.
            const respostaPhp = await fetch("salvar_sessao.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(sessao)
            });

            if (!respostaPhp.ok) {
                window.location.href = "sincronizar_sessao.php?erro=1";
                return;
            }

            // 3. Com a sessao PHP criada, volta para a listagem do Help Desk.
            window.location.href = "index.php";
        } catch (erro) {
            // Qualquer falha de rede/servidor cai na tela de erro amigavel.
            window.location.href = "sincronizar_sessao.php?erro=1";
        }
    }

    sincronizarSessao();
</script>
<?php endif; ?>

<?php rodape(); ?>
