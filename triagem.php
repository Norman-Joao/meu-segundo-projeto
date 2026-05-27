<?php require_once __DIR__ . "/api/config.php"; verificarAuthPagina(); ?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Triagem - Hospital do Ramiros</title>
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
            <a href="triagem.php" class="nav-item active">
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
                    <i class="fas fa-stethoscope"></i>
                    Triagem
                </h1>
                <p class="page-subtitle">Classificação de risco e registro de pacientes</p>
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
            <!-- Protocolo de Manchester - Cards de Prioridade -->
            <div class="protocol-card">
                <div class="protocol-header">
                    <i class="fas fa-chart-simple"></i>
                    <span>Protocolo de Manchester</span>
                </div>
                <div class="priority-legends">
                    <div class="priority-legend emergencia">
                        <span class="priority-color"></span>
                        <div class="priority-info">
                            <strong>Emergência</strong>
                            <small>Atendimento imediato</small>
                        </div>
                    </div>
                    <div class="priority-legend alta">
                        <span class="priority-color"></span>
                        <div class="priority-info">
                            <strong>Muito Urgente</strong>
                            <small>Atendimento em até 10min</small>
                        </div>
                    </div>
                    <div class="priority-legend media">
                        <span class="priority-color"></span>
                        <div class="priority-info">
                            <strong>Urgente</strong>
                            <small>Atendimento em até 60min</small>
                        </div>
                    </div>
                    <div class="priority-legend baixa">
                        <span class="priority-color"></span>
                        <div class="priority-info">
                            <strong>Pouco Urgente</strong>
                            <small>Atendimento em até 120min</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fila de Espera -->
            <div class="card card-secondary">
                <div class="card-header">
                    <div class="header-left">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h3>Fila de Espera</h3>
                            <p>Pacientes aguardando atendimento</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <button class="btn btn-primary" id="addTriagemBtn" onclick="document.getElementById('triagemForm').reset(); document.getElementById('dorValue').textContent='0 - Sem dor'; document.getElementById('priorityPreview').style.display='none'; prioridadeAtual=null; openModal('triagemFormModal')">
                            <i class="fas fa-plus"></i>
                            Nova Triagem
                        </button>
                        <button class="btn-refresh" id="refreshFilaBtn">
                            <i class="fas fa-sync-alt"></i>
                            Atualizar
                        </button>
                        <button class="btn-export" id="exportFilaBtn">
                            <i class="fas fa-download"></i>
                            Exportar CSV
                        </button>
                    </div>
                </div>

                <div class="fila-stats">
                    <div class="fila-stat-item">
                        <span class="stat-value" id="filaTotal">0</span>
                        <span class="stat-label">Na Fila</span>
                    </div>
                    <div class="fila-stat-item">
                        <span class="stat-value" id="filaEmergencia">0</span>
                        <span class="stat-label">Emergência</span>
                    </div>
                    <div class="fila-stat-item">
                        <span class="stat-value" id="filaAlta">0</span>
                        <span class="stat-label">Alta Prioridade</span>
                    </div>
                    <div class="fila-stat-item">
                        <span class="stat-value" id="filaMedia">0</span>
                        <span class="stat-label">Média Prioridade</span>
                    </div>
                    <div class="fila-stat-item">
                        <span class="stat-value" id="filaBaixa">0</span>
                        <span class="stat-label">Baixa Prioridade</span>
                    </div>
                </div>

                <div id="filaContainer" class="fila-container">
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h4>Fila de espera vazia</h4>
                        <p>Nenhum paciente aguardando atendimento</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Detalhes da Triagem -->
    <div class="modal" id="triagemModal">
        <div class="modal-content modal-md">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-clipboard-list"></i>
                    Detalhes da Triagem
                </h3>
                <button class="modal-close" onclick="closeModal('triagemModal')">
                </button>
            </div>
            <div class="modal-body" id="triagemDetalhes"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('triagemModal')">Fechar</button>
                <button class="btn btn-primary" id="iniciarAtendimentoBtn">
                    <i class="fas fa-stethoscope"></i>
                    Iniciar Atendimento
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Reclassificação -->
    <div class="modal" id="reclassificarModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-chart-simple"></i>
                    Reclassificar Prioridade
                </h3>
                <button class="modal-close" onclick="closeModal('reclassificarModal')">
                </button>
            </div>
            <div class="modal-body">
                <form id="reclassificarForm">
                    <input type="hidden" id="reclassificarTriagemId">
                    <div class="form-group">
                        <label>Nova Prioridade</label>
                        <select id="novaPrioridade" required>
                            <option value="baixa">🟢 Baixa</option>
                            <option value="media">🟡 Média</option>
                            <option value="alta">🟠 Alta</option>
                            <option value="emergencia">🔴 Emergência</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Motivo da Reclassificação</label>
                        <textarea id="motivoReclassificacao" rows="3" placeholder="Descreva o motivo da alteração de prioridade..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('reclassificarModal')">Cancelar</button>
                <button class="btn btn-primary" onclick="confirmarReclassificacao()">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Modal do Formulário de Triagem -->
    <div class="modal" id="triagemFormModal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-clipboard-list"></i> Registrar Triagem</h3>
                <button class="modal-close" onclick="closeModal('triagemFormModal')">
                </button>
            </div>
            <form id="triagemForm">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pacienteSelect">
                                <i class="fas fa-user-injured"></i>
                                Paciente <span class="required">*</span>
                            </label>
                            <select id="pacienteSelect" required>
                                <option value="">Selecione um paciente...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="pressao_arterial">
                                <i class="fas fa-heartbeat"></i>
                                Pressão Arterial <span class="required">*</span>
                            </label>
                            <input type="text" id="pressao_arterial" placeholder="Ex: 120/80" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="frequencia_cardiaca">
                                <i class="fas fa-waveform"></i>
                                Frequência Cardíaca (bpm) <span class="required">*</span>
                            </label>
                            <input type="number" id="frequencia_cardiaca" placeholder="60-100" min="0" max="250" required>
                        </div>
                        <div class="form-group">
                            <label for="temperatura">
                                <i class="fas fa-thermometer-half"></i>
                                Temperatura (°C) <span class="required">*</span>
                            </label>
                            <input type="number" id="temperatura" placeholder="36.5" step="0.1" min="30" max="45" required>
                        </div>
                        <div class="form-group">
                            <label for="saturacao_oxigenio">
                                <i class="fas fa-lungs"></i>
                                Saturação O₂ (%) <span class="required">*</span>
                            </label>
                            <input type="number" id="saturacao_oxigenio" placeholder="95-100" min="0" max="100" required>
                        </div>
                        <div class="form-group">
                            <label for="frequencia_respiratoria">
                                <i class="fas fa-breathing"></i>
                                Frequência Respiratória (rpm)
                            </label>
                            <input type="number" id="frequencia_respiratoria" placeholder="12-20" min="0" max="60">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="glicemia">
                                <i class="fas fa-tint"></i>
                                Glicemia (mg/dL)
                            </label>
                            <input type="number" id="glicemia" placeholder="70-99">
                        </div>
                        <div class="form-group">
                            <label for="dor">
                                <i class="fas fa-face-frown"></i>
                                Nível de Dor (0-10)
                            </label>
                            <input type="range" id="dor" min="0" max="10" step="1" value="0">
                            <span id="dorValue" style="font-size: 12px; margin-top: 5px;">0 - Sem dor</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="sintomas">
                            <i class="fas fa-notes-medical"></i>
                            Sintomas <span class="required">*</span>
                        </label>
                        <textarea id="sintomas" rows="3" placeholder="Descreva os sintomas apresentados pelo paciente..." required></textarea>
                    </div>
                    <div class="priority-preview" id="priorityPreview" style="display: none;">
                        <i class="fas fa-chart-simple"></i>
                        <div>
                            <strong>Classificação de Risco:</strong>
                            <span id="priorityLabel"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('triagemFormModal')">
                        <i class="fas fa-xmark"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Registrar Triagem
                    </button>
                </div>
            </form>
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
    <script src="js/triagem.js"></script>
</body>
</html>