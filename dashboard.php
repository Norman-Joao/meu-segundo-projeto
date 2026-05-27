<?php require_once __DIR__ . "/api/config.php"; verificarAuthPagina(); ?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Executivo - Hospital do Ramiros</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            <a href="dashboard.php" class="nav-item active">
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
            <a href="relatorios.php" class="nav-item">
                <i class="fas fa-file-alt"></i>
                <span>Relatórios</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile">
                <i class="fas fa-user-circle"></i>
                <div class="user-info">
                    <span id="userName">Dr. Admin</span>
                    <small>Administrador</small>
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
                    <i class="fas fa-chart-pie"></i>
                    Dashboard Executivo
                </h1>
                <p class="page-subtitle">Visão geral do pronto-socorro em tempo real</p>
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
            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="statEspera">0</div>
                        <div class="kpi-label">Pacientes em Espera</div>
                        <div class="kpi-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12% hoje</span>
                        </div>
                    </div>
                    <div class="kpi-progress">
                        <div class="progress-bar" style="width: 45%"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="statAtendimento">0</div>
                        <div class="kpi-label">Em Atendimento</div>
                        <div class="kpi-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span>+5% hoje</span>
                        </div>
                    </div>
                    <div class="kpi-progress">
                        <div class="progress-bar" style="width: 68%"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="statLeitos">0 / 0</div>
                        <div class="kpi-label">Leitos Disponíveis</div>
                        <div class="kpi-trend trend-down">
                            <i class="fas fa-arrow-down"></i>
                            <span>-8% hoje</span>
                        </div>
                    </div>
                    <div class="kpi-progress">
                        <div class="progress-bar" style="width: 32%"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="stat24h">0</div>
                        <div class="kpi-label">Atendimentos (24h)</div>
                        <div class="kpi-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span>+23% vs ontem</span>
                        </div>
                    </div>
                    <div class="kpi-progress">
                        <div class="progress-bar" style="width: 78%"></div>
                    </div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="statTempoMedio">0 min</div>
                        <div class="kpi-label">Tempo Médio de Espera</div>
                        <div class="kpi-trend trend-down">
                            <i class="fas fa-arrow-down"></i>
                            <span>-15% hoje</span>
                        </div>
                    </div>
                    <div class="kpi-progress">
                        <div class="progress-bar progress-success" style="width: 85%"></div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Atendimentos por Prioridade</h3>
                            <p>Últimos 7 dias</p>
                        </div>
                        <div class="chart-actions">
                            <button class="chart-btn active" data-period="7">7d</button>
                            <button class="chart-btn" data-period="30">30d</button>
                            <button class="chart-btn" data-period="90">90d</button>
                        </div>
                    </div>
                    <div class="chart-body">
                        <canvas id="prioridadeChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Fluxo de Atendimentos</h3>
                            <p>Horários de maior demanda</p>
                        </div>
                        <i class="fas fa-chart-line chart-icon"></i>
                    </div>
                    <div class="chart-body">
                        <canvas id="fluxoChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Segundo Row de Charts -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Distribuição por Convênio</h3>
                            <p>Atendimentos ativos</p>
                        </div>
                        <i class="fas fa-pie-chart chart-icon"></i>
                    </div>
                    <div class="chart-body">
                        <canvas id="convenioChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <div>
                            <h3>Ocupação de Leitos</h3>
                            <p>Status atual</p>
                        </div>
                        <i class="fas fa-chart-simple chart-icon"></i>
                    </div>
                    <div class="chart-body">
                        <div class="leitos-stats">
                            <div class="leito-category">
                                <div class="leito-label">
                                    <span class="color-dot disponivel"></span>
                                    <span>Disponíveis</span>
                                </div>
                                <div class="leito-value" id="leitosDisponiveis">0</div>
                            </div>
                            <div class="leito-category">
                                <div class="leito-label">
                                    <span class="color-dot ocupado"></span>
                                    <span>Ocupados</span>
                                </div>
                                <div class="leito-value" id="leitosOcupados">0</div>
                            </div>
                            <div class="leito-category">
                                <div class="leito-label">
                                    <span class="color-dot manutencao"></span>
                                    <span>Manutenção</span>
                                </div>
                                <div class="leito-value" id="leitosManutencao">0</div>
                            </div>
                        </div>
                        <canvas id="leitosChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Últimos Atendimentos com Detalhes -->
            <div class="card">
                <div class="card-header">
                    <div class="header-left">
                        <i class="fas fa-history"></i>
                        <div>
                            <h3>Últimos Atendimentos</h3>
                            <p>Registros mais recentes do pronto-socorro</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <button class="btn-refresh" id="refreshAtendimentos">
                            <i class="fas fa-sync-alt"></i>
                            Atualizar
                        </button>
                        <a href="atendimento.php" class="btn btn-secondary">
                            Ver todos <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="table-responsive" id="ultimosAtendimentos">
                    <div class="loading-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Carregando atendimentos...</p>
                    </div>
                </div>
            </div>

            <!-- Alertas e Notificações -->
            <div class="alert-card" id="alertContainer" style="display: none;">
                <div class="alert-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Alertas do Sistema</h3>
                    <button class="alert-close" onclick="this.closest('.alert-card').style.display='none'">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
                <div class="alert-list" id="alertList"></div>
            </div>
        </div>
    </main>

    <!-- Modal de Detalhes -->
    <div class="modal" id="modalDetalhes">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes do Atendimento</h3>
                <button class="modal-close" onclick="closeModal('modalDetalhes')">
                </button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modalDetalhes')">Fechar</button>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="medical-spinner">
                <div class="pulse-ring"></div>
                <i class="fas fa-heartbeat"></i>
            </div>
            <p>Carregando dados...</p>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>