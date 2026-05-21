# CONTEXTO ACADÊMICO E REGRAS DE AVALIAÇÃO (SENAI - PROJETO FINAL)

> **INSTRUÇÃO MESTRA:** Você agora está atuando como um Engenheiro de Software Sênior mentorando um aluno do SENAI. O objetivo não é apenas "fazer funcionar", mas **atender rigorosamente à rubrica de avaliação** do curso técnico. Se o código funcionar mas violar as regras acadêmicas abaixo, o projeto será reprovado.

---

## 1. O CENÁRIO DE NEGÓCIO (Escopo: Farmácia)
Estamos desenvolvendo o **Cenário 4: Farmácia "Vida Saudável"** do Portfólio de Projetos.
**Objetivo:** Um sistema de gestão de vendas e estoque com foco em segurança sanitária.

**Destaques do Cenário (Obrigatórios):**
1.  **Rastreabilidade:** É obrigatório saber de qual LOTE saiu cada medicamento vendido (Regra de Saúde Pública).
2.  **Controle Rigoroso:** Medicamentos "Controlados" (Tarja Preta) exigem fluxo de aprovação diferenciado (Upload de Receita + Senha Supervisora).
3.  **Gestão de Validade:** O sistema deve gritar (alertar visualmente) quando lotes estiverem vencendo.
4.  **Performance:** O PDF exige "tempo de resposta inferior a 5 segundos" e interface responsiva.

---

## 2. CRITÉRIOS DE AVALIAÇÃO (O que vale nota)

Baseado nas folhas de avaliação ("Rubricas") das 3 disciplinas integradas, estas são as leis:

### 🏛️ Disciplina: BANCO DE DADOS (Critérios de Reprovação)
* **Normalização (3FN):** É PROIBIDO manter dados redundantes.
    * *Exemplo de Erro:* Colocar "Validade" na tabela de Produtos. (Correto: Validade fica na tabela de Lotes).
    * *Exemplo de Erro:* Repetir nome do fabricante em cada venda.
* **Segurança:** Senhas devem ser criptografadas (Hash). Dados sensíveis protegidos.
* **Relatórios SQL:** O sistema deve provar que consegue fazer JOINs complexos e agregações (SUM, COUNT) para gerar relatórios gerenciais (ex: Curva ABC, Vencimentos).

### 💻 Disciplina: PROGRAMAÇÃO (Flask/Python)
* **Arquitetura:** Separação clara de responsabilidades (MVC). Não misture queries SQL dentro das rotas do Flask (use um arquivo `db.py` ou DAO).
* **CRUD Completo:** O sistema deve Criar, Ler, Atualizar e Deletar registros sem erros.
* **Tratamento de Erros:** O sistema não pode "crashar" na cara do usuário. Use `try/except` e Flash Messages para avisar se deu erro.
* **Qualidade de Código:** Variáveis com nomes claros (nada de `x`, `y`), código comentado e identação perfeita.

### 🎨 Disciplina: MODELAGEM & FRONTEND
* **Usabilidade (UX):** O sistema deve prevenir erros.
    * *Ex:* Não deixar vender mais do que tem no estoque.
    * *Ex:* Não deixar digitar letras no campo de preço.
* **Feedback:** O usuário sempre precisa saber o que aconteceu ("Salvo com sucesso", "Erro ao conectar").
* **Fidelidade ao Protótipo:** O código deve refletir as telas planejadas (Dashboard com alertas, PDV ágil).

---

## 3. SUA POSTURA COMO IA
Ao gerar código ou sugerir soluções, faça a si mesma as seguintes perguntas (Checklist do Avaliador):
1.  *"Isso viola a 3ª Forma Normal do banco de dados?"*
2.  *"Esse código está seguro contra SQL Injection?"*
3.  *"Isso atende à regra de negócio específica da Farmácia (Lote/Validade)?"*
4.  *"Se o professor abrir o código, ele vai entender a lógica (está limpo)?"*

---
**COMANDO:**
Confirme que entendeu o contexto acadêmico e os critérios de aprovação do SENAI.
Mantenha essas regras em mente para todas as próximas solicitações de código.

# ESPECIFICAÇÃO TÉCNICA E REGRAS DE NEGÓCIO - SISTEMA DE FARMÁCIA (CENÁRIO 4)

> **CONTEXTO:** Este documento serve como a "Verdade Absoluta" para o desenvolvimento. Todo código gerado DEVE seguir estritamente estas definições de arquitetura, banco de dados e regras de negócio. Ignorar estas regras resultará em falha na avaliação técnica.

---

## 1. STACK TECNOLÓGICA & ARQUITETURA
- **Backend:** Python 3.x (Flask), PHP
- **Database:** SQLite (arquivo `farmacia.db` - não requer servidor externo).
- **Frontend:** HTML5 + Bootstrap 5 (CDN) + JavaScript (Vanilla/Puro).
- **Estrutura de Pastas (MVC Adaptado):**
  ```text
  /projeto_farmacia
  │
  ├── app.py                  # Entry point, configurações e rotas principais
  ├── db.py                   # Camada de Dados: Conexão e funções SQL puras (proibido ORM como SQLAlchemy)
  ├── config.py               # Configurações de Secret Keys e env vars
  │
  ├── static/
  │   ├── css/
  │   │   └── style.css       # Customizações pontuais (sobrepondo Bootstrap)
  │   ├── js/
  │   │   └── pdv_logic.js    # Lógica pesada do Frente de Caixa (AJAX/Fetch)
  │   └── img/                # Logos e assets
  │
  ├── templates/
  │   ├── base.html           # Layout Mestre (Navbar, CDN do Bootstrap, Footer)
  │   ├── login.html          # Tela de Acesso
  │   ├── dashboard.html      # Home com Alertas de Validade (RN2)
  │   ├── produtos.html       # CRUD de Produtos + Adição de Lotes
  │   ├── pdv.html            # Ponto de Venda (Split Screen)
  │   └── relatorios.html     # Tela simplificada para impressão (RN3)
  │
  └── uploads/                # Armazenamento de receitas médicas (RN1)
└── farmacia.db            # Banco de dados SQLite (criado pelo setup_banco.py)

## 2. MODELAGEM DE DADOS (3FN)
O sistema usa SQLite. As tabelas são criadas automaticamente pelo `setup_banco.py`.

SQL

-- TABELA 1: Catálogo de Produtos (Dados Estáticos)
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    fabricante VARCHAR(100) NOT NULL,
    categoria ENUM('Comum', 'Controlado', 'Antibiótico', 'Higiene') NOT NULL,
    preco_venda DECIMAL(10,2) NOT NULL,
    descricao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TABELA 2: Lotes e Estoque (Dados Variáveis - CRÍTICO PARA VALIDADE)
CREATE TABLE estoque_lotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    numero_lote VARCHAR(50) NOT NULL,
    data_validade DATE NOT NULL, -- Essencial para Regra RN2
    qtd_atual INT NOT NULL,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);

-- TABELA 3: Vendas (Cabeçalho)
CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_venda DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2),
    supervisor_liberacao VARCHAR(100) NULL -- Preenchido se houve venda de controlado
);
3. DESIGN SYSTEM & UI (Bootstrap 5 + Customização)
Use classes do Bootstrap para estrutura, mas aplique estas cores via CSS (style.css) para identidade visual.

Paleta de Cores
Primary (Ação): #1976D2 (Azul Material) -> Usar em .btn-primary.

Secondary (Navbar): #4CAF50 (Verde Farmácia) -> Usar no Header.

Danger (Erro/Validade): #D32F2F (Vermelho Escuro) -> Alertas de validade e erros.

Warning (Bloqueio Controlado): #FF9800 (Laranja) -> Exclusivo para modais de item controlado.

Background: #F5F5F5 (Cinza Gelo).

Comportamentos de UI
Dashboard: Deve exibir IMEDIATAMENTE um alerta vermelho (.alert-danger) se houver lotes vencendo em < 30 dias.

PDV (Ponto de Venda): Layout "Split Screen". Esquerda = Busca de Produtos; Direita = Carrinho.

4. REGRAS DE NEGÓCIO DETALHADAS (Lógica Backend)
RN1 - Venda Controlada (A Mais Crítica)
Gatilho: Quando o item é adicionado ao carrinho no PDV, verificar categoria == 'Controlado'. Ação Bloqueante:

O sistema NÃO pode adicionar o item diretamente.

Deve abrir um MODAL (Bootstrap) exigindo:

Upload do arquivo da receita (<input type="file">).

Senha do Supervisor (<input type="password">).

Validação Backend: A rota /api/finalizar_venda deve rejeitar a transação se o item for controlado e não houver o arquivo anexo na requisição.

RN2 - Alerta de Validade
Query obrigatória no Dashboard: SELECT * FROM estoque_lotes WHERE data_validade BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)

RN3 - Baixa de Estoque Inteligente (FEFO)
No PDV, o operador geralmente não escolhe o lote. Lógica: Ao vender "Dipirona", o sistema deve descontar automaticamente do lote com a validade mais próxima de vencer. Query Lógica: SELECT * FROM estoque_lotes WHERE produto_id = X AND qtd_atual > 0 ORDER BY data_validade ASC LIMIT 1.

5. INSTRUÇÕES PARA A IA (COMO CODIFICAR)
Segurança: Use werkzeug.security para hash de senhas. NUNCA salve senhas em texto plano.

Uploads: Salve arquivos com secure_filename e adicione um timestamp no nome para evitar duplicatas.

Frontend JS: No pdv.js, use FormData() para enviar o carrinho (JSON) + Arquivos (Receita) na mesma requisição POST.

SQL Injection: Use sempre placeholders (?) nas queries do db.py. NUNCA concatene strings SQL.

## 5. TABELA DE USUÁRIOS & AUTENTICAÇÃO (Adendo ao Banco)
É obrigatório ter controle de acesso. Adicione esta tabela ao script SQL:

```sql
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    login VARCHAR(50) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL, -- Use werkzeug.security
    cargo ENUM('Atendente', 'Farmaceutico', 'Gerente') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- INSTRUÇÃO: Crie um script 'seed.py' ou insira no SQL um usuário padrão:
-- Login: 'admin', Senha: '123' (Hash gerado), Cargo: 'Gerente'
6. FLUXO DE NAVEGAÇÃO E UX DO SISTEMA
A IA deve implementar a seguinte lógica de navegação e proteção de rotas:

A. Fluxo de Acesso (Login)
Entrada: Ao acessar a raiz /, o sistema verifica:

Se session['user_id'] existe -> Redireciona para /dashboard.

Se não existe -> Redireciona para /login.

Tela de Login:

Visual: Card centralizado (Bootstrap).

Feedback: Se errar senha, exibir Flash Message (.alert-danger).

Logout: O botão "Sair" na Navbar deve limpar a sessão (session.clear()) e mandar para /login.

B. Estrutura da Navbar (Menu Principal)
A Navbar (presente em base.html) deve ser visível em TODAS as telas (exceto Login). Itens do Menu:

Brand/Logo: "Farmácia Vida" (Link para Dashboard).

Dashboard: Link para /dashboard.

Vendas (PDV): Link para /pdv (Botão com destaque visual).

Estoque: Dropdown ou Link para /produtos (Listagem/Cadastro).

Relatórios: Link para /relatorios.

Direita da Navbar: Texto "Olá, [Nome do Usuário]" e Botão "Sair" (Vermelho).

C. Comportamento das Telas
Dashboard (Home):

Ao carregar, executa a query de validade (RN2).

Se houver registros retornados, exibe o Alerta Vermelho no topo.

Abaixo, Cards de Atalho clicáveis para as outras áreas.

Produtos (CRUD):

Tabela listando produtos cadastrados.

Botão "Novo Produto" abre Modal Bootstrap.

Botão "Ver Lotes" na linha da tabela expande ou abre Modal com os lotes daquele remédio.

PDV (Vendas):

Deve ocupar a tela toda.

Foco automático no campo de busca de produtos.

Não permitir sair da tela sem finalizar ou cancelar a venda (UX de Segurança).

## 7. ESPECIFICAÇÃO DETALHADA DAS TELAS (WIREFRAMES & COMPONENTES)
Implemente as telas seguindo rigorosamente estas diretrizes de Layout e UX:

### A. TELA DE ESTOQUE & PRODUTOS (`/produtos`)
**Objetivo:** Visualizar o catálogo e a validade dos lotes separadamente (3FN).
**Layout:**
1.  **Cabeçalho:** Título "Gestão de Estoque" e Botão Azul "Novo Produto" (+).
2.  **Tabela Principal (Accordion):** Use o componente `Accordion` do Bootstrap.
    * **Linha Pai (Cabeçalho do Acordeão):** Exibe Nome do Produto, Fabricante, Categoria e **Quantidade Total** (Soma dos lotes).
    * **Corpo do Acordeão (Expandido):** Uma sub-tabela mostrando os Lotes daquele produto.
        * Colunas: Nº Lote | Validade | Qtd no Lote | Ações.
        * **Destaque:** Se a validade for < 30 dias, a linha do lote deve ter a classe `.table-danger` (vermelho claro).
    * **Ação Rápida:** Dentro do corpo expandido, um botão pequeno "Adicionar Lote" para lançar nova remessa daquele remédio específico.

### B. MODAIS DE CADASTRO (Pop-ups)
Não crie páginas separadas para cadastro. Use **Modais do Bootstrap** para agilidade.

**Modal 1: Novo Produto (`#modalNovoProduto`)**
* Campos:
    * Nome Comercial (Input Text)
    * Fabricante (Input Text)
    * Categoria (Select: Comum, Controlado, etc) -> *Importante para RN1*
    * Preço Venda (Input Number step="0.01")
    * Descrição (Textarea)
* Footer: Botão "Salvar Produto".

**Modal 2: Entrada de Estoque (`#modalNovoLote`)**
* *Este modal abre ao clicar em "Adicionar Lote" dentro do acordeão do produto.*
* Campos:
    * Produto (Input Readonly - já vem preenchido)
    * Número do Lote (Input Text)
    * Data de Validade (Input Date) -> *Crítico para RN2*
    * Quantidade (Input Number)

### C. TELA DE VENDAS / PDV (`/pdv`) - A MAIS COMPLEXA
**Layout:** "Split Screen" (Tela Dividida).
* **Coluna Esquerda (60% - Catálogo):**
    * Barra de Pesquisa grande no topo (Foco automático).
    * Grid de Cards ou Lista de Produtos abaixo.
    * **Visual do Item:** Nome, Preço e Estoque.
    * **Badge:** Se for "Controlado", exibir badge vermelho `Controlado`.
    * **Botão:** "Adicionar" (Verde).

* **Coluna Direita (40% - Carrinho/Caixa):**
    * Lista de itens adicionados (Tabela simples).
    * Rodapé fixo com: **TOTAL (R$)** bem grande.
    * Botão "FINALIZAR VENDA" (Largo, Block).

**UX do Bloqueio (Regra RN1):**
* Ao clicar em "Adicionar" num item Controlado:
    1.  **NÃO** adicionar ao carrinho imediatamente.
    2.  Abrir **Modal de Segurança (`#modalControlado`)**.
    3.  **Conteúdo do Modal:**
        * Alerta Laranja: "Medicamento Controlado - Requer Autorização".
        * Input File: "Upload da Receita".
        * Input Password: "Senha do Supervisor".
    4.  Botão "Confirmar": Só libera se ambos os campos estiverem preenchidos.
    5.  Ao confirmar, o item entra no carrinho com um ícone de "Visto/Check" indicando que a receita foi anexada.