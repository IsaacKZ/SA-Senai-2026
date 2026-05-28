<?php
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
            const respostaFlask = await fetch("<?php echo URL_SISTEMA_FLASK; ?>/api/sessao", {
                credentials: "include"
            });

            if (!respostaFlask.ok) {
                window.location.href = "<?php echo URL_SISTEMA_FLASK; ?>/login";
                return;
            }

            const sessao = await respostaFlask.json();

            if (!sessao.logado) {
                window.location.href = "<?php echo URL_SISTEMA_FLASK; ?>/login";
                return;
            }

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

            window.location.href = "index.php";
        } catch (erro) {
            window.location.href = "sincronizar_sessao.php?erro=1";
        }
    }

    sincronizarSessao();
</script>
<?php endif; ?>

<?php rodape(); ?>
