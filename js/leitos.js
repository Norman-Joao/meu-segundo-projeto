// leitos.js - Versão Profissional

let currentFilter = 'all';
let leitosData = [];

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    loadLeitos();
    setupEventListeners();
    updateDateTime();
    setInterval(updateDateTime, 1000);
});

function setupEventListeners() {
    // Filtros
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            renderLeitosGrid();
        });
    });
    
    // Botão adicionar leito
    document.getElementById('adicionarLeitoBtn')?.addEventListener('click', () => {
        document.getElementById('adicionarLeitoForm').reset();
        openModal('adicionarLeitoModal');
    });
    
    // Botão refresh
    document.getElementById('refreshLeitosBtn')?.addEventListener('click', () => {
        loadLeitos();
        showToast('Mapa de leitos atualizado!', 'success');
    });
    
    // Botão exportar
    document.getElementById('exportLeitosBtn')?.addEventListener('click', () => {
        exportRelatorioLeitos();
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

async function loadLeitos() {
    showLoading();
    
    try {
        const response = await fetch('api/leitos.php');
        const data = await response.json();
        
        if (data.success) {
            leitosData = data.leitos;
            updateKPIs(data);
            renderLeitosGrid();
            loadHistorico();
        } else {
            showToast('Erro ao carregar leitos', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão com o servidor', 'error');
    } finally {
        hideLoading();
    }
}

function updateKPIs(data) {
    const total = data.total_leitos || 1;
    const disponiveis = data.disponiveis || 0;
    const ocupados = data.ocupados || 0;
    const manutencao = data.manutencao || 0;
    
    // Atualizar valores
    document.getElementById('leitosDisponiveis').textContent = disponiveis;
    document.getElementById('leitosOcupados').textContent = ocupados;
    document.getElementById('leitosManutencao').textContent = manutencao;
    
    // Atualizar percentuais
    const percentDisponivel = (disponiveis / total) * 100;
    const percentOcupado = (ocupados / total) * 100;
    const percentManutencao = (manutencao / total) * 100;
    
    document.getElementById('percentDisponivel').textContent = `${percentDisponivel.toFixed(1)}%`;
    document.getElementById('percentOcupado').textContent = `${percentOcupado.toFixed(1)}%`;
    document.getElementById('percentManutencao').textContent = `${percentManutencao.toFixed(1)}%`;
    
    // Atualizar barras de progresso
    document.getElementById('progressDisponivel').style.width = `${percentDisponivel}%`;
    document.getElementById('progressOcupado').style.width = `${percentOcupado}%`;
    document.getElementById('progressManutencao').style.width = `${percentManutencao}%`;
}

function renderLeitosGrid() {
    const grid = document.getElementById('leitosGrid');
    if (!grid) return;
    
    let filteredLeitos = leitosData;
    if (currentFilter !== 'all') {
        filteredLeitos = leitosData.filter(l => l.status === currentFilter);
    }
    
    if (filteredLeitos.length === 0) {
        grid.innerHTML = `
            <div class="empty-state" style="grid-column: 1/-1;">
                <i class="fas fa-bed"></i>
                <p>Nenhum leito encontrado com este filtro</p>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = filteredLeitos.map(leito => `
        <div class="leito-card ${leito.status}" onclick="verDetalhesLeito(${leito.id})">
            <div class="leito-header">
                <span class="leito-numero">#${leito.numero_leito}</span>
                <span class="leito-status ${leito.status}"></span>
            </div>
            <div class="leito-ala">
                <i class="fas fa-building"></i>
                Ala ${leito.ala || 'Geral'}
            </div>
            <div class="leito-paciente">
                <i class="fas ${leito.status === 'ocupado' ? 'fa-user-injured' : 'fa-user-plus'}"></i>
                ${leito.paciente_nome || (leito.status === 'disponivel' ? 'Leito disponível' : 'Em manutenção')}
            </div>
            ${leito.status === 'ocupado' && leito.tempo_ocupacao ? `
                <div class="leito-tempo">
                    <i class="fas fa-clock"></i>
                    Ocupado há ${leito.tempo_ocupacao}
                </div>
            ` : ''}
            <div class="leito-actions">
                ${leito.status === 'disponivel' ? `
                    <button class="leito-action-btn ocupar" onclick="event.stopPropagation(); abrirModalOcupar(${leito.id}, '${leito.numero_leito}')" title="Ocupar Leito">
                        <i class="fas fa-user-plus"></i>
                    </button>
                    <button class="leito-action-btn manutencao-btn" onclick="event.stopPropagation(); abrirModalManutencao(${leito.id}, '${leito.numero_leito}')" title="Manutenção">
                        <i class="fas fa-tools"></i>
                    </button>
                ` : ''}
                ${leito.status === 'ocupado' ? `
                    <button class="leito-action-btn liberar" onclick="event.stopPropagation(); abrirModalLiberar(${leito.id}, '${leito.numero_leito}')" title="Liberar Leito">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                    <button class="leito-action-btn manutencao-btn" onclick="event.stopPropagation(); abrirModalManutencao(${leito.id}, '${leito.numero_leito}')" title="Manutenção">
                        <i class="fas fa-tools"></i>
                    </button>
                ` : ''}
                ${leito.status === 'manutencao' ? `
                    <button class="leito-action-btn ocupar" onclick="event.stopPropagation(); abrirModalManutencao(${leito.id}, '${leito.numero_leito}')" title="Retornar Disponível">
                        <i class="fas fa-check"></i>
                    </button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

async function loadHistorico() {
    const container = document.getElementById('historicoContainer');
    if (!container) return;
    
    try {
        const response = await fetch('api/leitos.php?action=historico');
        const data = await response.json();
        
        if (data.success && data.historico && data.historico.length > 0) {
            const table = `
                <table class="historico-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Leito</th>
                            <th>Ação</th>
                            <th>Paciente</th>
                            <th>Responsável</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.historico.map(item => `
                            <tr>
                                <td>${formatDateTime(item.data_hora)}</td>
                                <td>Leito #${item.numero_leito}</td>
                                <td>${getAcaoLabel(item.acao)}</td>
                                <td>${item.paciente_nome || '—'}</td>
                                <td>${item.responsavel || 'Sistema'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            container.innerHTML = table;
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>Nenhuma movimentação recente</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Erro:', error);
    }
}

function verDetalhesLeito(leitoId) {
    const leito = leitosData.find(l => l.id === leitoId);
    if (!leito) return;
    
    document.getElementById('leitoModalNumero').textContent = `#${leito.numero_leito}`;
    document.getElementById('leitoModalAla').textContent = leito.ala || 'UTI';
    document.getElementById('leitoModalPaciente').textContent = leito.paciente_nome || 'Nenhum';
    document.getElementById('leitoModalDataOcupacao').textContent = leito.data_ocupacao || '—';
    document.getElementById('leitoModalTempo').textContent = leito.tempo_ocupacao || '—';
    
    const statusBadge = document.getElementById('leitoModalStatusBadge');
    statusBadge.textContent = getStatusLabel(leito.status);
    statusBadge.className = `leito-badge ${leito.status}`;
    
    const actionsContainer = document.getElementById('leitoModalActions');
    if (leito.status === 'disponivel') {
        actionsContainer.innerHTML = `
            <button class="btn btn-success" onclick="closeModal('leitoModal'); abrirModalOcupar(${leito.id}, '${leito.numero_leito}')">
                <i class="fas fa-user-plus"></i> Ocupar Leito
            </button>
            <button class="btn btn-warning" onclick="closeModal('leitoModal'); abrirModalManutencao(${leito.id}, '${leito.numero_leito}')">
                <i class="fas fa-tools"></i> Manutenção
            </button>
        `;
    } else if (leito.status === 'ocupado') {
        actionsContainer.innerHTML = `
            <button class="btn btn-danger" onclick="closeModal('leitoModal'); abrirModalLiberar(${leito.id}, '${leito.numero_leito}')">
                <i class="fas fa-sign-out-alt"></i> Liberar Leito
            </button>
            <button class="btn btn-warning" onclick="closeModal('leitoModal'); abrirModalManutencao(${leito.id}, '${leito.numero_leito}')">
                <i class="fas fa-tools"></i> Manutenção
            </button>
        `;
    } else {
        actionsContainer.innerHTML = `
            <button class="btn btn-primary" onclick="closeModal('leitoModal'); abrirModalManutencao(${leito.id}, '${leito.numero_leito}')">
                <i class="fas fa-check"></i> Retornar Disponível
            </button>
        `;
    }
    
    openModal('leitoModal');
}

async function abrirModalOcupar(leitoId, leitoNumero) {
    // Carregar pacientes para o select
    try {
        const response = await fetch('api/pacientes.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('pacienteSelect');
            select.innerHTML = '<option value="">Selecione um paciente...</option>';
            data.pacientes.forEach(paciente => {
                select.innerHTML += `<option value="${paciente.id}">${paciente.nome_completo} - ${paciente.cpf}</option>`;
            });
        }
    } catch (error) {
        console.error('Erro:', error);
    }
    
    document.getElementById('ocuparLeitoId').value = leitoId;
    openModal('ocuparLeitoModal');
}

function abrirModalLiberar(leitoId, leitoNumero) {
    document.getElementById('liberarLeitoId').value = leitoId;
    document.getElementById('liberarLeitoNumero').textContent = `#${leitoNumero}`;
    openModal('liberarLeitoModal');
}

function abrirModalManutencao(leitoId, leitoNumero) {
    document.getElementById('manutencaoLeitoId').value = leitoId;
    openModal('manutencaoLeitoModal');
}

async function confirmarOcuparLeito() {
    const leitoId = document.getElementById('ocuparLeitoId').value;
    const pacienteId = document.getElementById('pacienteSelect').value;
    const observacoes = document.getElementById('observacoesOcupacao').value;
    
    if (!pacienteId) {
        showToast('Selecione um paciente', 'error');
        return;
    }
    
    showLoading();
    
    try {
        const response = await fetch('api/leitos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'ocupar',
                leito_id: leitoId,
                paciente_id: pacienteId,
                observacoes: observacoes
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Leito ocupado com sucesso!', 'success');
            closeModal('ocuparLeitoModal');
            loadLeitos();
        } else {
            showToast(data.erro || 'Erro ao ocupar leito', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    } finally {
        hideLoading();
    }
}

async function confirmarLiberarLeito() {
    const leitoId = document.getElementById('liberarLeitoId').value;
    
    showLoading();
    
    try {
        const response = await fetch('api/leitos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'liberar',
                leito_id: leitoId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Leito liberado com sucesso!', 'success');
            closeModal('liberarLeitoModal');
            loadLeitos();
        } else {
            showToast(data.erro || 'Erro ao liberar leito', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    } finally {
        hideLoading();
    }
}

async function confirmarManutencao() {
    const leitoId = document.getElementById('manutencaoLeitoId').value;
    const status = document.getElementById('statusManutencao').value;
    const motivo = document.getElementById('motivoManutencao').value;
    
    showLoading();
    
    try {
        const response = await fetch('api/leitos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'manutencao',
                leito_id: leitoId,
                status: status,
                motivo: motivo
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Status do leito atualizado!', 'success');
            closeModal('manutencaoLeitoModal');
            loadLeitos();
        } else {
            showToast(data.erro || 'Erro ao atualizar leito', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    } finally {
        hideLoading();
    }
}

async function confirmarAdicionarLeito() {
    const numero = document.getElementById('novoLeitoNumero').value.trim();
    const ala = document.getElementById('novoLeitoAla').value;

    if (!numero) {
        showToast('Informe o número do leito', 'error');
        return;
    }
    if (!ala) {
        showToast('Selecione a ala', 'error');
        return;
    }

    showLoading();

    try {
        const response = await fetch('api/leitos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'criar',
                numero_leito: numero,
                ala: ala
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast('Leito adicionado com sucesso!', 'success');
            closeModal('adicionarLeitoModal');
            loadLeitos();
        } else {
            showToast(data.erro || 'Erro ao adicionar leito', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão com o servidor', 'error');
    } finally {
        hideLoading();
    }
}

function exportRelatorioLeitos() {
    const data = leitosData.map(leito => ({
        'Número': leito.numero_leito,
        'Ala': leito.ala || 'Geral',
        'Status': getStatusLabel(leito.status),
        'Paciente': leito.paciente_nome || '—',
        'Ocupado desde': leito.data_ocupacao || '—',
        'Tempo': leito.tempo_ocupacao || '—'
    }));
    
    const csv = convertToCSV(data);
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.href = url;
    link.setAttribute('download', `relatorio_leitos_${new Date().toISOString().slice(0,19)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    showToast('Relatório exportado com sucesso!', 'success');
}

function getStatusLabel(status) {
    const labels = {
        'disponivel': 'Disponível',
        'ocupado': 'Ocupado',
        'manutencao': 'Manutenção'
    };
    return labels[status] || status;
}

function getAcaoLabel(acao) {
    const labels = {
        'ocupar': 'Ocupação',
        'liberar': 'Liberação',
        'manutencao': 'Manutenção'
    };
    return labels[acao] || acao;
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-AO') + ' ' + date.toLocaleTimeString('pt-AO', {hour: '2-digit', minute:'2-digit'});
}

// openModal, closeModal, showToast, showLoading, hideLoading - definidos em main.js