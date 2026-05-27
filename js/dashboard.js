// dashboard.js - Versão Profissional

let prioridadeChart, fluxoChart, convenioChart, leitosChart;

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    setupAutoRefresh();
    setupChartPeriodButtons();
    setupEventListeners();
    updateDateTime();
    setInterval(updateDateTime, 1000);
});

function setupEventListeners() {
    document.getElementById('refreshAtendimentos')?.addEventListener('click', () => {
        loadUltimosAtendimentos();
        showToast('Dados atualizados com sucesso!', 'success');
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

function setupAutoRefresh() {
    // Auto-refresh a cada 30 segundos
    setInterval(() => {
        loadDashboardData(true);
    }, 30000);
}

function setupChartPeriodButtons() {
    document.querySelectorAll('.chart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.chart-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const period = this.dataset.period;
            loadPrioridadeChart(period);
        });
    });
}

async function loadDashboardData(silent = false) {
    if (!silent) showLoading();
    
    try {
        const response = await fetch('api/dashboard.php');
        const data = await response.json();
        
        if (data.success) {
            updateKPIs(data);
            updateCharts(data);
            updateUltimosAtendimentos(data.ultimos_atendimentos);
            checkAlerts(data);
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro ao carregar dados do dashboard', 'error');
    } finally {
        if (!silent) hideLoading();
    }
}

function updateKPIs(data) {
    // Animar os valores
    animateValue('statEspera', 0, data.em_espera || 0, 500);
    animateValue('statAtendimento', 0, data.em_atendimento || 0, 500);
    animateValue('stat24h', 0, data.atendimentos_24h || 0, 500);
    animateValue('statTempoMedio', 0, data.tempo_medio_espera || 0, 500);
    
    const leitos = `${data.leitos_disponiveis || 0}/${data.total_leitos || 0}`;
    document.getElementById('statLeitos').textContent = leitos;
    
    // Atualizar leitos específicos
    document.getElementById('leitosDisponiveis').textContent = data.leitos_disponiveis || 0;
    document.getElementById('leitosOcupados').textContent = data.leitos_ocupados || 0;
    document.getElementById('leitosManutencao').textContent = data.leitos_manutencao || 0;
}

function animateValue(elementId, start, end, duration) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const startTime = performance.now();
    const updateValue = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const value = Math.floor(start + (end - start) * progress);
        
        if (elementId === 'statTempoMedio') {
            element.textContent = `${value} min`;
        } else {
            element.textContent = value;
        }
        
        if (progress < 1) {
            requestAnimationFrame(updateValue);
        }
    };
    
    requestAnimationFrame(updateValue);
}

function updateCharts(data) {
    updatePrioridadeChart(data.prioridades || {});
    updateFluxoChart(data.fluxo_horario || []);
    updateConvenioChart(data.convenios || {});
    updateLeitosChart(data.leitos_disponiveis || 0, data.leitos_ocupados || 0, data.leitos_manutencao || 0);
}

function updatePrioridadeChart(prioridades) {
    const ctx = document.getElementById('prioridadeChart')?.getContext('2d');
    if (!ctx) return;
    
    const labels = ['Emergência', 'Alta', 'Média', 'Baixa'];
    const data = [
        prioridades.emergencia || 0,
        prioridades.alta || 0,
        prioridades.media || 0,
        prioridades.baixa || 0
    ];
    const colors = ['#c0392b', '#e74c3c', '#f39c12', '#27ae60'];
    
    if (prioridadeChart) {
        prioridadeChart.destroy();
    }
    
    prioridadeChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Número de Atendimentos',
                data: data,
                backgroundColor: colors,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.raw} atendimentos`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f0f0f0'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function updateFluxoChart(fluxoData) {
    const ctx = document.getElementById('fluxoChart')?.getContext('2d');
    if (!ctx) return;
    
    const horas = Array.from({length: 24}, (_, i) => `${i}:00`);
    const dados = fluxoData.length === 24 ? fluxoData : Array(24).fill(0);
    
    if (fluxoChart) {
        fluxoChart.destroy();
    }
    
    fluxoChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: horas,
            datasets: [{
                label: 'Atendimentos',
                data: dados,
                borderColor: '#00d4ff',
                backgroundColor: 'rgba(0, 212, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointHoverRadius: 6,
                pointBackgroundColor: '#00d4ff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Quantidade'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Hora'
                    },
                    ticks: {
                        maxRotation: 45,
                        autoSkip: true,
                        maxTicksLimit: 12
                    }
                }
            }
        }
    });
}

function updateConvenioChart(convenios) {
    const ctx = document.getElementById('convenioChart')?.getContext('2d');
    if (!ctx) return;
    
    const labels = Object.keys(convenios);
    const data = Object.values(convenios);
    const colors = ['#00d4ff', '#0099ff', '#0066cc', '#003366', '#3399ff'];
    
    if (convenioChart) {
        convenioChart.destroy();
    }
    
    convenioChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
}

function updateLeitosChart(disponiveis, ocupados, manutencao) {
    const ctx = document.getElementById('leitosChart')?.getContext('2d');
    if (!ctx) return;
    
    if (leitosChart) {
        leitosChart.destroy();
    }
    
    leitosChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Disponíveis', 'Ocupados', 'Manutenção'],
            datasets: [{
                data: [disponiveis, ocupados, manutencao],
                backgroundColor: ['#27ae60', '#e74c3c', '#f39c12'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
}

function updateUltimosAtendimentos(atendimentos) {
    const container = document.getElementById('ultimosAtendimentos');
    if (!container) return;
    
    if (!atendimentos || atendimentos.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h4>Nenhum atendimento registrado</h4>
                <p>Os atendimentos aparecerão aqui</p>
            </div>
        `;
        return;
    }
    
    const table = `
        <table class="atendimentos-table">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Paciente</th>
                    <th>Prioridade</th>
                    <th>Médico</th>
                    <th>Diagnóstico</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                ${atendimentos.map(att => `
                    <tr>
                        <td>${formatDateTime(att.data_hora)}</td>
                        <td><strong>${escapeHtml(att.paciente_nome)}</strong></td>
                        <td><span class="priority-badge priority-${att.prioridade}">${getPriorityLabel(att.prioridade)}</span></td>
                        <td>${escapeHtml(att.medico_responsavel || '—')}</td>
                        <td>${truncate(escapeHtml(att.diagnostico), 50)}</td>
                        <td>
                            <button class="action-btn view-btn" onclick="verDetalhes(${att.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
    
    container.innerHTML = table;
}

function checkAlerts(data) {
    const alertContainer = document.getElementById('alertContainer');
    const alertList = document.getElementById('alertList');
    const alerts = [];
    
    if (data.em_espera > 10) {
        alerts.push({
            icon: '⚠️',
            text: 'Alta demanda na triagem. Mais de 10 pacientes aguardando.',
            time: 'Agora'
        });
    }
    
    if (data.leitos_disponiveis < 3) {
        alerts.push({
            icon: '🛏️',
            text: `Capacidade crítica de leitos. Apenas ${data.leitos_disponiveis} leito(s) disponível(is).`,
            time: 'Agora'
        });
    }
    
    if (data.tempo_medio_espera > 60) {
        alerts.push({
            icon: '⏰',
            text: `Tempo de espera elevado: ${data.tempo_medio_espera} minutos.`,
            time: 'Agora'
        });
    }
    
    if (alerts.length > 0) {
        alertContainer.style.display = 'block';
        alertList.innerHTML = alerts.map(alert => `
            <div class="alert-item">
                <i class="fas ${alert.icon === '⚠️' ? 'fa-exclamation-triangle' : alert.icon === '🛏️' ? 'fa-bed' : 'fa-clock'}"></i>
                <div class="alert-text">${alert.text}</div>
                <div class="alert-time">${alert.time}</div>
            </div>
        `).join('');
    } else {
        alertContainer.style.display = 'none';
    }
}

async function verDetalhes(atendimentoId) {
    showLoading();
    try {
        const response = await fetch(`api/atendimentos.php?id=${atendimentoId}`);
        const data = await response.json();
        if (!data.success || !data.atendimento) throw new Error('Atendimento não encontrado');
        const a = data.atendimento;
        document.getElementById('modalBody').innerHTML = `
            <div class="patient-details">
                <div class="detail-section"><h4><i class="fas fa-user"></i> Paciente</h4><p><strong>${escapeHtml(a.paciente_nome || '')}</strong></p></div>
                <div class="detail-section"><h4><i class="fas fa-user-md"></i> Médico</h4><p>${escapeHtml(a.medico_responsavel)}</p></div>
                <div class="detail-grid">
                    <div class="detail-section"><h4><i class="fas fa-calendar"></i> Data/Hora</h4><p>${formatDateTime(a.data_hora_atendimento)}</p></div>
                    <div class="detail-section"><h4><i class="fas fa-ambulance"></i> Encaminhamento</h4><p>${a.encaminhamento || 'Não informado'}</p></div>
                </div>
                <div class="detail-section"><h4><i class="fas fa-file-prescription"></i> Diagnóstico</h4><p>${escapeHtml(a.diagnostico) || '—'}</p></div>
                <div class="detail-grid">
                    <div class="detail-section"><h4><i class="fas fa-capsules"></i> Prescrição</h4><p>${escapeHtml(a.prescricao) || 'Nenhuma'}</p></div>
                    <div class="detail-section"><h4><i class="fas fa-flask"></i> Exames</h4><p>${escapeHtml(a.exames_solicitados) || 'Nenhum'}</p></div>
                </div>
            </div>`;
        openModal('modalDetalhes');
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        hideLoading();
    }
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-AO') + ' ' + date.toLocaleTimeString('pt-AO', {hour: '2-digit', minute:'2-digit'});
}

function getPriorityLabel(priority) {
    const labels = {
        'emergencia': 'EMERGÊNCIA',
        'alta': 'ALTA',
        'media': 'MÉDIA',
        'baixa': 'BAIXA'
    };
    return labels[priority] || priority;
}

function truncate(str, length) {
    if (!str) return '—';
    return str.length > length ? str.substring(0, length) + '...' : str;
}

// escapeHtml - definido em main.js

function loadPrioridadeChart(period, silent) {
    if (!silent) showLoading();
    fetch(`api/dashboard.php?period=${period}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.prioridades) {
                updatePrioridadeChart(data.prioridades);
            }
        })
        .catch(error => console.error('Erro:', error))
        .finally(() => { if (!silent) hideLoading(); });
}

function loadUltimosAtendimentos() {
    fetch('api/atendimentos.php?action=ultimos')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateUltimosAtendimentos(data.atendimentos);
            }
        })
        .catch(error => console.error('Erro:', error));
}