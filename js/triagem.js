// triagem.js - Versão Profissional

let currentFila = [];
let prioridadeAtual = null;

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    loadPacientes();
    loadFilaEspera();
    setupEventListeners();
    updateDateTime();
    setInterval(updateDateTime, 1000);
    setupSinaisVitaisListeners();
});

function setupEventListeners() {
    document.getElementById('triagemForm').addEventListener('submit', handleTriagemSubmit);
    document.getElementById('refreshFilaBtn').addEventListener('click', () => loadFilaEspera());
    document.getElementById('exportFilaBtn').addEventListener('click', exportFilaCSV);

    
    // Nível de dor
    const dorSlider = document.getElementById('dor');
    const dorValue = document.getElementById('dorValue');
    if (dorSlider) {
        dorSlider.addEventListener('input', function() {
            const value = this.value;
            let label = '';
            if (value == 0) label = '0 - Sem dor';
            else if (value <= 3) label = `${value} - Dor leve`;
            else if (value <= 6) label = `${value} - Dor moderada`;
            else if (value <= 8) label = `${value} - Dor forte`;
            else label = `${value} - Dor intensa`;
            dorValue.textContent = label;
        });
    }
}

function setupSinaisVitaisListeners() {
    const inputs = ['frequencia_cardiaca', 'temperatura', 'saturacao_oxigenio'];
    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', calcularPrioridadePreview);
    });
}

function calcularPrioridadePreview() {
    const fc = parseInt(document.getElementById('frequencia_cardiaca').value, 10) || 0;
    const temp = parseFloat(document.getElementById('temperatura').value) || 0;
    const sat = parseInt(document.getElementById('saturacao_oxigenio').value, 10) || 0;
    
    let prioridade = 'baixa';
    
    if (fc > 140 || sat < 85) {
        prioridade = 'emergencia';
    } else if (fc > 120 || sat < 90 || temp > 39) {
        prioridade = 'alta';
    } else if ((fc >= 100 && fc <= 120) || (temp >= 38 && temp <= 39)) {
        prioridade = 'media';
    }
    
    prioridadeAtual = prioridade;
    
    const preview = document.getElementById('priorityPreview');
    const priorityLabel = document.getElementById('priorityLabel');
    
    if (prioridade !== 'baixa') {
        preview.style.display = 'flex';
        const labels = {
            'emergencia': '🔴 EMERGÊNCIA - Atendimento imediato necessário!',
            'alta': '🟠 ALTA PRIORIDADE - Atendimento prioritário',
            'media': '🟡 MÉDIA PRIORIDADE - Necessita avaliação breve'
        };
        priorityLabel.textContent = labels[prioridade];
        priorityLabel.style.color = prioridade === 'emergencia' ? '#c0392b' : 
                                      prioridade === 'alta' ? '#e74c3c' : '#f39c12';
    } else {
        preview.style.display = 'none';
    }
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

async function loadPacientes() {
    try {
        const response = await fetch('api/pacientes.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('pacienteSelect');
            select.innerHTML = '<option value="">Selecione um paciente...</option>';
            data.pacientes.forEach(paciente => {
                select.innerHTML += `<option value="${paciente.id}">${escapeHtml(paciente.nome_completo)} - ${paciente.cpf || ''}</option>`;
            });
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro ao carregar pacientes', 'error');
    }
}

async function loadFilaEspera() {
    showLoading();
    
    try {
        const response = await fetch('api/triagem.php?action=fila');
        const data = await response.json();
        
        if (data.success) {
            currentFila = data.fila || [];
            renderFilaEspera();
            updateFilaStats();
        } else {
            showToast(data.erro || 'Erro ao carregar fila', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
    } finally {
        hideLoading();
    }
}

function renderFilaEspera() {
    const container = document.getElementById('filaContainer');
    
    if (!currentFila || currentFila.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h4>Fila de espera vazia</h4>
                <p>Nenhum paciente aguardando atendimento</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = currentFila.map(item => `
        <div class="fila-item ${item.prioridade}" onclick="verDetalhesTriagem(${item.id})">
            <div class="fila-info">
                <div class="fila-nome">
                    ${escapeHtml(item.paciente_nome)}
                </div>
                <div class="fila-detalhes">
                    <span><i class="fas fa-heartbeat"></i> ${item.pressao_arterial || '—'}</span>
                    <span><i class="fas fa-waveform"></i> ${item.frequencia_cardiaca || '—'} bpm</span>
                    <span><i class="fas fa-thermometer-half"></i> ${item.temperatura || '—'}°C</span>
                    <span><i class="fas fa-lungs"></i> SatO₂ ${item.saturacao_oxigenio || '—'}%</span>
                </div>
            </div>
            <div class="fila-priority">
                <span class="priority-badge-lg ${item.prioridade}">
                    ${getPriorityLabel(item.prioridade)}
                </span>
            </div>
            <div class="fila-tempo">
                <i class="fas fa-clock"></i>
                ${formatTempoEspera(item.data_hora_chegada)}
            </div>
            <div class="fila-actions" onclick="event.stopPropagation()">
                <button class="fila-action-btn atender" onclick="iniciarAtendimento(${item.id}, ${item.paciente_id})" title="Iniciar Atendimento">
                    <i class="fas fa-stethoscope"></i>
                </button>
                <button class="fila-action-btn reclassificar" onclick="abrirReclassificacao(${item.id})" title="Reclassificar Prioridade">
                    <i class="fas fa-chart-simple"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function updateFilaStats() {
    const stats = {
        total: currentFila.length,
        emergencia: currentFila.filter(i => i.prioridade === 'emergencia').length,
        alta: currentFila.filter(i => i.prioridade === 'alta').length,
        media: currentFila.filter(i => i.prioridade === 'media').length,
        baixa: currentFila.filter(i => i.prioridade === 'baixa').length
    };
    
    document.getElementById('filaTotal').textContent = stats.total;
    document.getElementById('filaEmergencia').textContent = stats.emergencia;
    document.getElementById('filaAlta').textContent = stats.alta;
    document.getElementById('filaMedia').textContent = stats.media;
    document.getElementById('filaBaixa').textContent = stats.baixa;
}

async function handleTriagemSubmit(e) {
    e.preventDefault();
    
    const paciente_id = document.getElementById('pacienteSelect').value;
    if (!paciente_id) {
        showToast('Selecione um paciente', 'error');
        return;
    }
    
    const fc = document.getElementById('frequencia_cardiaca').value;
    const temp = document.getElementById('temperatura').value;
    const sat = document.getElementById('saturacao_oxigenio').value;
    const fr = document.getElementById('frequencia_respiratoria').value;
    const gli = document.getElementById('glicemia').value;

    const formData = {
        paciente_id: paciente_id,
        pressao_arterial: document.getElementById('pressao_arterial').value || null,
        frequencia_cardiaca: fc !== '' ? parseInt(fc, 10) : null,
        temperatura: temp !== '' ? parseFloat(temp) : null,
        saturacao_oxigenio: sat !== '' ? parseInt(sat, 10) : null,
        sintomas: document.getElementById('sintomas').value,
        frequencia_respiratoria: fr !== '' ? parseInt(fr, 10) : null,
        glicemia: gli !== '' ? parseInt(gli, 10) : null,
        dor: parseInt(document.getElementById('dor').value, 10),
        prioridade: prioridadeAtual || 'baixa'
    };
    
    showLoading();
    
    try {
        const response = await fetch('api/triagem.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Triagem registrada com sucesso!', 'success');
            document.getElementById('triagemForm').reset();
            document.getElementById('dorValue').textContent = '0 - Sem dor';
            prioridadeAtual = null;
            document.getElementById('priorityPreview').style.display = 'none';
            closeModal('triagemFormModal');
            loadFilaEspera();
        } else {
            showToast(data.erro || 'Erro ao registrar triagem', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão', 'error');
    } finally {
        hideLoading();
    }
}

function verDetalhesTriagem(triagemId) {
    const triagem = currentFila.find(t => t.id === triagemId);
    if (!triagem) return;
    
    const modalBody = document.getElementById('triagemDetalhes');
    modalBody.innerHTML = `
        <div class="patient-details">
            <div class="detail-section">
                <h4><i class="fas fa-user"></i> Dados do Paciente</h4>
                <div class="detail-row">
                    <span class="detail-label">Nome:</span>
                    <span class="detail-value">${escapeHtml(triagem.paciente_nome)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">BI:</span>
                    <span class="detail-value">${triagem.cpf || '—'}</span>
                </div>
            </div>
            <div class="detail-section">
                <h4><i class="fas fa-heartbeat"></i> Sinais Vitais</h4>
                <div class="detail-row">
                    <span class="detail-label">Pressão Arterial:</span>
                    <span class="detail-value">${triagem.pressao_arterial || '—'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Frequência Cardíaca:</span>
                    <span class="detail-value">${triagem.frequencia_cardiaca || '—'} bpm</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Temperatura:</span>
                    <span class="detail-value">${triagem.temperatura || '—'}°C</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Saturação O₂:</span>
                    <span class="detail-value">${triagem.saturacao_oxigenio || '—'}%</span>
                </div>
            </div>
            <div class="detail-section full-width">
                <h4><i class="fas fa-notes-medical"></i> Sintomas</h4>
                <p>${escapeHtml(triagem.sintomas) || '—'}</p>
            </div>
            <div class="detail-section">
                <h4><i class="fas fa-chart-simple"></i> Classificação</h4>
                <div class="detail-row">
                    <span class="detail-label">Prioridade:</span>
                    <span class="detail-value">
                        <span class="priority-badge-lg ${triagem.prioridade}">
                            ${getPriorityLabel(triagem.prioridade)}
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Chegada:</span>
                    <span class="detail-value">${formatDateTime(triagem.data_hora_chegada)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tempo de espera:</span>
                    <span class="detail-value">${formatTempoEspera(triagem.data_hora_chegada)}</span>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('iniciarAtendimentoBtn').onclick = () => {
        closeModal('triagemModal');
        iniciarAtendimento(triagem.id, triagem.paciente_id);
    };
    
    openModal('triagemModal');
}

function iniciarAtendimento(triagemId, pacienteId) {
    // Redirecionar para página de atendimento
    window.location.href = `atendimento.php?triagem_id=${triagemId}&paciente_id=${pacienteId}`;
}

function abrirReclassificacao(triagemId) {
    document.getElementById('reclassificarTriagemId').value = triagemId;
    openModal('reclassificarModal');
}

async function confirmarReclassificacao() {
    const triagemId = document.getElementById('reclassificarTriagemId').value;
    const novaPrioridade = document.getElementById('novaPrioridade').value;
    const motivo = document.getElementById('motivoReclassificacao').value;
    
    showLoading();
    
    try {
        const response = await fetch('api/triagem.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'reclassificar',
                triagem_id: triagemId,
                prioridade: novaPrioridade,
                motivo: motivo
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Prioridade reclassificada com sucesso!', 'success');
            closeModal('reclassificarModal');
            loadFilaEspera();
        } else {
            showToast(data.erro || 'Erro ao reclassificar', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    } finally {
        hideLoading();
    }
}

function exportFilaCSV() {
    if (!currentFila || currentFila.length === 0) {
        showToast('Não há dados para exportar', 'error');
        return;
    }
    
    const data = currentFila.map(item => ({
        'Paciente': item.paciente_nome,
        'Prioridade': getPriorityLabel(item.prioridade),
        'Pressão Arterial': item.pressao_arterial,
        'Frequência Cardíaca': `${item.frequencia_cardiaca} bpm`,
        'Temperatura': `${item.temperatura}°C`,
        'SatO₂': `${item.saturacao_oxigenio}%`,
        'Chegada': formatDateTime(item.data_hora_chegada),
        'Tempo de Espera': formatTempoEspera(item.data_hora_chegada)
    }));
    
    const csv = convertToCSV(data);
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.href = url;
    link.setAttribute('download', `fila_triagem_${new Date().toISOString().slice(0,19)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    showToast('Fila exportada com sucesso!', 'success');
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-AO') + ' ' + date.toLocaleTimeString('pt-AO', {hour: '2-digit', minute:'2-digit'});
}

function formatTempoEspera(dataChegada) {
    const chegada = new Date(dataChegada);
    const agora = new Date();
    const diffMs = agora - chegada;
    const diffMin = Math.floor(diffMs / 60000);
    
    if (diffMin < 60) {
        return `${diffMin} min`;
    } else {
        const horas = Math.floor(diffMin / 60);
        const minutos = diffMin % 60;
        return `${horas}h ${minutos}min`;
    }
}

function getPriorityLabel(priority) {
    const labels = {
        'emergencia': 'EMERGÊNCIA',
        'alta': 'ALTA PRIORIDADE',
        'media': 'MÉDIA PRIORIDADE',
        'baixa': 'BAIXA PRIORIDADE'
    };
    return labels[priority] || priority;
}

function convertToCSV(data) {
    const headers = Object.keys(data[0]);
    const csvRows = [headers.join(',')];
    
    for (const row of data) {
        const values = headers.map(header => `"${String(row[header]).replace(/"/g, '""')}"`);
        csvRows.push(values.join(','));
    }
    
    return csvRows.join('\n');
}

// showToast, openModal, closeModal, showLoading, hideLoading, escapeHtml
// são definidos globalmente em main.js