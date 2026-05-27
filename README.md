# Sistema de Farmácia "Vida Saudável" - SENAI

Sistema de gerenciamento de farmácia desenvolvido em Python/Flask e PHP com SQLite.

## Funcionalidades

- **Autenticação**: Login com dropdown de usuários e hash de senhas.
- **Dashboard**: Visão geral com alertas de validade e últimas vendas.
- **PDV (Ponto de Venda)**:
  - Busca rápida de produtos.
  - Carrinho de compras.
  - **RN1**: Controle de medicamentos controlados (receita + senha supervisor).
  - **RN3**: Baixa de estoque via FEFO (First Expire, First Out).
- **Gestão de Estoque**:
  - CRUD completo de produtos.
  - Cadastro de lotes com controle de validade.
  - **RN2**: Alertas automáticos para lotes vencendo em 30 dias.
- **Relatórios**: Histórico de vendas e lotes críticos.
- **Help Desk**
  - Abrir chamados sobre problemas/sugestões

## Tecnologias

- **Backend**: Python 3.x, Flask, PHP
- **Banco de Dados**: SQLite (arquivo `farmacia.db`)
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript

## Instalação

### 1. Instalar dependências
```bash
pip install -r requirements.txt
```

### 2. Criar banco de dados
```bash
python setup_banco.py
```

### 3. Executar o sistema
```bash
python app.py
```

Acesse: **http://localhost:5000**

## Credenciais

### Usuários do Sistema
| Login | Nome | Cargo | Senha |
|-------|------|-------|-------|
| fernanda | Fernanda Castro | Gerente | 12345678 |
| bruno | Bruno Alves | Farmacêutico | 12345678 |
| tania | Tânia Lima | Atendente | 12345678 |
| admin | Admin | Gerente | 12345678 |

### Senha do Supervisor (Medicamentos Controlados)
- **Senha Mestra**: `farmacia_VS`

---
Desenvolvido para avaliação acadêmica - SENAI 2026.
