<?php require_once __DIR__ . "/api/config.php"; verificarAuthPagina(); ?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacientes - Hospital do Ramiros</title>
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
            <a href="pacientes.php" class="nav-item active">
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
                    <i class="fas fa-users"></i>
                    Pacientes
                </h1>
                <p class="page-subtitle">Gerenciamento completo de pacientes</p>
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
            <!-- Stats Cards -->
            <div class="stats-pacientes-grid">
                <div class="stat-paciente-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="totalPacientes">0</div>
                        <div class="stat-label">Total de Pacientes</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12 este mês</span>
                        </div>
                    </div>
                </div>

                <div class="stat-paciente-card">
                    <div class="stat-icon">
                        <i class="fas fa-ambulance"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="pacientesHoje">0</div>
                        <div class="stat-label">Atendidos Hoje</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span>+5% vs ontem</span>
                        </div>
                    </div>
                </div>

                <div class="stat-paciente-card">
                    <div class="stat-icon">
                        <i class="fas fa-hospital-user"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value" id="pacientesInternados">0</div>
                        <div class="stat-label">Pacientes Internados</div>
                        <div class="stat-trend trend-down">
                            <i class="fas fa-arrow-down"></i>
                            <span>-3 hoje</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Pacientes -->
            <div class="card card-secondary">
                <div class="card-header">
                    <div class="header-left">
                        <i class="fas fa-list"></i>
                        <div>
                            <h3>Lista de Pacientes</h3>
                            <p>Pacientes cadastrados no sistema</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <button class="btn btn-primary" id="addPacienteBtn" onclick="resetForm(); openModal('pacienteFormModal')">
                            <i class="fas fa-plus"></i>
                            Adicionar Paciente
                        </button>
                        <button class="btn-refresh" id="refreshPacientesBtn">
                            <i class="fas fa-sync-alt"></i>
                            Atualizar
                        </button>
                        <button class="btn-export" id="exportPacientesBtn">
                            <i class="fas fa-download"></i>
                            Exportar
                        </button>
                    </div>
                </div>

                <div class="search-section">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Buscar por nome, BI ou convênio...">
                        <button class="btn-search" id="searchBtn">
                            <i class="fas fa-search"></i>
                            Buscar
                        </button>
                        <button class="btn-clear" id="clearSearchBtn">
                            <i class="fas fa-xmark"></i>
                            Limpar
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>BI</th>
                                <th>Telefone</th>
                                <th>Convênio</th>
                                <th>Nº Convênio</th>
                                <th>Último Atendimento</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="pacientesTableBody">
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-folder-open"></i>
                                    <p>Nenhum paciente encontrado</p>
                                    <small>Cadastre um novo paciente para começar</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
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
        </div>
    </main>

    <!-- Modal de Detalhes do Paciente -->
    <div class="modal" id="pacienteModal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-user-circle"></i>
                    Detalhes do Paciente
                </h3>
                <button class="modal-close" onclick="closeModal('pacienteModal')">
                </button>
            </div>
            <div class="modal-body">
                <div id="pacienteDetalhes"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('pacienteModal')">Fechar</button>
                <button class="btn btn-primary" id="editarPacienteModalBtn">
                    <i class="fas fa-edit"></i>
                    Editar Paciente
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal" id="confirmModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-exclamation-triangle"></i>
                    Confirmar Exclusão
                </h3>
                <button class="modal-close" onclick="closeModal('confirmModal')">
                </button>
            </div>
            <div class="modal-body">
                <div class="alert-warning">
                    <i class="fas fa-trash-alt"></i>
                    <div>
                        <strong>Atenção!</strong>
                        <p>Esta ação não pode ser desfeita. O paciente será removido permanentemente do sistema.</p>
                    </div>
                </div>
                <p id="deleteInfo" style="margin-top: 15px; font-size: 13px; color: #666;"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('confirmModal')">Cancelar</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i>
                    Confirmar Exclusão
                </button>
            </div>
        </div>
    </div>

    <!-- Modal do Formulário de Paciente -->
    <div class="modal" id="pacienteFormModal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 id="formTitle"><i class="fas fa-user-plus"></i> Novo Paciente</h3>
                <button class="modal-close" onclick="closeModal('pacienteFormModal')">
                </button>
            </div>
            <form id="pacienteForm">
                <input type="hidden" id="pacienteId" value="">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome_completo">
                                <i class="fas fa-user"></i>
                                Nome Completo <span class="required">*</span>
                            </label>
                            <input type="text" id="nome_completo" placeholder="Digite o nome completo" required>
                        </div>
                        <div class="form-group">
                            <label for="data_nascimento">
                                <i class="fas fa-calendar-alt"></i>
                                Data de Nascimento <span class="required">*</span>
                            </label>
                            <input type="date" id="data_nascimento" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cpf">
                                <i class="fas fa-id-card"></i>
                                BI <span class="required">*</span>
                            </label>
                            <input type="text" id="cpf" placeholder="000000000AA000 (9 dígitos + 2 letras + 3 dígitos)" maxlength="14" required>
                        </div>
                        <div class="form-group">
                            <label for="telefone">
                                <i class="fas fa-phone"></i>
                                Telefone <span class="required">*</span>
                            </label>
                            <input type="text" id="telefone" placeholder="(00) 00000-0000" maxlength="15" required>
                        </div>
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i>
                                E-mail
                            </label>
                            <input type="email" id="email" placeholder="paciente@exemplo.com">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="convenio">
                                <i class="fas fa-hospital"></i>
                                Convênio
                            </label>
                            <select id="convenio">
                                <option value="">Selecione o convênio</option>
                                <option value="Particular">Particular</option>
                                <option value="ENSA">ENSA</option>
                                <option value="Global Saúde">Global Saúde</option>
                                <option value="AAA Seguros">AAA Seguros</option>
                                <option value="Angola Seguros">Angola Seguros</option>
                                <option value="Nossa Seguros">Nossa Seguros</option>
                                <option value="Sagal">Sagal</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="numero_convenio">
                                <i class="fas fa-hashtag"></i>
                                Nº do Convênio
                            </label>
                            <input type="text" id="numero_convenio" placeholder="Número da carteirinha">
                        </div>
                        <div class="form-group">
                            <label for="plano">
                                <i class="fas fa-layer-group"></i>
                                Plano
                            </label>
                            <input type="text" id="plano" placeholder="Ex: Enfermaria, Apartamento">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="endereco">
                            <i class="fas fa-map-marker-alt"></i>
                            Endereço Completo <span class="required">*</span>
                        </label>
                        <textarea id="endereco" rows="2" placeholder="Rua, número, bairro, cidade - Caixa Postal" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contato_emergencia">
                                <i class="fas fa-exclamation-circle"></i>
                                Contato de Emergência
                            </label>
                            <input type="text" id="contato_emergencia" placeholder="Nome do contato">
                        </div>
                        <div class="form-group">
                            <label for="telefone_emergencia">
                                <i class="fas fa-phone-alt"></i>
                                Telefone de Emergência
                            </label>
                            <input type="text" id="telefone_emergencia" placeholder="(00) 00000-0000">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="observacoes">
                            <i class="fas fa-comment-medical"></i>
                            Observações Médicas
                        </label>
                        <textarea id="observacoes" rows="2" placeholder="Alergias, condições especiais, observações importantes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('pacienteFormModal')">
                        <i class="fas fa-xmark"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <i class="fas fa-save"></i>
                        Salvar Paciente
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
    <script src="js/pacientes.js"></script>
</body>
</html>