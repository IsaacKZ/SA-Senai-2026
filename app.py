from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify
from functools import wraps
import os
from datetime import datetime

# Importações locais
from config import Config
import db

"""
SISTEMA DE GESTÃO FARMACÊUTICA
Flask Principal
Arquivo: app.py
"""

# CONFIGURAÇÃO DA APLICAÇÃO FLASK
# =====================================================

app = Flask(__name__)
app.config.from_object(Config)
Config.init_app(app)

# =====================================================
# FILTRO JINJA PARA DATAS (SQLite retorna strings)
# =====================================================

@app.template_filter('format_date')
def format_date_filter(value, format='%d/%m/%Y'):
    """Formata data para exibição. Aceita string ou datetime."""
    if value is None:
        return ''
    if isinstance(value, str):
        try:
            # Tenta converter string para datetime
            if ' ' in value:
                value = datetime.strptime(value, '%Y-%m-%d %H:%M:%S')
            else:
                value = datetime.strptime(value, '%Y-%m-%d')
        except ValueError:
            return value  # Retorna a string original se falhar
    return value.strftime(format)

@app.template_filter('format_datetime')
def format_datetime_filter(value, format='%d/%m/%Y %H:%M'):
    """Formata data/hora para exibição."""
    return format_date_filter(value, format)

# =====================================================
# DECORADOR DE AUTENTICAÇÃO (Proteger Rotas)
# =====================================================

def login_required(f):
    """
    Decorador para proteger rotas que exigem autenticação.
    Redireciona para /login se usuário não estiver na sessão.
    """
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'user_id' not in session:
            flash('Você precisa fazer login para acessar esta página.', 'warning')
            return redirect(url_for('login'))
        return f(*args, **kwargs)
    return decorated_function

# =====================================================
# ROTAS: AUTENTICAÇÃO
# =====================================================

@app.route('/login', methods=['GET', 'POST'])
def login():
    """
    Tela de login do sistema.
    GET: Exibe formulário
    POST: Valida credenciais e inicia sessão
    """
    # Se já estiver logado, redireciona para dashboard
    if 'user_id' in session:
        return redirect(url_for('dashboard'))
    
    # Buscar lista de usuários para o dropdown
    usuarios = db.listar_usuarios()
    
    if request.method == 'POST':
        login_usuario = request.form.get('login')
        senha = request.form.get('senha')
        
        # Validação básica
        if not login_usuario or not senha:
            flash('Preencha todos os campos!', 'danger')
            return render_template('login.html', usuarios=usuarios)
        
        # Verificar credenciais no banco (db.py)
        usuario = db.verificar_login(login_usuario, senha)
        
        if usuario:
            # Autenticado com sucesso - Criar sessão
            session['user_id'] = usuario['id']
            session['user_nome'] = usuario['nome']
            session['user_cargo'] = usuario['cargo']
            session.permanent = True  # Usa PERMANENT_SESSION_LIFETIME do config.py
            
            flash(f'Bem-vindo(a), {usuario["nome"]}!', 'success')
            return redirect(url_for('dashboard'))
        else:
            # Credenciais inválidas
            flash('Usuário ou senha incorretos!', 'danger')
    
    return render_template('login.html', usuarios=usuarios)

@app.route('/logout')
def logout():
    """Encerra a sessão do usuário e redireciona para login"""
    session.clear()
    flash('Você saiu do sistema.', 'info')
    return redirect(url_for('login'))

# =====================================================
# ROTAS: NAVEGAÇÃO PRINCIPAL
# =====================================================

@app.route('/')
def index():
    """
    Rota raiz - Redireciona conforme estado de autenticação
    """
    if 'user_id' in session:
        return redirect(url_for('dashboard'))
    return redirect(url_for('login'))

@app.route('/dashboard')
@login_required
def dashboard():
    """
    Tela inicial (Home) do sistema.
    RN2: Exibe alertas de validade para lotes vencendo em < 30 dias.
    """
    try:
        # Buscar lotes vencendo (Regra RN2)
        lotes_vencendo = db.get_lotes_vencendo()
        
        # Buscar vendas recentes para exibir no dashboard
        vendas_recentes = db.get_vendas_recentes(limite=5)
        
        return render_template('dashboard.html', 
                             lotes_vencendo=lotes_vencendo,
                             vendas_recentes=vendas_recentes)
    except Exception as e:
        flash(f'Erro ao carregar dashboard: {str(e)}', 'danger')
        return render_template('dashboard.html', lotes_vencendo=[], vendas_recentes=[])

# =====================================================
# ROTAS: GESTÃO DE PRODUTOS E ESTOQUE
# =====================================================

@app.route('/produtos')
@login_required
def produtos():
    """
    Tela de gestão de produtos e lotes.
    Exibe lista em formato Accordion (Bootstrap).
    """
    try:
        produtos_lista = db.listar_produtos()
        return render_template('produtos.html', produtos=produtos_lista)
    except Exception as e:
        flash(f'Erro ao carregar produtos: {str(e)}', 'danger')
        return render_template('produtos.html', produtos=[])

@app.route('/api/produtos', methods=['POST'])
@login_required
def criar_produto():
    """
    API para criar novo produto.
    Retorna JSON para consumo via AJAX.
    """
    try:
        nome = request.form.get('nome')
        fabricante = request.form.get('fabricante')
        categoria = request.form.get('categoria')
        preco_venda = request.form.get('preco_venda')
        descricao = request.form.get('descricao', '')
        
        # Validação
        if not all([nome, fabricante, categoria, preco_venda]):
            return jsonify({'success': False, 'message': 'Preencha todos os campos obrigatórios'}), 400
        
        produto_id = db.criar_produto(nome, fabricante, categoria, float(preco_venda), descricao)
        
        if produto_id:
            flash('Produto cadastrado com sucesso!', 'success')
            return jsonify({'success': True, 'produto_id': produto_id})
        else:
            return jsonify({'success': False, 'message': 'Erro ao salvar no banco'}), 500
            
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/produtos/<int:produto_id>', methods=['PUT'])
@login_required
def atualizar_produto(produto_id):
    """API para atualizar produto existente"""
    try:
        data = request.get_json()
        
        sucesso = db.editar_produto(
            produto_id,
            data['nome'],
            data['fabricante'],
            data['categoria'],
            float(data['preco_venda']),
            data.get('descricao', '')
        )
        
        if sucesso:
            return jsonify({'success': True, 'message': 'Produto atualizado'})
        else:
            return jsonify({'success': False, 'message': 'Erro ao atualizar'}), 500
            
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/produtos/<int:produto_id>', methods=['DELETE'])
@login_required
def deletar_produto(produto_id):
    """API para deletar produto"""
    try:
        sucesso = db.deletar_produto(produto_id)
        
        if sucesso:
            flash('Produto removido com sucesso!', 'success')
            return jsonify({'success': True})
        else:
            return jsonify({'success': False, 'message': 'Erro ao deletar'}), 500
            
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/lotes/produto/<int:produto_id>', methods=['GET'])
@login_required
def listar_lotes(produto_id):
    """API para listar lotes de um produto específico"""
    try:
        lotes = db.listar_lotes_por_produto(produto_id)
        return jsonify({'success': True, 'lotes': lotes})
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/lotes', methods=['POST'])
@login_required
def criar_lote():
    """
    API para criar novo lote de estoque.
    Usado no modal de "Adicionar Lote" dentro do accordion.
    """
    try:
        produto_id = request.form.get('produto_id')
        numero_lote = request.form.get('numero_lote')
        data_validade = request.form.get('data_validade')
        qtd_atual = request.form.get('qtd_atual')
        
        # Validação
        if not all([produto_id, numero_lote, data_validade, qtd_atual]):
            return jsonify({'success': False, 'message': 'Preencha todos os campos'}), 400
        
        lote_id = db.criar_lote(int(produto_id), numero_lote, data_validade, int(qtd_atual))
        
        if lote_id:
            flash('Lote adicionado ao estoque!', 'success')
            return jsonify({'success': True, 'lote_id': lote_id})
        else:
            return jsonify({'success': False, 'message': 'Erro ao salvar lote'}), 500
            
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

# =====================================================
# ROTAS: PONTO DE VENDA (PDV)
# =====================================================

@app.route('/pdv')
@login_required
def pdv():
    """
    Tela de Ponto de Venda (Frente de Caixa).
    Layout Split Screen: Catálogo + Carrinho
    """
    try:
        # Buscar produtos disponíveis (com estoque > 0)
        produtos_disponiveis = db.listar_produtos()
        # Filtrar apenas produtos com estoque
        produtos_disponiveis = [p for p in produtos_disponiveis if p['estoque_total'] > 0]
        
        return render_template('pdv.html', produtos=produtos_disponiveis)
    except Exception as e:
        flash(f'Erro ao carregar PDV: {str(e)}', 'danger')
        return render_template('pdv.html', produtos=[])

@app.route('/api/produto/<int:produto_id>/detalhes', methods=['GET'])
@login_required
def produto_detalhes(produto_id):
    """
    API para obter detalhes completos de um produto (incluindo dados completos).
    Usado no PDV para verificar categoria (Controlado ou não).
    """
    try:
        produto = db.get_produto_por_id(produto_id)
        if produto:
            return jsonify({'success': True, 'produto': produto})
        else:
            return jsonify({'success': False, 'message': 'Produto não encontrado'}), 404
    except Exception as e:
        return jsonify({'success': False, 'message': str(e)}), 500

@app.route('/api/venda', methods=['POST'])
@login_required
def finalizar_venda():
    """
    API para processar venda completa.
    RN1: Valida receita médica se houver medicamento controlado.
    RN3: Usa lógica FEFO (First Expire, First Out) via db.get_lote_fefo()
    """
    try:
        # Dados do carrinho (JSON)
        itens_json = request.form.get('itens')  # String JSON
        supervisor = request.form.get('supervisor')  # Senha supervisor (se houver controlado)
        
        # Arquivo de receita (se houver)
        arquivo_receita = request.files.get('receita')
        
        if not itens_json:
            return jsonify({'success': False, 'message': 'Carrinho vazio'}), 400
        
        import json
        itens_carrinho = json.loads(itens_json)
        
        # Validação RN1: Se tem item controlado, DEVE ter receita e supervisor
        tem_controlado = any(item.get('categoria') == 'Controlado' for item in itens_carrinho)
        
        if tem_controlado:
            # Verificar se receita foi enviada
            if not arquivo_receita:
                return jsonify({
                    'success': False, 
                    'message': 'Medicamento controlado requer upload da receita médica!'
                }), 400
            
            # Verificar se senha do supervisor foi informada
            if not supervisor:
                return jsonify({
                    'success': False, 
                    'message': 'Medicamento controlado requer senha do supervisor!'
                }), 400
            
            # VALIDAR SENHA MESTRA (Case-sensitive)
            if supervisor != Config.SENHA_SUPERVISOR_MESTRA:
                return jsonify({
                    'success': False, 
                    'message': 'Senha do supervisor incorreta! Verifique maiúsculas e minúsculas.'
                }), 403
        
        # Validar upload de receita (se houver) - NÃO salva, só valida
        if arquivo_receita:
            if not Config.allowed_file(arquivo_receita.filename):
                return jsonify({
                    'success': False,
                    'message': 'Formato de arquivo inválido. Use PDF, JPG ou PNG.'
                }), 400
            # Arquivo validado - descartado (não salva)
        
        # Preparar itens para db.registrar_venda()
        # RN3: Para cada produto, buscar lote FEFO automaticamente
        itens_venda = []
        for item in itens_carrinho:
            produto_id = item['produto_id']
            quantidade = item['quantidade']
            preco = item['preco']
            
            # Buscar lote FEFO (First Expire, First Out)
            lote = db.get_lote_fefo(produto_id, quantidade)
            
            if not lote:
                return jsonify({
                    'success': False, 
                    'message': f'Estoque insuficiente para {item["nome"]}'
                }), 400
            
            itens_venda.append({
                'produto_id': produto_id,
                'lote_id': lote['id'],
                'quantidade': quantidade,
                'preco': preco
            })
        
        # Registrar venda no banco
        venda_id = db.registrar_venda(
            itens_venda,
            session['user_id'],
            supervisor=supervisor
        )
        
        if venda_id:
            return jsonify({
                'success': True, 
                'message': 'Venda finalizada com sucesso!',
                'venda_id': venda_id
            })
        else:
            return jsonify({'success': False, 'message': 'Erro ao registrar venda'}), 500
            
    except Exception as e:
        print(f"[ERRO] finalizar_venda: {e}")
        return jsonify({'success': False, 'message': str(e)}), 500

# =====================================================
# ROTAS: RELATÓRIOS
# =====================================================

@app.route('/relatorios')
@login_required
def relatorios():
    """
    Tela de relatórios gerenciais.
    Demonstra JOINs complexos e agregações (critério de avaliação).
    """
    try:
        vendas_recentes = db.get_vendas_recentes(limite=20)
        lotes_vencendo = db.get_lotes_vencendo()
        
        return render_template('relatorios.html', 
                             vendas=vendas_recentes,
                             lotes_vencendo=lotes_vencendo)
    except Exception as e:
        flash(f'Erro ao carregar relatórios: {str(e)}', 'danger')
        return render_template('relatorios.html', vendas=[], lotes_vencendo=[])

# =====================================================
# TRATAMENTO DE ERROS (UX - Critério de Avaliação)
# =====================================================

@app.errorhandler(404)
def page_not_found(e):
    """Página customizada para erro 404"""
    return render_template('404.html'), 404

@app.errorhandler(500)
def internal_error(e):
    """Página customizada para erro 500"""
    flash('Ocorreu um erro interno. Tente novamente.', 'danger')
    return redirect(url_for('dashboard'))

# =====================================================
# EXECUÇÃO DA APLICAÇÃO
# =====================================================

if __name__ == '__main__':
    # Criar pastas necessárias se não existirem
    os.makedirs('static/css', exist_ok=True)
    os.makedirs('static/js', exist_ok=True)
    
    print("\n" + "="*50)
    print("🏥 SISTEMA DE GESTÃO FARMACÊUTICA")
    print("="*50)
    print(f"🔒 Secret Key configurada: {'✓' if app.config['SECRET_KEY'] else '✗'}")
    print(f"🗄️  Banco de dados: {Config.DATABASE_PATH}")
    print("="*50)
    print("🚀 Servidor iniciado em: http://localhost:5000")
    print("="*50 + "\n")
    
    app.run(debug=Config.DEBUG, host='0.0.0.0', port=5000)