// pacientes.js - Versão Profissional

let currentPage = 1;
let itemsPerPage = 10;
let totalPacientes = 0;
let currentSearch = '';

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    loadPacientes();
    loadStats();
    setupEventListeners();
    updateDateTime();
    setInterval(updateDateTime, 1000);
});

function setupEventListeners() {
    document.getElementById('pacienteForm').addEventListener('submit', handleSubmit);
    document.getElementById('searchBtn').addEventListener('click', () => searchPacientes());
    document.getElementById('clearSearchBtn').addEventListener('click', clearSearch);
    document.getElementById('refreshPacientesBtn').addEventListener('click', () => {
        loadPacientes();
        loadStats();
        showToast('Lista atualizada!', 'success');
    });
    document.getElementById('exportPacientesBtn').addEventListener('click', exportPacientes);
    document.getElementById('prevPageBtn').addEventListener('click', () => changePage(-1));
    document.getElementById('nextPageBtn').addEventListener('click', () => changePage(1));
    
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') searchPacientes();
    });

    // Event delegation for action buttons and row clicks (evita XSS por inline onclick)
    document.querySelector('.table-responsive')?.addEventListener('click', function(e) {
        const row = e.target.closest('.clickable-row');
        if (row && !e.target.closest('.action-btn') && !e.target.closest('[data-stop-propagation]')) {
            verDetalhesPaciente(parseInt(row.dataset.rowId));
            return;
        }
        const btn = e.target.closest('.action-btn');
        if (!btn) return;
        if (btn.dataset.view) verDetalhesPaciente(parseInt(btn.dataset.view));
        if (btn.dataset.edit) editarPaciente(parseInt(btn.dataset.edit));
        if (btn.dataset.delete) {
            const data = JSON.parse(btn.dataset.delete);
            confirmarExclusao(data.id, data.nome);
        }
    });
}

function updateDateTime() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    const dateElement = document.getElementById('currentDate');
    if (dateElement) {
        dateElement.textContent = now.toLocaleDateString('pt-AO', options);
    }
}

async function loadStats() {
    try {
        const response = await fetch('api/pacientes.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('totalPacientes').textContent = data.total || 0;
            document.getElementById('pacientesHoje').textContent = data.hoje || 0;
            document.getElementById('pacientesInternados').textContent = data.internados || 0;
        }
    } catch (error) {
        console.error('Erro ao carregar stats:', error);
    }
}

async function loadPacientes() {
    showLoading();
    
    try {
        let url = `api/pacientes.php?action=list&page=${currentPage}&limit=${itemsPerPage}`;
        if (currentSearch) {
            url += `&search=${encodeURIComponent(currentSearch)}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            totalPacientes = data.total || 0;
            renderPacientesTable(data.pacientes);
            updatePagination();
        } else {
            showToast(data.erro || 'Erro ao carregar pacientes', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão com o servidor', 'error');
    } finally {
        hideLoading();
    }
}

function renderPacientesTable(pacientes) {
    const tbody = document.getElementById('pacientesTableBody');
    
    if (!pacientes || pacientes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>Nenhum paciente encontrado</p>
                    <small>Tente outros filtros de busca</small>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = pacientes.map(paciente => `
        <tr data-row-id="${paciente.id}" class="clickable-row">
            <td><strong>${escapeHtml(paciente.nome_completo)}</strong></td>
            <td>${paciente.cpf || '—'}</td>
            <td>${paciente.telefone || '—'}</td>
            <td>${paciente.convenio || 'Particular'}</td>
            <td>${paciente.numero_convenio || '—'}</td>
            <td>${formatDate(paciente.ultimo_atendimento)}</td>
            <td>
                <span class="status-badge ${getStatusClass(paciente.status)}">
                    ${getStatusLabel(paciente.status)}
                </span>
            </td>
            <td>
                <div class="action-buttons" data-stop-propagation>
                    <button class="action-btn view" data-view="${paciente.id}" title="Visualizar">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn edit" data-edit="${paciente.id}" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete" data-delete='${JSON.stringify({id: paciente.id, nome: paciente.nome_completo}).replace(/'/g, "&#39;")}' title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function updatePagination() {
    const totalPages = Math.ceil(totalPacientes / itemsPerPage);
    const pageInfo = document.getElementById('pageInfo');
    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');
    
    pageInfo.textContent = `Página ${currentPage} de ${totalPages || 1}`;
    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;
}

function changePage(delta) {
    const newPage = currentPage + delta;
    const totalPages = Math.ceil(totalPacientes / itemsPerPage);
    
    if (newPage >= 1 && newPage <= totalPages) {
        currentPage = newPage;
        loadPacientes();
    }
}

async function handleSubmit(e) {
    e.preventDefault();
    
    const id = document.getElementById('pacienteId').value;
    const formData = {
        nome_completo: document.getElementById('nome_completo').value,
        data_nascimento: document.getElementById('data_nascimento').value,
        cpf: document.getElementById('cpf').value,
        telefone: document.getElementById('telefone').value,
        endereco: document.getElementById('endereco').value,
        convenio: document.getElementById('convenio').value,
        numero_convenio: document.getElementById('numero_convenio').value,
        email: document.getElementById('email').value,
        plano: document.getElementById('plano').value,
        contato_emergencia: document.getElementById('contato_emergencia').value,
        telefone_emergencia: document.getElementById('telefone_emergencia').value,
        observacoes: document.getElementById('observacoes').value
    };
    
    if (!formData.nome_completo || !formData.data_nascimento || !formData.cpf || !formData.telefone || !formData.endereco) {
        showToast('Preencha todos os campos obrigatórios', 'error');
        return;
    }
    
    showLoading();
    
    try {
        const url = id ? `api/pacientes.php?id=${id}` : 'api/pacientes.php';
        const method = id ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(id ? 'Paciente atualizado com sucesso!' : 'Paciente cadastrado com sucesso!', 'success');
            resetForm();
            closeModal('pacienteFormModal');
            loadPacientes();
            loadStats();
        } else {
            showToast(data.erro || 'Erro ao salvar paciente', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão com o servidor', 'error');
    } finally {
        hideLoading();
    }
}

async function editarPaciente(id) {
    showLoading();
    
    try {
        const response = await fetch(`api/pacientes.php?id=${id}`);
        const data = await response.json();
        
        if (data.success && data.paciente) {
            const p = data.paciente;
            document.getElementById('pacienteId').value = p.id;
            document.getElementById('nome_completo').value = p.nome_completo;
            document.getElementById('data_nascimento').value = p.data_nascimento;
            document.getElementById('cpf').value = p.cpf;
            document.getElementById('telefone').value = p.telefone;
            document.getElementById('endereco').value = p.endereco;
            document.getElementById('convenio').value = p.convenio || '';
            document.getElementById('numero_convenio').value = p.numero_convenio || '';
            document.getElementById('email').value = p.email || '';
            document.getElementById('plano').value = p.plano || '';
            document.getElementById('contato_emergencia').value = p.contato_emergencia || '';
            document.getElementById('telefone_emergencia').value = p.telefone_emergencia || '';
            document.getElementById('observacoes').value = p.observacoes || '';
            
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Paciente';
            document.getElementById('saveBtn').innerHTML = '<i class="fas fa-save"></i> Atualizar Paciente';
            
            openModal('pacienteFormModal');
        } else {
            showToast('Erro ao carregar dados do paciente', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
    } finally {
        hideLoading();
    }
}



function resetForm() {
    document.getElementById('pacienteForm').reset();
    document.getElementById('pacienteId').value = '';
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-user-plus"></i> Novo Paciente';
    document.getElementById('saveBtn').innerHTML = '<i class="fas fa-save"></i> Salvar Paciente';
}

function cancelEdit() {
    resetForm();
    closeModal('pacienteFormModal');
}

async function verDetalhesPaciente(id) {
    showLoading();
    try {
        const response = await fetch(`api/pacientes.php?id=${id}`);
        const data = await response.json();
        if (data.success && data.paciente) {
            const p = data.paciente;
            const modalBody = document.getElementById('pacienteDetalhes');
            modalBody.innerHTML = `
                <div class="patient-details">
                    <div class="detail-section">
                        <h4><i class="fas fa-user"></i> Dados Pessoais</h4>
                        <div class="detail-row"><span class="detail-label">Nome:</span><span class="detail-value">${escapeHtml(p.nome_completo)}</span></div>
                        <div class="detail-row"><span class="detail-label">BI:</span><span class="detail-value">${p.cpf || '—'}</span></div>
                        <div class="detail-row"><span class="detail-label">Nascimento:</span><span class="detail-value">${formatDate(p.data_nascimento)}</span></div>
                        <div class="detail-row"><span class="detail-label">Telefone:</span><span class="detail-value">${p.telefone || '—'}</span></div>
                        <div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value">${p.email || '—'}</span></div>
                        <div class="detail-row"><span class="detail-label">Endereço:</span><span class="detail-value">${escapeHtml(p.endereco) || '—'}</span></div>
                    </div>
                    <div class="detail-section">
                        <h4><i class="fas fa-hospital"></i> Convênio</h4>
                        <div class="detail-row"><span class="detail-label">Convênio:</span><span class="detail-value">${p.convenio || 'Particular'}</span></div>
                        <div class="detail-row"><span class="detail-label">Nº Convênio:</span><span class="detail-value">${p.numero_convenio || '—'}</span></div>
                        <div class="detail-row"><span class="detail-label">Plano:</span><span class="detail-value">${p.plano || '—'}</span></div>
                    </div>
                    <div class="detail-section">
                        <h4><i class="fas fa-exclamation-circle"></i> Emergência</h4>
                        <div class="detail-row"><span class="detail-label">Contato:</span><span class="detail-value">${escapeHtml(p.contato_emergencia) || '—'}</span></div>
                        <div class="detail-row"><span class="detail-label">Telefone:</span><span class="detail-value">${p.telefone_emergencia || '—'}</span></div>
                    </div>
                    ${p.observacoes ? `<div class="detail-section full-width"><h4><i class="fas fa-comment-medical"></i> Observações</h4><p>${escapeHtml(p.observacoes)}</p></div>` : ''}
                </div>`;
            document.getElementById('editarPacienteModalBtn').onclick = () => { closeModal('pacienteModal'); editarPaciente(id); };
            openModal('pacienteModal');
        } else {
            showToast('Erro ao carregar dados do paciente', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    } finally {
        hideLoading();
    }
}

let deleteId = null;

function confirmarExclusao(id, nome) {
    deleteId = id;
    document.getElementById('deleteInfo').innerHTML = `Paciente: <strong>${nome}</strong>`;
    openModal('confirmModal');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    if (!deleteId) return;
    
    showLoading();
    
    try {
        const response = await fetch(`api/pacientes.php?id=${deleteId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Paciente excluído com sucesso!', 'success');
            closeModal('confirmModal');
            loadPacientes();
            loadStats();
            deleteId = null;
        } else {
            showToast(data.erro || 'Erro ao excluir paciente', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
    } finally {
        hideLoading();
    }
});

function searchPacientes() {
    currentSearch = document.getElementById('searchInput').value;
    currentPage = 1;
    loadPacientes();
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    currentSearch = '';
    currentPage = 1;
    loadPacientes();
}

async function exportPacientes() {
    showLoading();
    
    try {
        const response = await fetch('api/pacientes.php?action=export');
        const data = await response.json();
        
        if (data.success && data.pacientes) {
            const csv = convertToCSV(data.pacientes);
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.setAttribute('download', `pacientes_${new Date().toISOString().slice(0,19)}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            showToast('Relatório exportado com sucesso!', 'success');
        }
    } catch (error) {
        showToast('Erro ao exportar', 'error');
    } finally {
        hideLoading();
    }
}

function convertToCSV(pacientes) {
    const headers = ['Nome', 'BI', 'Telefone', 'Email', 'Convênio', 'Nº Convênio', 'Endereço'];
    const csvRows = [headers.join(',')];
    
    for (const p of pacientes) {
        const values = [
            `"${p.nome_completo}"`,
            `"${p.cpf || ''}"`,
            `"${p.telefone || ''}"`,
            `"${p.email || ''}"`,
            `"${p.convenio || ''}"`,
            `"${p.numero_convenio || ''}"`,
            `"${p.endereco || ''}"`
        ];
        csvRows.push(values.join(','));
    }
    
    return csvRows.join('\n');
}

function formatDate(dateString) {
    if (!dateString) return '—';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-AO');
}

function getStatusClass(status) {
    const classes = {
        'ativo': 'status-ativo',
        'inativo': 'status-inativo',
        'internado': 'status-internado'
    };
    return classes[status] || 'status-ativo';
}

function getStatusLabel(status) {
    const labels = {
        'ativo': 'Ativo',
        'inativo': 'Inativo',
        'internado': 'Internado'
    };
    return labels[status] || 'Ativo';
}

// escapeHtml, showToast, openModal, closeModal, showLoading, hideLoading - definidos em main.js