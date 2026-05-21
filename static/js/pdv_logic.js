/**
 * SISTEMA DE FARMÁCIA "VIDA SAUDÁVEL" - SENAI
 * Lógica JavaScript do PDV (Ponto de Venda)
 * Arquivo: pdv_logic.js
 * 
 * FUNÇÕES:
 * - Gerenciamento do carrinho (adicionar/remover itens)
 * - Validação de medicamentos controlados (RN1)
 * - Envio da venda via AJAX (Fetch API)
 * - Feedback visual para o usuário
 */

// =====================================================
// VARIÁVEIS GLOBAIS
// =====================================================

let carrinho = []; // Array de itens no carrinho
let totalVenda = 0.0; // Total da venda

// =====================================================
// INICIALIZAÇÃO
// =====================================================

document.addEventListener('DOMContentLoaded', function () {
    console.log('[PDV] Sistema PDV inicializado');

    // Foco automático no campo de busca
    const campoBusca = document.getElementById('busca-produto');
    if (campoBusca) {
        campoBusca.focus();
    }

    // Configurar busca em tempo real
    if (campoBusca) {
        campoBusca.addEventListener('input', filtrarProdutos);
    }

    // Event delegation para botões de adicionar produto
    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-adicionar-produto')) {
            const btn = e.target.closest('.btn-adicionar-produto');
            const produtoId = parseInt(btn.dataset.produtoId);
            const nome = btn.dataset.produtoNome;
            const preco = parseFloat(btn.dataset.produtoPreco);
            const categoria = btn.dataset.produtoCategoria;
            const estoque = parseInt(btn.dataset.produtoEstoque);

            adicionarAoCarrinho(produtoId, nome, preco, categoria, estoque);
        }
    });

    // Atualizar display do carrinho
    atualizarCarrinho();
});

// =====================================================
// FUNÇÃO: FILTRAR PRODUTOS (Busca em Tempo Real)
// =====================================================

function filtrarProdutos() {
    const termo = document.getElementById('busca-produto').value.toLowerCase();
    const produtoCards = document.querySelectorAll('.produto-card');

    produtoCards.forEach(card => {
        const nome = card.dataset.nome.toLowerCase();
        const fabricante = card.dataset.fabricante.toLowerCase();

        if (nome.includes(termo) || fabricante.includes(termo)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// =====================================================
// FUNÇÃO: ADICIONAR PRODUTO AO CARRINHO
// =====================================================

function adicionarAoCarrinho(produtoId, nome, preco, categoria, estoque) {
    // Validar estoque
    if (estoque <= 0) {
        mostrarAlerta('Produto sem estoque!', 'warning');
        return;
    }

    // Verificar se já está no carrinho
    const itemExistente = carrinho.find(item => item.produto_id === produtoId);

    if (itemExistente) {
        // Verifica se quantidade não excede estoque
        if (itemExistente.quantidade >= estoque) {
            mostrarAlerta(`Estoque insuficiente! Disponível: ${estoque}`, 'warning');
            return;
        }
        itemExistente.quantidade++;
    } else {
        // RN1: Se for medicamento controlado, abrir modal
        if (categoria === 'Controlado') {
            abrirModalControlado(produtoId, nome, preco, categoria);
            return; // Não adiciona diretamente
        }

        // Adicionar novo item
        carrinho.push({
            produto_id: produtoId,
            nome: nome,
            preco: parseFloat(preco),
            quantidade: 1,
            categoria: categoria
        });
    }

    atualizarCarrinho();
    mostrarAlerta(`${nome} adicionado ao carrinho!`, 'success');
}

// =====================================================
// FUNÇÃO: MODAL DE MEDICAMENTO CONTROLADO (RN1)
// =====================================================

let produtoControladoTemp = null; // Armazena dados temporariamente

function abrirModalControlado(produtoId, nome, preco, categoria) {
    // Armazenar dados do produto
    produtoControladoTemp = {
        produto_id: produtoId,
        nome: nome,
        preco: parseFloat(preco),
        categoria: categoria
    };

    // Exibir nome do produto no modal
    document.getElementById('nome-produto-controlado').textContent = nome;

    // Limpar campos
    document.getElementById('receita-upload').value = '';
    document.getElementById('senha-supervisor').value = '';

    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalControlado'));
    modal.show();
}

function confirmarControlado() {
    const arquivoReceita = document.getElementById('receita-upload').files[0];
    const senhaSupervisor = document.getElementById('senha-supervisor').value;

    // Validar campos
    if (!arquivoReceita) {
        mostrarAlerta('Faça upload da receita médica!', 'danger');
        return;
    }

    if (!senhaSupervisor) {
        mostrarAlerta('Digite a senha do supervisor!', 'danger');
        return;
    }

    // Adicionar ao carrinho com flag de controlado
    carrinho.push({
        produto_id: produtoControladoTemp.produto_id,
        nome: produtoControladoTemp.nome,
        preco: produtoControladoTemp.preco,
        quantidade: 1,
        categoria: 'Controlado',
        receita: arquivoReceita,
        supervisor: senhaSupervisor
    });

    atualizarCarrinho();
    mostrarAlerta(`${produtoControladoTemp.nome} adicionado (Controlado)`, 'success');

    // Fechar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalControlado'));
    modal.hide();
}

// =====================================================
// FUNÇÃO: REMOVER ITEM DO CARRINHO
// =====================================================

function removerDoCarrinho(index) {
    const itemRemovido = carrinho[index];
    carrinho.splice(index, 1);
    atualizarCarrinho();
    mostrarAlerta(`${itemRemovido.nome} removido do carrinho`, 'info');
}

// =====================================================
// FUNÇÃO: ATUALIZAR DISPLAY DO CARRINHO
// =====================================================

function atualizarCarrinho() {
    const listaCarrinho = document.getElementById('lista-carrinho');
    const totalDisplay = document.getElementById('total-venda');
    const btnFinalizar = document.getElementById('btn-finalizar-venda');

    // Limpar lista
    listaCarrinho.innerHTML = '';

    // Calcular total
    totalVenda = 0;

    if (carrinho.length === 0) {
        listaCarrinho.innerHTML = '<p class="text-center text-muted">Carrinho vazio</p>';
        btnFinalizar.disabled = true;
    } else {
        btnFinalizar.disabled = false;

        carrinho.forEach((item, index) => {
            const subtotal = item.preco * item.quantidade;
            totalVenda += subtotal;

            // Badge controlado
            const badgeControlado = item.categoria === 'Controlado'
                ? '<span class="badge bg-warning ms-2">Controlado ✓</span>'
                : '';

            const itemHTML = `
                <div class="carrinho-item">
                    <div>
                        <strong>${item.nome}</strong> ${badgeControlado}
                        <br>
                        <small>Qtd: ${item.quantidade} x R$ ${item.preco.toFixed(2)}</small>
                    </div>
                    <div>
                        <strong>R$ ${subtotal.toFixed(2)}</strong>
                        <button class="btn btn-sm btn-danger ms-2" onclick="removerDoCarrinho(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            listaCarrinho.innerHTML += itemHTML;
        });
    }

    // Atualizar total
    totalDisplay.textContent = `R$ ${totalVenda.toFixed(2)}`;
}

// =====================================================
// FUNÇÃO: FINALIZAR VENDA (AJAX)
// =====================================================

async function finalizarVenda() {
    if (carrinho.length === 0) {
        mostrarAlerta('Carrinho vazio!', 'warning');
        return;
    }

    // Verificar se tem medicamento controlado
    const temControlado = carrinho.some(item => item.categoria === 'Controlado');

    // Preparar FormData (JSON + Arquivo)
    const formData = new FormData();

    // Adicionar itens do carrinho (sem arquivo de receita)
    const itensParaEnvio = carrinho.map(item => ({
        produto_id: item.produto_id,
        nome: item.nome,
        preco: item.preco,
        quantidade: item.quantidade,
        categoria: item.categoria
    }));

    formData.append('itens', JSON.stringify(itensParaEnvio));

    // Se tem controlado, adicionar receita e senha
    if (temControlado) {
        const itemControlado = carrinho.find(item => item.categoria === 'Controlado');
        formData.append('receita', itemControlado.receita);
        formData.append('supervisor', itemControlado.supervisor);
    }

    // Desabilitar botão durante envio
    const btnFinalizar = document.getElementById('btn-finalizar-venda');
    btnFinalizar.disabled = true;
    btnFinalizar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processando...';

    try {
        const response = await fetch('/api/venda', {
            method: 'POST',
            body: formData
        });

        const resultado = await response.json();

        if (resultado.success) {
            mostrarAlerta(resultado.message, 'success');

            // Salvar total ANTES de limpar o carrinho
            const totalFinal = totalVenda;

            // Limpar carrinho
            carrinho = [];
            atualizarCarrinho();

            // Exibir modal de sucesso (opcional)
            alert(`Venda #${resultado.venda_id} finalizada com sucesso!\nTotal: R$ ${totalFinal.toFixed(2)}`);
        } else {
            mostrarAlerta(`Erro: ${resultado.message}`, 'danger');
        }
    } catch (error) {
        console.error('[ERRO] finalizarVenda:', error);
        mostrarAlerta('Erro ao processar venda. Tente novamente.', 'danger');
    } finally {
        // Reabilitar botão
        btnFinalizar.disabled = false;
        btnFinalizar.innerHTML = '<i class="bi bi-check-circle"></i> FINALIZAR VENDA';
    }
}

// =====================================================
// FUNÇÃO: MOSTRAR ALERTA (Toast Bootstrap)
// =====================================================

function mostrarAlerta(mensagem, tipo) {
    // Criar toast dinamicamente
    const toastContainer = document.getElementById('toast-container') || criarToastContainer();

    const toastHTML = `
        <div class="toast align-items-center text-white bg-${tipo} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${mensagem}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHTML);

    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();

    // Remover após fechar
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

function criarToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}

// =====================================================
// FUNÇÃO: CANCELAR VENDA
// =====================================================

function cancelarVenda() {
    if (carrinho.length === 0) {
        return;
    }

    if (confirm('Deseja cancelar a venda atual?')) {
        carrinho = [];
        atualizarCarrinho();
        mostrarAlerta('Venda cancelada', 'info');
    }
}

// =====================================================
// ATALHOS DE TECLADO (UX)
// =====================================================

document.addEventListener('keydown', function (e) {
    // F2 - Foco no campo de busca
    if (e.key === 'F2') {
        e.preventDefault();
        document.getElementById('busca-produto').focus();
    }

    // F12 - Finalizar venda
    if (e.key === 'F12') {
        e.preventDefault();
        finalizarVenda();
    }

    // ESC - Cancelar venda
    if (e.key === 'Escape') {
        cancelarVenda();
    }
});
