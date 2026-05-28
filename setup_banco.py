"""
Script para Criar o Banco de Dados SQLite
Execute este script UMA VEZ antes de rodar o app.py
"""

import sqlite3
from werkzeug.security import generate_password_hash # Criptografar senha
from config import Config

print("="*60)
print("SETUP DO BANCO DE DADOS (SQLite)")
print("="*60)

try:
    # 1. Conectar/Criar banco SQLite
    print(f"\n1. Criando banco em: {Config.DATABASE_PATH}")
    conexao = sqlite3.connect(Config.DATABASE_PATH)
    cursor = conexao.cursor()
    print("   OK - Conectado ao SQLite!")
    
    # 2. Criar tabelas
    print("\n2. Criando tabelas...")
    
    # Tabela: usuarios
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            login TEXT UNIQUE NOT NULL,
            senha_hash TEXT NOT NULL,
            cargo TEXT CHECK(cargo IN ('Atendente', 'Farmaceutico', 'Gerente', 'Admin')) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    """)
    print("   OK - Tabela 'usuarios' criada!")
    
    # Tabela: produtos
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS produtos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            fabricante TEXT NOT NULL,
            categoria TEXT CHECK(categoria IN ('Comum', 'Controlado', 'Antibiotico', 'Higiene')) NOT NULL,
            preco_venda REAL NOT NULL,
            descricao TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    """)
    print("   OK - Tabela 'produtos' criada!")
    
    # Tabela: estoque_lotes
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS estoque_lotes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            produto_id INTEGER NOT NULL,
            numero_lote TEXT NOT NULL,
            data_validade DATE NOT NULL,
            qtd_atual INTEGER NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
        )
    """)
    print("   OK - Tabela 'estoque_lotes' criada!")
    
    # Tabela: vendas
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS vendas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            data_venda TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            total REAL NOT NULL,
            usuario_id INTEGER NOT NULL,
            supervisor_liberacao TEXT,
            caminho_receita TEXT,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )
    """)
    print("   OK - Tabela 'vendas' criada!")
    
    # Tabela: itens_venda
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS itens_venda (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            venda_id INTEGER NOT NULL,
            produto_id INTEGER NOT NULL,
            lote_id INTEGER NOT NULL,
            quantidade INTEGER NOT NULL,
            preco_unitario REAL NOT NULL,
            subtotal REAL NOT NULL,
            FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE,
            FOREIGN KEY (produto_id) REFERENCES produtos(id),
            FOREIGN KEY (lote_id) REFERENCES estoque_lotes(id)
        )
    """)
    print("   OK - Tabela 'itens_venda' criada!")
    
    # 3. Criar chamados e usuários do sistema
    print("\n3. Criando chamados e usuários do sistema...")

    # Tabela: chamados do Help Desk
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS chamados (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo VARCHAR(100) NOT NULL,
            descricao TEXT NOT NULL,
            data_aberto DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            data_fechado DATETIME DEFAULT NULL,
            prioridade TEXT CHECK(prioridade IN ('Baixa', 'Media', 'Alta')) NOT NULL,
            status TEXT CHECK(status IN ('Aberto', 'Em andamento', 'Resolvido', 'Fechado')) NOT NULL DEFAULT 'Aberto',
            aberto_por_id INTEGER NOT NULL,
            fechado_por_id INTEGER DEFAULT NULL,
            FOREIGN KEY (aberto_por_id) REFERENCES usuarios(id),
            FOREIGN KEY (fechado_por_id) REFERENCES usuarios(id)
        )
    """)
    print("   Tabela 'chamados' criada!")

    usuarios = [
        ('Fernanda Castro', 'fernanda', '12345678', 'Gerente'),
        ('Bruno Alves', 'bruno', '12345678', 'Farmaceutico'),
        ('Tânia Lima', 'tania', '12345678', 'Atendente'),
        ('Admin', 'admin', '12345678', 'Gerente'),
    ]

    for nome, login, senha, cargo in usuarios:
        cursor.execute("SELECT COUNT(*) FROM usuarios WHERE login = ?", (login,))
        existe = cursor.fetchone()[0]

        if existe > 0:
            print(f"   Aviso - Usuário '{login}' já existe. Pulando...")
            continue

        senha_hash = generate_password_hash(senha)
        cursor.execute("""
            INSERT INTO usuarios (nome, login, senha_hash, cargo)
            VALUES (?, ?, ?, ?)
        """, (nome, login, senha_hash, cargo))
        print(f"   OK - Usuário '{login}' criado!")
    
    # 4. Inserir alguns produtos de exemplo
    print("\n4. Inserindo produtos de exemplo...")
    
    cursor.execute("SELECT COUNT(*) FROM produtos")
    if cursor.fetchone()[0] == 0:
        produtos = [
            ('Dipirona 500mg', 'EMS', 'Comum', 8.50, 'Analgésico e antitérmico'),
            ('Paracetamol 750mg', 'Medley', 'Comum', 6.90, 'Analgésico'),
            ('Amoxicilina 500mg', 'Eurofarma', 'Antibiotico', 25.00, 'Antibiótico - Venda sob prescrição'),
            ('Rivotril 2mg', 'Roche', 'Controlado', 45.90, 'Medicamento controlado - Tarja preta'),
            ('Shampoo Anticaspa', 'Head & Shoulders', 'Higiene', 22.50, 'Uso capilar'),
            ('Ibuprofeno 400mg', 'Neo Química', 'Comum', 12.00, 'Anti-inflamatório'),
        ]
        
        for p in produtos:
            cursor.execute("""
                INSERT INTO produtos (nome, fabricante, categoria, preco_venda, descricao)
                VALUES (?, ?, ?, ?, ?)
            """, p)
        print(f"   OK - {len(produtos)} produtos inseridos!")
        
        # Inserir lotes para os produtos
        print("\n5. Inserindo lotes de exemplo...")
        from datetime import datetime, timedelta
        
        hoje = datetime.now()
        lotes = [
            (1, 'LOT2024001', (hoje + timedelta(days=180)).strftime('%Y-%m-%d'), 100),
            (1, 'LOT2024002', (hoje + timedelta(days=25)).strftime('%Y-%m-%d'), 50),  # Vencendo
            (2, 'LOT2024003', (hoje + timedelta(days=365)).strftime('%Y-%m-%d'), 200),
            (3, 'LOT2024004', (hoje + timedelta(days=90)).strftime('%Y-%m-%d'), 30),
            (4, 'LOT2024005', (hoje + timedelta(days=120)).strftime('%Y-%m-%d'), 15),
            (5, 'LOT2024006', (hoje + timedelta(days=400)).strftime('%Y-%m-%d'), 80),
            (6, 'LOT2024007', (hoje + timedelta(days=60)).strftime('%Y-%m-%d'), 150),
        ]
        
        for l in lotes:
            cursor.execute("""
                INSERT INTO estoque_lotes (produto_id, numero_lote, data_validade, qtd_atual)
                VALUES (?, ?, ?, ?)
            """, l)
        print(f"   OK - {len(lotes)} lotes inseridos!")
    else:
        print("   Aviso - Produtos já existem. Pulando...")
    
    # 5. Commit
    conexao.commit()
    
    # 6. Verificação final
    print("\n6. Verificação final...")
    cursor.execute("SELECT COUNT(*) FROM usuarios")
    print(f"   OK - {cursor.fetchone()[0]} usuário(s) cadastrado(s)")
    
    cursor.execute("SELECT COUNT(*) FROM produtos")
    print(f"   OK - {cursor.fetchone()[0]} produto(s) cadastrado(s)")
    
    cursor.execute("SELECT COUNT(*) FROM estoque_lotes")
    print(f"   OK - {cursor.fetchone()[0]} lote(s) cadastrado(s)")
    
    cursor.execute("SELECT COUNT(*) FROM chamados")
    print(f"   {cursor.fetchone()[0]} chamado(s) cadastrado(s)")

    cursor.close()
    conexao.close()
    
    print("\n" + "="*60)
    print("SETUP CONCLUÍDO!")
    print("="*60)
    print("\n Próximos passos:")
    print("   1. Execute: python app.py")
    print("   2. Acesse: http://localhost:5000")
    print("\n" + "="*60)

except Exception as e:
    print(f"\nERRO: {e}")
    import traceback
    traceback.print_exc()
