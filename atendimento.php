<?php require_once __DIR__ . "/api/config.php"; verificarAuthPagina(); ?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atendimento Médico - Hospital do Ramiros</title>
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
            <a href="atendimento.php" class="nav-item active">
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
                    <i class="fas fa-notes-medical"></i>
                    Atendimento Médico
                </h1>
                <p class="page-subtitle">Registrar e consultar atendimentos</p>
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
            <!-- Cards de estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Em Atendimento</h3>
                        <div class="stat-value" id="emAtendimentoCount">0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Aguardando</h3>
                        <div class="stat-value" id="aguardandoCount">0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-simple"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Atendimentos Hoje</h3>
                        <div class="stat-value" id="atendimentosHoje">0</div>
                    </div>
                </div>
            </div>

            <!-- Histórico de Atendimentos -->
            <div class="card card-secondary">
                <div class="card-header">
                    <div class="header-left">
                        <i class="fas fa-history"></i>
                        <div>
                            <h3>Histórico de Atendimentos</h3>
                            <p>Consulta de atendimentos realizados</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <button class="btn btn-primary" onclick="document.getElementById('atendimentoForm').reset(); openModal('atendimentoFormModal')">
                            <i class="fas fa-plus"></i>
                            Novo Atendimento
                        </button>
                    </div>
                </div>
                
                <div class="search-section">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="historicoSearch" placeholder="Buscar por nome do paciente, médico ou diagnóstico...">
                        <button class="btn-search" id="historicoSearchBtn">
                            <i class="fas fa-search"></i>
                            Buscar
                        </button>
                    </div>
                    <div class="filter-buttons" id="historicoPacientes"></div>
                </div>

                <div class="table-container" id="historicoContainer" style="display:none;">
                    <div class="table-header">
                        <h4 id="historicoTitle">
                            <i class="fas fa-file-medical"></i>
                            Resultados
                        </h4>
                        <div class="table-actions">
                            <button class="btn btn-secondary" id="exportBtn" title="Exportar">
                                <i class="fas fa-download"></i>
                                Exportar
                            </button>
                            <button class="btn btn-secondary" id="printBtn" title="Imprimir">
                                <i class="fas fa-print"></i>
                                Imprimir
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Paciente</th>
                                    <th>Médico</th>
                                    <th>Diagnóstico</th>
                                    <th>Encaminhamento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="atendimentosTableBody">
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <i class="fas fa-folder-open"></i>
                                        <p>Nenhum atendimento encontrado</p>
                                        <small>Selecione um paciente para visualizar o histórico</small>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Detalhes do Atendimento -->
    <div class="modal" id="modalAtendimento">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-file-prescription"></i>
                    Detalhes do Atendimento
                </h3>
                <button class="modal-close" onclick="closeModal('modalAtendimento')">
                </button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('modalAtendimento')">Fechar</button>
                <button class="btn btn-primary" id="imprimirAtendimentoBtn">
                    <i class="fas fa-print"></i>
                    Imprimir
                </button>
            </div>
        </div>
    </div>

    <!-- Modal do Formulário de Atendimento -->
    <div class="modal" id="atendimentoFormModal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3><i class="fas fa-plus-circle"></i> Novo Atendimento</h3>
                <button class="modal-close" onclick="closeModal('atendimentoFormModal')">
                </button>
            </div>
            <form id="atendimentoForm">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="triagemSelect">
                                <i class="fas fa-user-injured"></i>
                                Paciente em Triagem <span class="required">*</span>
                            </label>
                            <select id="triagemSelect" required>
                                <option value="">Selecione um paciente...</option>
                            </select>
                            <small class="form-hint">Pacientes com triagem concluída aguardando atendimento</small>
                        </div>
                        <div class="form-group">
                            <label for="medico_responsavel">
                                <i class="fas fa-user-md"></i>
                                Médico Responsável <span class="required">*</span>
                            </label>
                            <input type="text" id="medico_responsavel" placeholder="Nome do médico" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pressao_atendimento">
                                <i class="fas fa-heartbeat"></i>
                                Pressão Arterial
                            </label>
                            <input type="text" id="pressao_atendimento" placeholder="Ex: 120/80">
                        </div>
                        <div class="form-group">
                            <label for="frequencia_atendimento">
                                <i class="fas fa-waveform"></i>
                                Frequência Cardíaca
                            </label>
                            <input type="number" id="frequencia_atendimento" placeholder="bpm">
                        </div>
                        <div class="form-group">
                            <label for="temperatura_atendimento">
                                <i class="fas fa-thermometer-half"></i>
                                Temperatura
                            </label>
                            <input type="number" step="0.1" id="temperatura_atendimento" placeholder="°C">
                        </div>
                        <div class="form-group">
                            <label for="saturacao_atendimento">
                                <i class="fas fa-lungs"></i>
                                Saturação O₂
                            </label>
                            <input type="number" id="saturacao_atendimento" placeholder="%">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="diagnostico">
                            <i class="fas fa-file-prescription"></i>
                            Diagnóstico <span class="required">*</span>
                        </label>
                        <textarea id="diagnostico" rows="3" placeholder="Descreva o diagnóstico do paciente..." required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cid_code">
                                <i class="fas fa-code"></i>
                                CID Principal
                            </label>
                            <input type="text" id="cid_code" placeholder="Ex: J15.0" maxlength="10">
                        </div>
                        <div class="form-group">
                            <label for="encaminhamento">
                                <i class="fas fa-ambulance"></i>
                                Encaminhamento
                            </label>
                            <select id="encaminhamento">
                                <option value="">Selecione uma opção...</option>
                                <option value="Alta">🏠 Alta Médica</option>
                                <option value="Internação">🏥 Internação</option>
                                <option value="Transferência">🚑 Transferência</option>
                                <option value="Observação">🔍 Observação</option>
                                <option value="UTI">⚠️ UTI</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="prescricao">
                                <i class="fas fa-capsules"></i>
                                Prescrição Médica
                            </label>
                            <textarea id="prescricao" rows="4" placeholder="Liste os medicamentos e dosagens prescritos..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="exames_solicitados">
                                <i class="fas fa-flask"></i>
                                Exames Solicitados
                            </label>
                            <textarea id="exames_solicitados" rows="4" placeholder="Exames laboratoriais e de imagem solicitados..."></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="observacoes">
                            <i class="fas fa-comment-medical"></i>
                            Observações Adicionais
                        </label>
                        <textarea id="observacoes" rows="2" placeholder="Observações importantes sobre o atendimento..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('atendimentoFormModal')">
                        <i class="fas fa-xmark"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Registrar Atendimento
                    </button>
                </div>
            </form>
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
            <p>Processando...</p>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/atendimento.js"></script>
</body>
</html>