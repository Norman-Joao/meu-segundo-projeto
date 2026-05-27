<picture>
  <source media="(prefers-color-scheme: dark)" srcset="">
  <img alt="Hospital do Ramiros" src="https://img.shields.io/badge/Hospital%20do%20Ramiros-Sistema%20de%20Gest%C3%A3o-0ea5e9?style=for-the-badge&logo=hospital&labelColor=0f172a">
</picture>

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8%2B-777BB4?style=flat-square&logo=php&logoColor=white">
  <img alt="MySQL" src="https://img.shields.io/badge/MySQL-8-4479A1?style=flat-square&logo=mysql&logoColor=white">
  <img alt="JavaScript" src="https://img.shields.io/badge/JS-ES6-F7DF1E?style=flat-square&logo=javascript&logoColor=black">
  <img alt="Chart.js" src="https://img.shields.io/badge/Chart.js-4-FF6384?style=flat-square&logo=chartdotjs&logoColor=white">
  <img alt="Responsivo" src="https://img.shields.io/badge/Responsivo-Sim-27ae60?style=flat-square">
  <img alt="Licença" src="https://img.shields.io/badge/Licença-MIT-3da639?style=flat-square">
</p>

## Sobre o Projecto

O **Hospital do Ramiros — Sistema de Gestão Hospitalar** é uma plataforma web desenvolvida para gerir o fluxo de urgência de um hospital em Luanda, Angola. O sistema cobre todo o ciclo de atendimento: desde o **registo do paciente** na entrada, passando pela **triagem** com classificação de prioridade (Protocolo de Manchester), **atendimento médico** com registo clínico, **gestão de leitos** com mapa de ocupação visual, até à geração de **relatórios gerenciais** com gráficos e exportação de dados.

Construído com tecnologias web padrão (PHP, MySQL, JavaScript vanilla e CSS3), o sistema funciona sem dependências externas complexas — basta um servidor Apache com PHP 8+ e MySQL. O frontend é responsivo e utiliza modais para formulários, Chart.js para gráficos interactivos e um sistema de notificações em tempo real com actualização automática a cada 30 segundos.

Entre as suas principais características estão a validação inteligente do BI angolano (formato `000000000AA000`), formatação automática de telefone no padrão `+244`, e suporte a convénios de saúde angolanos (ENSA, Global Saúde, AAA Seguros, entre outros). A segurança é garantida por prepared statements, tokens CSRF, rate limiting no login, sessões com timeout e hash bcrypt para senhas.

---

## 📋 Funcionalidades

<table>
<tr>
<td width="50%">

**👥 Gestão de Pacientes**
- Cadastro completo (BI angolano, telefone +244)
- Busca por nome, BI ou convênio
- Edição, exclusão e visualização de detalhes
- Formulário em modal com validação em tempo real

**🏥 Triagem & Fila de Espera**
- Protocolo de Manchester (cálculo automático)
- Sinais vitais: FC, temperatura, SatO2, FR, glicemia, dor
- Fila ordenada por prioridade + tempo de espera
- Reclassificação de prioridade

**🩺 Atendimento Médico**
- Diagnóstico, CID, prescrição e exames
- Histórico completo por paciente
- Sinais vitais durante o atendimento
- Integração com triagem e fila
</td>
<td width="50%">

**🛏️ Gestão de Leitos**
- Mapa visual de ocupação (alas A e B)
- Cadastro de novos leitos
- Ocupar, liberar e manutenção
- KPIs em tempo real (disponíveis, ocupados, manutenção)

**📊 Dashboard & Relatórios**
- Gráficos interativos (Chart.js 4)
- Atendimentos por prioridade, fluxo horário, convénios
- Exportação CSV, download HTML, impressão
- Filtro por período

**🔔 Notificações**
- Sino no cabeçalho com badge numérico
- Dropdown com fila de espera + alerta de leitos
- Atualização automática a cada 30s
- Presente em todas as páginas

**🔒 Segurança**
- Autenticação por sessão (30min timeout)
- CSRF token em todas as requisições
- Rate limiting no login (5 tentativas/15min)
- Prepared Statements + XSS protection
</td>
</tr>
</table>

---

## 🚀 Instalação

<table>
<tr>
<td>

### ⚡ XAMPP / WAMP / LAMP

1. Copie a pasta `hospital-do-ramiros` para `htdocs`
2. Importe `database.sql` pelo phpMyAdmin
3. Acesse [`http://localhost/hospital-do-ramiros`](http://localhost)

</td>
<td>

### 🖥️ Terminal (MySQL)

```bash
mysql -u root -p < database.sql
```

</td>
</tr>
</table>

### 👤 Credenciais de Acesso

```
Usuário: admin
Senha:   admin
```

---

## 🛠️ Stack Tecnológica

| Camada | Tecnologias |
|--------|-------------|
| **Frontend** | HTML5, CSS3 (Flexbox/Grid), JavaScript (Fetch API), Chart.js 4 |
| **Backend** | PHP 8+ com PDO (Prepared Statements) |
| **Banco** | MySQL 8 |
| **Design** | Responsivo (mobile first), tema médico, Font Awesome 6, Google Fonts (Inter) |

---

## 📁 Estrutura do Projeto

```
hospital-do-ramiros/
│
├── 🌐 Páginas Públicas
│   ├── index.html              # Landing page institucional
│   └── login.html               # Tela de login
│
├── 🔒 Páginas Protegidas (auth)
│   ├── dashboard.php            # Dashboard com gráficos
│   ├── pacientes.php            # CRUD de pacientes
│   ├── triagem.php              # Triagem e fila de espera
│   ├── atendimento.php          # Atendimento médico
│   ├── leitos.php               # Gerenciamento de leitos
│   └── relatorios.php           # Relatórios gerenciais
│
├── 🎨 Estilos
│   └── css/style.css            ~3300 linhas — sistema completo de estilos
│
├── ⚙️ JavaScript
│   ├── js/main.js               # Globais: notificações, BI, telefone, CSRF
│   ├── js/pacientes.js          # CRUD pacientes com modal
│   ├── js/triagem.js            # Triagem + cálculo de prioridade
│   ├── js/atendimento.js        # Atendimento + histórico
│   ├── js/leitos.js             # Gestão de leitos
│   ├── js/dashboard.js          # Dashboard + gráficos
│   └── js/relatorio.js          # Relatórios + exportação
│
├── 🔌 API REST
│   ├── api/config.php           # Conexão BD, validação BI, helpers
│   ├── api/login.php            # Autenticação + rate limit
│   ├── api/logout.php           # Logout
│   ├── api/check_session.php    # Verificação de sessão + CSRF
│   ├── api/pacientes.php        # CRUD pacientes
│   ├── api/triagem.php          # Triagem e fila
│   ├── api/atendimentos.php     # Atendimentos
│   ├── api/leitos.php           # Leitos
│   ├── api/dashboard.php        # Estatísticas do dashboard
│   ├── api/notificacoes.php     # Endpoint de notificações
│   ├── api/relatorios.php       # Relatórios e exportação
│   └── api/usuarios.php         # Gestão de usuários
│
├── 🗄️ Banco de Dados
│   └── database.sql             # Script completo (6 tabelas)
│
└── 📄 Documentação
    └── README.md                # Este arquivo
```

---

## 🇦🇴 Adaptações Angola

| Característica | Descrição |
|----------------|-----------|
| **BI** | Formato `000000000AA000` (9 dígitos + 2 letras + 3 dígitos) com validação inteligente |
| **Telefone** | Formato `+244 XXX XXX XXX` |
| **Convénios** | ENSA, Global Saúde, AAA Seguros, Angola Seguros, Nossa Seguros, Sagal Seguros |
| **Código Postal** | Incluído no cadastro de pacientes |
| **Terminologia** | BI (Bilhete de Identidade), Sala de Triagem, Pronto-Socorro |

---

## 🧪 Dados de Teste

```text
BI Válidos:  123456789AA001 · 987654321BB002 · 111222333CC003 · 444555666DD004 · 777888999EE005
```

| Nome | BI | Convénio |
|------|-----|----------|
| João Silva Santos | 123456789AA001 | ENSA |
| Maria Oliveira Souza | 987654321BB002 | Particular |
| Pedro Costa Lima | 111222333CC003 | Global Saúde |
| Ana Paula Santos | 444555666DD004 | AAA Seguros |
| Carlos Eduardo Rocha | 777888999EE005 | Angola Seguros |

---

## 🔒 Segurança

| Medida | Implementação |
|--------|---------------|
| **SQL Injection** | Prepared Statements em todas as queries |
| **XSS** | `escapeHtml()` + sanitização de inputs |
| **CSRF** | Token de 32 bits validado em toda requisição POST/PUT/DELETE |
| **Rate Limiting** | Máx. 5 tentativas de login por IP a cada 15 minutos |
| **Sessão** | Timeout de 30 minutos + `session_regenerate_id()` no login |
| **Senhas** | Hash bcrypt |
| **Autenticação** | Páginas protegidas com `verificarAuthPagina()` + APIs com `verificarAuth()` |
| **Auditoria** | Log de todas as ações críticas (`audit_log`) |

---

<p align="center">
  <sub>Desenvolvido para o Hospital do Ramiros — Luanda, Angola 🇦🇴</sub>
  <br>
  <sub>Sistema de Gestão Hospitalar · Pronto-Socorro 24h</sub>
</p>
