<?php require_once __DIR__ . "/api/config.php"; verificarAuthPagina(); ?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Hospital do Ramiros</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* Estilos específicos para relatórios */
        .filters-card {
            background: white;
            border-radius: 16px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .filters-header {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .filters-body {
            padding: 20px;
        }

        .filter-group {
            margin-bottom: 15px;
        }

        .filter-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 10px;
            color: #666;
        }

        .period-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .period-btn {
            padding: 8px 16px;
            background: #f0f2f5;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .period-btn.active {
            background: linear-gradient(135deg, #0056b3, #00b4d8);
            color: white;
        }

        .date-range-inputs {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .date-range-inputs input {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }

        .btn-apply {
            padding: 8px 20px;
            background: #0056b3;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .summary-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #0056b3, #00b4d8);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .summary-icon i {
            font-size: 24px;
            color: white;
        }

        .summary-info {
            flex: 1;
        }

        .summary-value {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .summary-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .summary-trend {
            font-size: 11px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .trend-up { color: #27ae60; }
        .trend-down { color: #e74c3c; }

        .charts-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 25px;
        }

        .chart-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .chart-header {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chart-header h3 {
            font-size: 16px;
            margin-bottom: 4px;
        }

        .chart-header p {
            font-size: 12px;
            color: #999;
        }

        .chart-body {
            padding: 20px;
            height: 300px;
            position: relative;
        }

        .export-chart-btn {
            width: 32px;
            height: 32px;
            background: #f0f2f5;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .export-chart-btn:hover {
            background: #e0e0e0;
        }

        .quick-reports-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 20px;
        }

        .report-card {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            background: #f8f9fa;
            transition: all 0.3s;
            cursor: pointer;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .report-card i {
            font-size: 40px;
            color: #0056b3;
            margin-bottom: 15px;
        }

        .report-card h4 {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .report-card p {
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
        }

        .btn-report {
            padding: 8px 16px;
            background: #0056b3;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }

        @media (max-width: 1024px) {
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-row { grid-template-columns: 1fr; }
            .quick-reports-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .summary-grid { grid-template-columns: 1fr; }
            .quick-reports-grid { grid-template-columns: 1fr; }
            .period-buttons { flex-direction: column; }
            .date-range-inputs { flex-direction: column; }
        }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="index.html" class="logo" style="text-decoration: none; color: inherit;">
                <i class="fas fa-hospital-user"></i>
                <div class="logo-text">
                    <h2>Hospital do Ramiros</h2>
                    <span>Gestão Hospitalar</span>
                </div>
            </a>
        </div>
        
        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                <span>Dashboard</span>
            </a>
            <a href="pacientes.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Pacientes</span>
            </a>
            <a href="triagem.php" class="nav-item">
                <i class="fas fa-stethoscope"></i>
                <span>Triagem</span>
            </a>
            <a href="atendimento.php" class="nav-item">
                <i class="fas fa-notes-medical"></i>
                <span>Atendimento</span>
            </a>
            <a href="leitos.php" class="nav-item">
                <i class="fas fa-bed"></i>
                <span>Leitos</span>
            </a>
            <a href="relatorios.php" class="nav-item active">
                <i class="fas fa-file-alt"></i>
                <span>Relatórios</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile">
                <i class="fas fa-user-circle"></i>
                <div class="user-info">
                    <span id="userName">Dr. Ramiros</span>
                    <small>Diretor</small>
                </div>
            </div>
            <button class="logout-btn" id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </button>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-file-alt"></i>
                    Relatórios Gerenciais
                </h1>
                <p class="page-subtitle">Análise de dados e métricas do Hospital do Ramiros</p>
            </div>
            <div class="header-actions">
                <div class="date-range">
                    <i class="fas fa-calendar-alt"></i>
                    <span id="currentDate"></span>
                </div>
                <div class="notification-badge">
                    <i class="fas fa-bell"></i>
                    <span class="badge">0</span>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-dropdown-header">
                            <h4>Notificações</h4>
                        </div>
                        <div class="notification-list"></div>
                    </div>
                </div>
                <div class="doctor-info">
                    <i class="fas fa-user-md"></i>
                    <div>
                        <span id="doctorName">Carregando...</span>
                        <small id="doctorTitle"></small>
                    </div>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <!-- Filtros de Período -->
            <div class="filters-card">
                <div class="filters-header">
                    <i class="fas fa-filter"></i>
                    <span>Filtros de Período</span>
                </div>
                <div class="filters-body">
                    <div class="filter-group">
                        <label>Período:</label>
                        <div class="period-buttons">
                            <button class="period-btn active" data-period="7">Últimos 7 dias</button>
                            <button class="period-btn" data-period="30">Últimos 30 dias</button>
                            <button class="period-btn" data-period="90">Últimos 90 dias</button>
                            <button class="period-btn" data-period="365">Último ano</button>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label>Intervalo Personalizado:</label>
                        <div class="date-range-inputs">
                            <input type="date" id="dataInicio">
                            <span>até</span>
                            <input type="date" id="dataFim">
                            <button class="btn-apply" id="applyDateBtn">Aplicar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cards de Resumo -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="summary-info">
                        <div class="summary-value" id="totalAtendimentos">0</div>
                        <div class="summary-label">Total de Atendimentos</div>
                        <div class="summary-trend" id="trendAtendimentos">
                            <i class="fas fa-chart-line"></i>
                            <span>Carregando...</span>
                        </div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div class="summary-info">
                        <div class="summary-value" id="totalPacientes">0</div>
                        <div class="summary-label">Pacientes Atendidos</div>
                        <div class="summary-trend" id="trendPacientes">
                            <i class="fas fa-chart-line"></i>
                            <span>Carregando...</span>
                        </div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-chart-simple"></i>
                    </div>
                    <div class="summary-info">
                        <div class="summary-value" id="mediaDiaria">0</div>
                        <div class="summary-label">Média Diária</div>
                        <div class="summary-trend" id="trendMedia">
                            <i class="fas fa-chart-line"></i>
                            <span>Carregando...</span>
                        </div>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="summary-info">
                        <div class="summary-value" id="tempoMedio">0 min</div>
                        <div class="summary-label">Tempo Médio de Espera</div>
                        <div class="summary-trend" id="trendTempo">
                            <i class="fas fa-chart-line"></i>
                            <span>Carregando...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos Principais -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Atendimentos por Dia</h3>
                            <p>Evolução diária no período selecionado</p>
                        </div>
                        <div class="chart-export">
                            <button class="export-chart-btn" data-chart="atendimentos">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="atendimentosChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Distribuição por Prioridade</h3>
                            <p>Classificação de risco dos atendimentos</p>
                        </div>
                        <div class="chart-export">
                            <button class="export-chart-btn" data-chart="prioridade">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="prioridadeChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Atendimentos por Convênio</h3>
                            <p>Distribuição por tipo de convênio</p>
                        </div>
                        <div class="chart-export">
                            <button class="export-chart-btn" data-chart="convenio">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="convenioChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Horários de Pico</h3>
                            <p>Distribuição de atendimentos por hora</p>
                        </div>
                        <div class="chart-export">
                            <button class="export-chart-btn" data-chart="horarios">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="horariosChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tabelas de Dados -->
            <div class="card">
                <div class="card-header">
                    <div class="header-left">
                        <i class="fas fa-table"></i>
                        <div>
                            <h3>Atendimentos Detalhados</h3>
                            <p>Lista completa dos atendimentos no período</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <button class="btn-export" id="exportAtendimentosBtn">
                            <i class="fas fa-file-excel"></i>
                            Exportar CSV
                        </button>
                        <button class="btn-export" id="exportPDFBtn">
                            <i class="fas fa-file-code"></i>
                            Exportar HTML
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="atendimentosTable">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Paciente</th>
                                <th>Prioridade</th>
                                <th>Médico</th>
                                <th>Diagnóstico</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="atendimentosTableBody">
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Carregando dados...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="pagination" id="pagination">
                    <button class="page-btn" id="prevPageBtn" disabled>
                        <i class="fas fa-chevron-left"></i>
                        Anterior
                    </button>
                    <span class="page-info" id="pageInfo">Página 1 de 1</span>
                    <button class="page-btn" id="nextPageBtn" disabled>
                        Próxima
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Relatórios Rápidos -->
            <div class="quick-reports">
                <div class="card">
                    <div class="card-header">
                        <div class="header-left">
                            <i class="fas fa-print"></i>
                            <div>
                                <h3>Relatórios Rápidos</h3>
                                <p>Exporte relatórios pré-configurados</p>
                            </div>
                        </div>
                    </div>
                    <div class="quick-reports-grid">
                        <div class="report-card" data-report="pacientes">
                            <i class="fas fa-users"></i>
                            <h4>Lista de Pacientes</h4>
                            <p>Relatório completo de pacientes cadastrados</p>
                            <button class="btn-report">Gerar Relatório</button>
                        </div>
                        <div class="report-card" data-report="leitos">
                            <i class="fas fa-bed"></i>
                            <h4>Ocupação de Leitos</h4>
                            <p>Relatório de ocupação e disponibilidade</p>
                            <button class="btn-report">Gerar Relatório</button>
                        </div>
                        <div class="report-card" data-report="triagem">
                            <i class="fas fa-stethoscope"></i>
                            <h4>Relatório de Triagem</h4>
                            <p>Análise de classificação de risco</p>
                            <button class="btn-report">Gerar Relatório</button>
                        </div>
                        <div class="report-card" data-report="financeiro">
                            <i class="fas fa-chart-line"></i>
                            <h4>Relatório Financeiro</h4>
                            <p>Análise de convênios e faturamento</p>
                            <button class="btn-report">Gerar Relatório</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Visualização de Relatório -->
    <div class="modal" id="reportModal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-file-alt"></i>
                    Visualizar Relatório
                </h3>
                <button class="modal-close" onclick="closeModal('reportModal')">
                </button>
            </div>
            <div class="modal-body" id="reportModalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('reportModal')">Fechar</button>
                <button class="btn btn-primary" id="printReportBtn">
                    <i class="fas fa-print"></i>
                    Imprimir
                </button>
                <button class="btn btn-success" id="downloadReportBtn">
                    <i class="fas fa-download"></i>
                    Download
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="medical-spinner">
                <div class="pulse-ring"></div>
                <i class="fas fa-heartbeat"></i>
            </div>
            <p>Gerando relatório...</p>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/relatorio.js"></script>
</body>
</html>