# IA_README

Esse arquivo e para qualquer IA entender meu projeto antes de sair alterando codigo.

Por favor, leia isso antes de mexer em qualquer coisa. Eu quero manter o projeto organizado e nao quero que a IA tome decisoes grandes sem me perguntar.

Estou deixando este arquivo em ASCII de proposito para evitar caracteres quebrados no terminal do Windows.

---

## Contexto do projeto

Esse projeto comecou como minha S.A. do segundo semestre no SENAI.

Era um sistema de farmacia chamado **Vida Saudavel**, feito principalmente em Flask/Python com banco SQLite.

Agora estou no terceiro semestre e o projeto desse semestre vai ser uma extensao da S.A. anterior. A extensao que escolhi fazer e um **Help Desk**.

A ideia e que o sistema continue sendo o sistema da farmacia, mas agora com uma area para chamados internos, problemas, solicitacoes e acompanhamento.

O Help Desk devera ser feito em **PHP**, porque faz parte da proposta/necessidade do semestre atual.

---

## Como o projeto esta dividido

O sistema principal continua em Flask.

Arquivos importantes:

- `app.py`: arquivo principal do Flask. Tem as rotas de login, dashboard, produtos, PDV, vendas e relatorios.
- `db.py`: arquivo de acesso ao banco usado pelo Flask.
- `config.py`: configuracoes gerais, caminho do banco e senha mestra do supervisor.
- `setup_banco.py`: cria as tabelas e popula dados iniciais.
- `farmacia.db`: banco SQLite do projeto.
- `templates/`: telas HTML usadas pelo Flask.
- `static/css/style.css`: CSS principal do sistema.
- `static/js/pdv_logic.js`: JavaScript do PDV.
- `php/conexao.php`: conexao PHP com o mesmo banco `farmacia.db`.
- `php/help_desk/`: pasta criada para o modulo Help Desk em PHP.

O Flask e o PHP devem conviver no mesmo projeto, mas um nao executa o outro automaticamente.

O Flask roda o sistema principal. O PHP roda o Help Desk.

---

## Integracao Flask + PHP

Importante: Flask nao executa PHP.

A integracao sera feita de tres formas:

1. Os dois usam o mesmo banco `farmacia.db`.
2. Os dois devem usar o mesmo visual, reaproveitando `static/css/style.css`.
3. A navbar do Flask tera um botao para abrir o Help Desk.

Exemplo de como pode rodar:

```text
Flask:
http://localhost:5000

PHP:
http://localhost:8000/php/help_desk/
```

Para testar PHP, posso usar:

```bash
php -S localhost:8000 -t .
```

Atencao: atualmente existe a pasta `php/help_desk`, mas o link criado na navbar aponta para `/php/helpdesk/index.php`. Antes de criar as telas, preciso padronizar isso. Nao decida sozinho sem me perguntar.

---

## Sobre permissoes e modo de trabalho

Antes de implementar algo novo, pergunte.

Eu posso autorizar uma tarefa especifica. Quando eu autorizar, faca apenas aquilo.

Nao refatore o projeto inteiro sem necessidade.

Nao crie pastas duplicadas se ja existir uma estrutura parecida.

Nao mexa no banco `farmacia.db` sem avisar, porque ele e o banco real do projeto.

Se encontrar alguma inconsistencia, me explique primeiro e sugira caminhos.

---

## Usuarios atuais

O login do Flask usa a tabela `usuarios`.

Usuarios esperados:

| Login | Nome | Cargo | Senha |
| --- | --- | --- | --- |
| admin | Admin | Gerente | 12345678 |
| fernanda | Fernanda Castro | Gerente | 12345678 |
| bruno | Bruno Alves | Farmaceutico | 12345678 |
| tania | Tania Lima | Atendente | 12345678 |

O usuario `admin` foi adicionado para aparecer tambem na tela inicial de login.

No banco atual, os cargos aceitos sao:

- `Atendente`
- `Farmaceutico`
- `Gerente`

Por isso o `admin` esta como `Gerente`, e nao como cargo `Admin`.

As senhas devem continuar usando hash. Nao salvar senha pura no banco.

---

## Design do projeto

O projeto ja tem uma identidade visual.

Use o design existente.

Caracteristicas:

- navbar verde;
- fundo cinza claro;
- cards brancos;
- Bootstrap 5;
- Bootstrap Icons;
- azul para acoes principais;
- verde para acoes positivas;
- vermelho para erro/perigo;
- amarelo ou laranja para alerta.

Cores principais no `static/css/style.css`:

```css
--primary-color: #1976D2;
--secondary-color: #4CAF50;
--danger-color: #D32F2F;
--warning-color: #FF9800;
--background-color: #F5F5F5;
```

Nao criar uma identidade visual nova para o Help Desk. Ele deve parecer um modulo do mesmo sistema.

Tambem nao criar uma pasta `assets` dentro de `php` se nao for necessario. A ideia e reutilizar:

```text
static/css/style.css
static/js/
```

Se precisar de JavaScript especifico para o Help Desk, pode sugerir criar:

```text
static/js/helpdesk.js
```

Mas pergunte antes.

---

## Botao Help Desk na navbar

Eu pedi para colocar o botao do Help Desk na navbar, no espaco entre `Relatorios` e o bloco do usuario logado.

Isso ja foi feito em:

```text
templates/base.html
```

O item usa o icone:

```html
<i class="bi bi-headset"></i> Help Desk
```

Ainda precisamos ajustar o link quando a pasta/pagina PHP final estiver definida.

---

## Regras importantes do sistema de farmacia

Mesmo com o Help Desk, o sistema antigo continua tendo regras importantes.

### RN1 - Medicamento controlado

Produto da categoria `Controlado` precisa de:

- upload de receita;
- senha do supervisor;
- validacao no backend.

A senha mestra fica em `config.py`:

```python
SENHA_SUPERVISOR_MESTRA = 'farmacia_VS'
```

### RN2 - Alerta de validade

O dashboard alerta lotes que vencem nos proximos 30 dias.

### RN3 - Baixa por FEFO

O PDV deve baixar primeiro o lote que vence antes.

FEFO significa First Expire, First Out.

Detalhe: a implementacao atual busca um lote que tenha a quantidade inteira. Se precisar dividir a venda entre varios lotes, isso ainda pode ser melhorado.

### RN4 - Desconto por validade proxima

O `db.py` aplica desconto automatico para produtos que estao perto do vencimento.

---

## Help Desk

O Help Desk vai ser a extensao do terceiro semestre.

Ele deve ser feito em PHP e usar o mesmo banco SQLite do projeto.

Eu desenhei a modelagem assim:

```text
usuarios 1 ---- N chamados
```

Ou seja, um usuario pode abrir varios chamados, e cada chamado pertence a um usuario.

Quero manter a tabela `chamados` do jeito que planejei por enquanto.

Modelo planejado:

```sql
CREATE TABLE chamados (
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
);
```

Nao adicione campos como `categoria`, `comentarios_chamado`, `anexos` ou historico sem me perguntar antes.

Talvez isso seja feito depois, mas agora eu quero seguir a modelagem inicial.

---

## Ideia de telas do Help Desk

Ainda nao implemente sem me perguntar.

Possiveis telas:

- tela inicial/listagem de chamados;
- tela para abrir novo chamado;
- tela de detalhes do chamado;
- acao para atualizar status;
- acao para fechar chamado.

Campos importantes:

- titulo;
- descricao;
- prioridade;
- status;
- quem abriu;
- quem fechou;
- data de abertura;
- data de fechamento.

Visual esperado:

- tabela para listar chamados;
- badges para prioridade;
- badges para status;
- botoes Bootstrap;
- mesmo CSS do resto do sistema.

Sugestao de cores:

Status:

- `Aberto`: azul;
- `Em andamento`: amarelo;
- `Resolvido`: verde;
- `Fechado`: cinza.

Prioridade:

- `Baixa`: verde;
- `Media`: amarelo;
- `Alta`: vermelho.

---

## Sobre a conexao PHP

Foi criado o arquivo:

```text
php/conexao.php
```

Ele foi escrito no estilo da aula, com a variavel `$pdo` direto.

Ele nao usa MySQL. Ele usa SQLite.

Por isso nao tem:

- host;
- usuario;
- senha;
- porta.

No SQLite, o banco e um arquivo:

```text
farmacia.db
```

Uso esperado nas paginas PHP:

```php
require_once __DIR__ . "/../conexao.php";

$stmt = $pdo->query("SELECT * FROM usuarios");
$usuarios = $stmt->fetchAll();
```

Atencao: no ambiente atual, o PHP tem `PDO`, mas nao tem `pdo_sqlite` habilitado. Para a conexao funcionar de verdade, preciso habilitar essa extensao.

---

## Pendencias

Coisas que ainda precisam ser resolvidas:

- padronizar `php/help_desk` vs `php/helpdesk`;
- ajustar o link da navbar depois dessa decisao;
- habilitar `pdo_sqlite` no PHP;
- criar a tabela `chamados`;
- decidir se a tabela sera criada pelo `setup_banco.py` ou por um script PHP;
- decidir como o PHP vai identificar o usuario logado;
- criar as telas PHP do Help Desk;
- revisar textos antigos com encoding quebrado.

---

## Como eu explicaria o projeto

Este projeto comecou como uma S.A. de farmacia no segundo semestre, feita em Flask com SQLite. Ele controla usuarios, produtos, lotes, validade, vendas e regras para medicamentos controlados.

No terceiro semestre, estou estendendo esse mesmo sistema com um modulo de Help Desk em PHP. O objetivo e mostrar continuidade do projeto, reaproveitando o banco, o visual e a estrutura ja existente, mas adicionando uma nova funcionalidade.

