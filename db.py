"""
Camada de Acesso a Dados (Data Access Layer) - SQLite
"""

import sqlite3
from werkzeug.security import check_password_hash
from config import Config
from datetime import date, timedelta

# =====================================================
# CONEXÃO COM O BANCO DE DADOS
# =====================================================

def get_db_connection():
    """
    Estabelece e retorna uma conexão com o banco SQLite.
    """
    try:
        conexao = sqlite3.connect(Config.DATABASE_PATH)
        conexao.row_factory = sqlite3.Row  # Retorna dicts ao invés de tuples
        return conexao
    except Exception as err:
        print(f"[ERRO DB] Falha na conexão: {err}")
        return None

def dict_from_row(row):
    """Converte sqlite3.Row para dict"""
    if row is None:
        return None
    return dict(row)

# =====================================================
# MÓDULO: AUTENTICAÇÃO E USUÁRIOS
# =====================================================

def verificar_login(login, senha):
    """
    Verifica credenciais de login.
    Retorna dados do usuário se autenticado, ou None se falhou.
    """
    conexao = get_db_connection()
    if not conexao:
        return None
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            SELECT id, nome, login, senha_hash, cargo
            FROM usuarios
            WHERE login = ?
        """, (login,))
        
        row = cursor.fetchone()
        
        if row:
            usuario = dict_from_row(row)
            if check_password_hash(usuario['senha_hash'], senha):
                return {
                    'id': usuario['id'],
                    'nome': usuario['nome'],
                    'login': usuario['login'],
                    'cargo': usuario['cargo']
                }
        
        return None
    
    except Exception as err:
        print(f"[ERRO] verificar_login: {err}")
        return None
    finally:
        conexao.close()

def get_usuario_por_id(usuario_id):
    """Busca dados de um usuário pelo ID."""
    conexao = get_db_connection()
    if not conexao:
        return None
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            SELECT id, nome, login, cargo
            FROM usuarios
            WHERE id = ?
        """, (usuario_id,))
        
        return dict_from_row(cursor.fetchone())
    
    except Exception as err:
        print(f"[ERRO] get_usuario_por_id: {err}")
        return None
    finally:
        conexao.close()

def listar_usuarios():
    """Retorna lista de todos os usuários para o dropdown de login."""
    conexao = get_db_connection()
    if not conexao:
        return []
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            SELECT id, nome, login, cargo
            FROM usuarios
            ORDER BY nome ASC
        """)
        
        return [dict_from_row(row) for row in cursor.fetchall()]
    
    except Exception as err:
        print(f"[ERRO] listar_usuarios: {err}")
        return []
    finally:
        conexao.close()

# =====================================================
# MÓDULO: PRODUTOS (CRUD Completo)
# =====================================================

def listar_produtos():
    """
    Retorna lista de todos os produtos com estoque total calculado.
    RN4: Aplica desconto automático de 20% para produtos vencendo em 30 dias.
    """
    conexao = get_db_connection()
    if not conexao:
        return []
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            SELECT 
                p.id,
                p.nome,
                p.fabricante,
                p.categoria,
                COALESCE(SUM(el.qtd_atual), 0) AS estoque_total,
                MIN(el.data_validade) AS validade_mais_proxima,
                p.preco_venda,
                p.descricao,
                CAST(julianday(MIN(el.data_validade)) - julianday('now') AS INTEGER) AS dias_para_vencer
            FROM produtos p
            LEFT JOIN estoque_lotes el ON p.id = el.produto_id AND el.qtd_atual > 0
            GROUP BY p.id, p.nome, p.fabricante, p.categoria, p.preco_venda, p.descricao
            ORDER BY p.nome ASC
        """)
        
        produtos = []
        for row in cursor.fetchall():
            produto = dict_from_row(row)
            
            # RN4: Desconto automático de 20% para produtos vencendo em 30 dias
            dias = produto.get('dias_para_vencer')
            if dias is not None and dias <= 30:
                produto['tem_desconto'] = True
                produto['percentual_desconto'] = 20
                produto['preco_original'] = produto['preco_venda']
                produto['preco_venda'] = round(produto['preco_venda'] * 0.80, 2)  # 20% off
            else:
                produto['tem_desconto'] = False
                
            produtos.append(produto)
        
        return produtos
    
    except Exception as err:
        print(f"[ERRO] listar_produtos: {err}")
        return []
    finally:
        conexao.close()

def get_produto_por_id(produto_id):
    """Busca um produto específico pelo ID"""
    conexao = get_db_connection()
    if not conexao:
        return None
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            SELECT id, nome, fabricante, categoria, preco_venda, descricao
            FROM produtos
            WHERE id = ?
        """, (produto_id,))
        
        return dict_from_row(cursor.fetchone())
    
    except Exception as err:
        print(f"[ERRO] get_produto_por_id: {err}")
        return None
    finally:
        conexao.close()

def criar_produto(nome, fabricante, categoria, preco_venda, descricao=''):
    """
    Cria um novo produto no catálogo.
    Retorna o ID do produto criado ou None se falhou.
    """
    conexao = get_db_connection()
    if not conexao:
        return None
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            INSERT INTO produtos (nome, fabricante, categoria, preco_venda, descricao)
            VALUES (?, ?, ?, ?, ?)
        """, (nome, fabricante, categoria, preco_venda, descricao))
        
        conexao.commit()
        return cursor.lastrowid
    
    except Exception as err:
        print(f"[ERRO] criar_produto: {err}")
        conexao.rollback()
        return None
    finally:
        conexao.close()

def editar_produto(produto_id, nome, fabricante, categoria, preco_venda, descricao):
    """Atualiza dados de um produto existente"""
    conexao = get_db_connection()
    if not conexao:
        return False
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            UPDATE produtos
            SET nome = ?, fabricante = ?, categoria = ?, 
                preco_venda = ?, descricao = ?
            WHERE id = ?
        """, (nome, fabricante, categoria, preco_venda, descricao, produto_id))
        
        conexao.commit()
        return True
    
    except Exception as err:
        print(f"[ERRO] editar_produto: {err}")
        conexao.rollback()
        return False
    finally:
        conexao.close()

def deletar_produto(produto_id):
    """Remove um produto do sistema."""
    conexao = get_db_connection()
    if not conexao:
        return False
    
    try:
        cursor = conexao.cursor()
        cursor.execute("DELETE FROM produtos WHERE id = ?", (produto_id,))
        
        conexao.commit()
        return True
    
    except Exception as err:
        print(f"[ERRO] deletar_produto: {err}")
        conexao.rollback()
        return False
    finally:
        conexao.close()

# =====================================================
# MÓDULO: LOTES E ESTOQUE
# =====================================================

def listar_lotes_por_produto(produto_id):
    """
    Retorna todos os lotes de um produto específico.
    """
    conexao = get_db_connection()
    if not conexao:
        return []
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            SELECT 
                id,
                numero_lote,
                data_validade,
                qtd_atual,
                CAST(julianday(data_validade) - julianday('now') AS INTEGER) AS dias_para_vencer,
                CASE 
                    WHEN date(data_validade) <= date('now', '+30 days') 
                    THEN 1 ELSE 0
                END AS vencendo
            FROM estoque_lotes
            WHERE produto_id = ?
            ORDER BY data_validade ASC
        """, (produto_id,))
        
        return [dict_from_row(row) for row in cursor.fetchall()]
    
    except Exception as err:
        print(f"[ERRO] listar_lotes_por_produto: {err}")
        return []
    finally:
        conexao.close()

def criar_lote(produto_id, numero_lote, data_validade, qtd_atual):
    """Adiciona um novo lote ao estoque"""
    conexao = get_db_connection()
    if not conexao:
        return None
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            INSERT INTO estoque_lotes (produto_id, numero_lote, data_validade, qtd_atual)
            VALUES (?, ?, ?, ?)
        """, (produto_id, numero_lote, data_validade, qtd_atual))
        
        conexao.commit()
        return cursor.lastrowid
    
    except Exception as err:
        print(f"[ERRO] criar_lote: {err}")
        conexao.rollback()
        return None
    finally:
        conexao.close()

def get_lote_fefo(produto_id, quantidade_necessaria):
    """
    RN3 - BAIXA DE ESTOQUE FEFO (First Expire, First Out)
    Retorna o lote com validade mais próxima que tenha estoque suficiente.
    """
    conexao = get_db_connection()
    if not conexao:
        return None
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            SELECT id, numero_lote, data_validade, qtd_atual
            FROM estoque_lotes
            WHERE produto_id = ? AND qtd_atual >= ?
            ORDER BY data_validade ASC
            LIMIT 1
        """, (produto_id, quantidade_necessaria))
        
        return dict_from_row(cursor.fetchone())
    
    except Exception as err:
        print(f"[ERRO] get_lote_fefo: {err}")
        return None
    finally:
        conexao.close()

def baixar_estoque(lote_id, quantidade):
    """
    Diminui a quantidade de um lote específico.
    """
    conexao = get_db_connection()
    if not conexao:
        return False
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            UPDATE estoque_lotes
            SET qtd_atual = qtd_atual - ?
            WHERE id = ? AND qtd_atual >= ?
        """, (quantidade, lote_id, quantidade))
        
        conexao.commit()
        return cursor.rowcount > 0
    
    except Exception as err:
        print(f"[ERRO] baixar_estoque: {err}")
        conexao.rollback()
        return False
    finally:
        conexao.close()

# =====================================================
# MÓDULO: ALERTAS E RELATÓRIOS
# =====================================================

def get_lotes_vencendo():
    """
    RN2 - ALERTA DE VALIDADE
    Retorna lotes que vencem nos próximos 30 dias.
    """
    conexao = get_db_connection()
    if not conexao:
        return []
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            SELECT 
                el.id AS lote_id,
                p.nome AS produto_nome,
                p.fabricante,
                el.numero_lote,
                el.data_validade,
                el.qtd_atual,
                CAST(julianday(el.data_validade) - julianday('now') AS INTEGER) AS dias_para_vencer
            FROM estoque_lotes el
            INNER JOIN produtos p ON el.produto_id = p.id
            WHERE date(el.data_validade) BETWEEN date('now') AND date('now', '+30 days')
                AND el.qtd_atual > 0
            ORDER BY el.data_validade ASC
        """)
        
        return [dict_from_row(row) for row in cursor.fetchall()]
    
    except Exception as err:
        print(f"[ERRO] get_lotes_vencendo: {err}")
        return []
    finally:
        conexao.close()

# =====================================================
# MÓDULO: VENDAS E TRANSAÇÕES
# =====================================================

def registrar_venda(itens, usuario_id, supervisor=None):
    """
    Registra uma venda completa no sistema.
    """
    conexao = get_db_connection()
    if not conexao:
        return None
    
    try:
        cursor = conexao.cursor()
        
        # Calcula total da venda
        total = sum(item['quantidade'] * item['preco'] for item in itens)
        
        # Insere cabeçalho da venda
        cursor.execute("""
            INSERT INTO vendas (total, usuario_id, supervisor_liberacao)
            VALUES (?, ?, ?)
        """, (total, usuario_id, supervisor))
        
        venda_id = cursor.lastrowid
        
        # Insere itens da venda
        for item in itens:
            subtotal = item['quantidade'] * item['preco']
            
            cursor.execute("""
                INSERT INTO itens_venda 
                (venda_id, produto_id, lote_id, quantidade, preco_unitario, subtotal)
                VALUES (?, ?, ?, ?, ?, ?)
            """, (
                venda_id,
                item['produto_id'],
                item['lote_id'],
                item['quantidade'],
                item['preco'],
                subtotal
            ))
            
            # Baixa estoque do lote usado
            cursor.execute("""
                UPDATE estoque_lotes
                SET qtd_atual = qtd_atual - ?
                WHERE id = ?
            """, (item['quantidade'], item['lote_id']))
        
        conexao.commit()
        return venda_id
    
    except Exception as err:
        print(f"[ERRO] registrar_venda: {err}")
        conexao.rollback()
        return None
    finally:
        conexao.close()

def get_vendas_recentes(limite=10):
    """Retorna as últimas vendas realizadas"""
    conexao = get_db_connection()
    if not conexao:
        return []
    
    try:
        cursor = conexao.cursor()
        cursor.execute("""
            SELECT 
                v.id,
                v.data_venda,
                v.total,
                u.nome AS vendedor,
                u.cargo AS cargo_vendedor,
                v.supervisor_liberacao
            FROM vendas v
            INNER JOIN usuarios u ON v.usuario_id = u.id
            ORDER BY v.data_venda DESC
            LIMIT ?
        """, (limite,))
        
        return [dict_from_row(row) for row in cursor.fetchall()]
    
    except Exception as err:
        print(f"[ERRO] get_vendas_recentes: {err}")
        return []
    finally:
        conexao.close()
