<?php require_once __DIR__ . "/api/config.php"; verificarAuthPagina(); ?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Leitos - Hospital do Ramiros</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <a href="leitos.php" class="nav-item active">
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
                    <i class="fas fa-bed"></i>
                    Gerenciamento de Leitos
                </h1>
                <p class="page-subtitle">Controle de ocupação e disponibilidade</p>
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
            <div class="kpi-leitos-grid">
                <div class="kpi-leito-card disponivel">
                    <div class="kpi-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="leitosDisponiveis">0</div>
                        <div class="kpi-label">Leitos Disponíveis</div>
                        <div class="kpi-percent" id="percentDisponivel">0%</div>
                    </div>
                    <div class="kpi-progress">
                        <div class="progress-bar" id="progressDisponivel" style="width: 0%"></div>
                    </div>
                </div>

                <div class="kpi-leito-card ocupado">
                    <div class="kpi-icon">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="leitosOcupados">0</div>
                        <div class="kpi-label">Leitos Ocupados</div>
                        <div class="kpi-percent" id="percentOcupado">0%</div>
                    </div>
                    <div class="kpi-progress">
                        <div class="progress-bar" id="progressOcupado" style="width: 0%"></div>
                    </div>
                </div>

                <div class="kpi-leito-card manutencao">
                    <div class="kpi-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="kpi-content">
                        <div class="kpi-value" id="leitosManutencao">0</div>
                        <div class="kpi-label">Em Manutenção</div>
                        <div class="kpi-percent" id="percentManutencao">0%</div>
                    </div>
                    <div class="kpi-progress">
                        <div class="progress-bar" id="progressManutencao" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <!-- Filtros e Ações -->
            <div class="actions-bar">
                <div class="filter-group">
                    <button class="filter-btn active" data-filter="all">
                        <i class="fas fa-th-large"></i>
                        Todos
                    </button>
                    <button class="filter-btn" data-filter="disponivel">
                        <i class="fas fa-check-circle"></i>
                        Disponíveis
                    </button>
                    <button class="filter-btn" data-filter="ocupado">
                        <i class="fas fa-user-injured"></i>
                        Ocupados
                    </button>
                    <button class="filter-btn" data-filter="manutencao">
                        <i class="fas fa-tools"></i>
                        Manutenção
                    </button>
                </div>
                <div class="action-group">
                    <button class="btn btn-primary" id="adicionarLeitoBtn">
                        <i class="fas fa-plus"></i>
                        Adicionar Leito
                    </button>
                    <button class="action-btn" id="refreshLeitosBtn">
                        <i class="fas fa-sync-alt"></i>
                        Atualizar
                    </button>
                    <button class="action-btn" id="exportLeitosBtn">
                        <i class="fas fa-download"></i>
                        Exportar Relatório
                    </button>
                </div>
            </div>

            <!-- Mapa de Leitos -->
            <div class="card">
                <div class="card-header">
                    <div class="header-left">
                        <i class="fas fa-map"></i>
                        <div>
                            <h3>Mapa de Leitos</h3>
                            <p>Visualização da UTI e Enfermaria</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <div class="legend">
                            <span class="legend-item">
                                <i class="fas fa-circle" style="color: #27ae60;"></i>
                                Disponível
                            </span>
                            <span class="legend-item">
                                <i class="fas fa-circle" style="color: #e74c3c;"></i>
                                Ocupado
                            </span>
                            <span class="legend-item">
                                <i class="fas fa-circle" style="color: #f39c12;"></i>
                                Manutenção
                            </span>
                        </div>
                    </div>
                </div>
                <div class="leitos-container">
                    <div class="leitos-grid" id="leitosGrid">
                        <div class="loading-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Carregando mapa de leitos...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Histórico de Ocupação -->
            <div class="card">
                <div class="card-header">
                    <div class="header-left">
                        <i class="fas fa-history"></i>
                        <div>
                            <h3>Histórico de Ocupação</h3>
                            <p>Últimas movimentações de leitos</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <button class="btn btn-secondary" id="verHistoricoBtn">
                            Ver todos <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                <div class="table-responsive" id="historicoContainer">
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>Nenhuma movimentação recente</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Adicionar Leito -->
    <div class="modal" id="adicionarLeitoModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-plus-circle"></i>
                    Adicionar Leito
                </h3>
                <button class="modal-close" onclick="closeModal('adicionarLeitoModal')">
                </button>
            </div>
            <div class="modal-body">
                <form id="adicionarLeitoForm">
                    <div class="form-group">
                        <label for="novoLeitoNumero">
                            <i class="fas fa-hashtag"></i>
                            Número do Leito
                        </label>
                        <input type="text" id="novoLeitoNumero" placeholder="Ex: 001" required>
                    </div>
                    <div class="form-group">
                        <label for="novoLeitoAla">
                            <i class="fas fa-building"></i>
                            Ala
                        </label>
                        <select id="novoLeitoAla" required>
                            <option value="">Selecione a ala...</option>
                            <option value="UTI">UTI</option>
                            <option value="Enfermaria">Enfermaria</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('adicionarLeitoModal')">
                    Cancelar
                </button>
                <button class="btn btn-primary" onclick="confirmarAdicionarLeito()">
                    <i class="fas fa-check"></i>
                    Adicionar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes do Leito -->
    <div class="modal" id="leitoModal">
        <div class="modal-content modal-md">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-bed"></i>
                    Detalhes do Leito
                </h3>
                <button class="modal-close" onclick="closeModal('leitoModal')">
                </button>
            </div>
            <div class="modal-body">
                <div class="leito-detail-card">
                    <div class="detail-header">
                        <span class="leito-number" id="leitoModalNumero">#001</span>
                        <span class="leito-badge" id="leitoModalStatusBadge"></span>
                    </div>
                    <div class="detail-info">
                        <div class="info-row">
                            <i class="fas fa-location-dot"></i>
                            <strong>Ala:</strong>
                            <span id="leitoModalAla">UTI</span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-user"></i>
                            <strong>Paciente:</strong>
                            <span id="leitoModalPaciente">Nenhum</span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-clock"></i>
                            <strong>Ocupado desde:</strong>
                            <span id="leitoModalDataOcupacao">—</span>
                        </div>
                        <div class="info-row">
                            <i class="fas fa-chart-line"></i>
                            <strong>Tempo de ocupação:</strong>
                            <span id="leitoModalTempo">—</span>
                        </div>
                    </div>
                </div>
                <div id="leitoModalActions" class="modal-actions"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('leitoModal')">
                    Fechar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Ocupar Leito -->
    <div class="modal" id="ocuparLeitoModal">
        <div class="modal-content modal-md">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user-plus"></i>
                    Ocupar Leito
                </h3>
                <button class="modal-close" onclick="closeModal('ocuparLeitoModal')">
                </button>
            </div>
            <div class="modal-body">
                <form id="ocuparLeitoForm">
                    <input type="hidden" id="ocuparLeitoId">
                    <div class="form-group">
                        <label for="pacienteSelect">
                            <i class="fas fa-user-injured"></i>
                            Selecionar Paciente
                        </label>
                        <select id="pacienteSelect" required>
                            <option value="">Selecione um paciente...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="observacoesOcupacao">
                            <i class="fas fa-comment"></i>
                            Observações
                        </label>
                        <textarea id="observacoesOcupacao" rows="3" placeholder="Informações adicionais sobre a ocupação..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('ocuparLeitoModal')">
                    Cancelar
                </button>
                <button class="btn btn-primary" onclick="confirmarOcuparLeito()">
                    <i class="fas fa-check"></i>
                    Confirmar Ocupação
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Liberar Leito -->
    <div class="modal" id="liberarLeitoModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-sign-out-alt"></i>
                    Liberar Leito
                </h3>
                <button class="modal-close" onclick="closeModal('liberarLeitoModal')">
                </button>
            </div>
            <div class="modal-body">
                <div class="alert-info">
                    <i class="fas fa-info-circle"></i>
                    <p>Tem certeza que deseja liberar o leito <strong id="liberarLeitoNumero"></strong>?</p>
                </div>
                <p class="text-muted">Esta ação registrará a saída do paciente e disponibilizará o leito para novos atendimentos.</p>
                <input type="hidden" id="liberarLeitoId">
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('liberarLeitoModal')">
                    Cancelar
                </button>
                <button class="btn btn-warning" onclick="confirmarLiberarLeito()">
                    <i class="fas fa-check"></i>
                    Liberar Leito
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Manutenção -->
    <div class="modal" id="manutencaoLeitoModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-tools"></i>
                    Manutenção do Leito
                </h3>
                <button class="modal-close" onclick="closeModal('manutencaoLeitoModal')">
                </button>
            </div>
            <div class="modal-body">
                <form id="manutencaoLeitoForm">
                    <input type="hidden" id="manutencaoLeitoId">
                    <div class="form-group">
                        <label for="statusManutencao">
                            <i class="fas fa-gear"></i>
                            Status
                        </label>
                        <select id="statusManutencao" required>
                            <option value="manutencao">Colocar em Manutenção</option>
                            <option value="disponivel">Retornar para Disponível</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="motivoManutencao">
                            <i class="fas fa-pen"></i>
                            Motivo
                        </label>
                        <textarea id="motivoManutencao" rows="3" placeholder="Descreva o motivo da manutenção..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('manutencaoLeitoModal')">
                    Cancelar
                </button>
                <button class="btn btn-primary" onclick="confirmarManutencao()">
                    <i class="fas fa-check"></i>
                    Confirmar
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
            <p>Processando...</p>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/leitos.js"></script>
</body>
</html>