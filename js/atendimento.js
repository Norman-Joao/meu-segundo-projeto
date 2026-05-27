// atendimento.js - Versão Profissional

document.addEventListener('DOMContentLoaded', function() {
    carregarTriagensAtivas();
    carregarStats();
    setupEventListeners();
    updateDateTime();
    setInterval(updateDateTime, 1000);

    handleViewParam();
});

function setupEventListeners() {
    document.getElementById('atendimentoForm').addEventListener('submit', registrarAtendimento);
    document.getElementById('historicoSearchBtn').addEventListener('click', buscarHistorico);
    document.getElementById('historicoSearch').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') buscarHistorico();
    });

    document.getElementById('exportBtn')?.addEventListener('click', exportarAtendimentos);
    document.getElementById('printBtn')?.addEventListener('click', imprimirAtendimentos);
}

function updateDateTime() {
    const now = new Date();
    const dateElement = document.querySelector('.date-range span, #currentDate');
    if (dateElement) {
        dateElement.textContent = now.toLocaleDateString('pt-AO', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }
}

async function carregarStats() {
    try {
        const response = await fetch('api/triagem.php?action=fila');
        const data = await response.json();
        if (data.success) {
            const fila = data.fila || [];
            const emAtendimento = fila.filter(t => t.status === 'em_atendimento').length;
            const aguardando = fila.filter(t => t.status === 'aguardando').length;
            document.getElementById('emAtendimentoCount').textContent = emAtendimento;
            document.getElementById('aguardandoCount').textContent = aguardando;
        }
    } catch (e) { console.error(e); }

    try {
        const res = await fetch('api/dashboard.php');
        const d = await res.json();
        if (d.success) {
            document.getElementById('atendimentosHoje').textContent = d.atendimentos_24h || 0;
        }
    } catch (e) { console.error(e); }
}

async function carregarTriagensAtivas() {
    try {
        const response = await fetch('api/triagem.php?action=fila');
        const data = await response.json();
        const select = document.getElementById('triagemSelect');
        if (data.success) {
            const ativas = (data.fila || []).filter(t => t.status === 'aguardando');
            select.innerHTML = '<option value="">Selecione um paciente...</option>' +
                ativas.map(t =>
                    `<option value="${t.id}">${t.paciente_nome || t.nome_completo} - ${(t.prioridade || '').toUpperCase()}</option>`
                ).join('');
            if (ativas.length === 0) {
                select.innerHTML = '<option value="">Nenhum paciente em atendimento...</option>';
            }
        }
    } catch (err) {
        console.error('Erro:', err);
    }
}

async function registrarAtendimento(e) {
    e.preventDefault();
    showLoading();

    const triagemSelect = document.getElementById('triagemSelect');
    const data = {
        triagem_id: parseInt(triagemSelect.value, 10) || 0,
        medico_responsavel: document.getElementById('medico_responsavel').value.trim(),
        diagnostico: document.getElementById('diagnostico').value.trim(),
        prescricao: document.getElementById('prescricao').value.trim(),
        exames_solicitados: document.getElementById('exames_solicitados').value.trim(),
        encaminhamento: document.getElementById('encaminhamento').value,
        pressao_arterial: document.getElementById('pressao_atendimento').value.trim(),
        frequencia_cardiaca: document.getElementById('frequencia_atendimento').value,
        temperatura: document.getElementById('temperatura_atendimento').value,
        saturacao_oxigenio: document.getElementById('saturacao_atendimento').value,
        cid_code: document.getElementById('cid_code').value.trim(),
        observacoes: document.getElementById('observacoes').value.trim(),
    };

    if (!data.triagem_id) {
        showToast('Selecione um paciente.', 'error');
        hideLoading();
        return;
    }

    try {
        const response = await fetch('api/atendimentos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.mensagem, 'success');
            document.getElementById('atendimentoForm').reset();
            closeModal('atendimentoFormModal');
            carregarTriagensAtivas();
            carregarStats();
        } else {
            showToast(result.erro || 'Erro ao registrar', 'error');
        }
    } catch (err) {
        showToast('Erro de conexão', 'error');
    } finally {
        hideLoading();
    }
}

async function buscarHistorico() {
    const search = document.getElementById('historicoSearch').value.trim();
    if (!search) {
        showToast('Digite o nome do paciente.', 'warning');
        return;
    }

    showLoading();
    try {
        const response = await fetch(`api/pacientes.php?search=${encodeURIComponent(search)}`);
        const data = await response.json();
        const div = document.getElementById('historicoPacientes');

        if (data.success && data.pacientes.length > 0) {
            div.innerHTML = data.pacientes.map(p =>
                `<button class="btn btn-sm btn-outline" onclick="carregarHistorico(${p.id}, '${escapeHtml(p.nome_completo).replace(/'/g, "\\'")}')" style="margin:3px;">
                    <i class="fas fa-user"></i> ${escapeHtml(p.nome_completo)}
                </button>`
            ).join('');
        } else {
            div.innerHTML = '<p style="color:var(--text-secondary);padding:10px;">Nenhum paciente encontrado.</p>';
            document.getElementById('historicoContainer').style.display = 'none';
        }
    } catch (err) {
        showToast('Erro na busca', 'error');
    } finally {
        hideLoading();
    }
}

async function carregarHistorico(pacienteId, nome) {
    showLoading();
    try {
        const response = await fetch(`api/atendimentos.php?paciente_id=${pacienteId}`);
        const data = await response.json();
        const container = document.getElementById('historicoContainer');
        const title = document.getElementById('historicoTitle');
        const tbody = document.getElementById('atendimentosTableBody');

        if (data.success) {
            title.innerHTML = `<i class="fas fa-file-medical"></i> Histórico de ${escapeHtml(nome)}`;

            if (data.atendimentos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state"><p>Nenhum atendimento registrado.</p></td></tr>';
            } else {
                tbody.innerHTML = data.atendimentos.map(a => `
                    <tr>
                        <td>${formatDateTime(a.data_hora_atendimento)}</td>
                        <td><strong>${a.paciente_nome || nome}</strong></td>
                        <td>${escapeHtml(a.medico_responsavel)}</td>
                        <td>${truncate(escapeHtml(a.diagnostico), 60)}</td>
                        <td>${a.encaminhamento || '—'}</td>
                        <td><span class="status-badge status-finalizado">Finalizado</span></td>
                        <td>
                            <button class="action-btn view" onclick="verDetalhes(${a.id})" title="Detalhes">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            }
            container.style.display = 'block';
        }
    } catch (err) {
        showToast('Erro ao carregar histórico', 'error');
    } finally {
        hideLoading();
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
                <div class="detail-section">
                    <h4><i class="fas fa-user"></i> Paciente</h4>
                    <p><strong>${escapeHtml(a.paciente_nome || '')}</strong></p>
                </div>
                <div class="detail-section">
                    <h4><i class="fas fa-user-md"></i> Médico Responsável</h4>
                    <p>${escapeHtml(a.medico_responsavel)}</p>
                </div>
                <div class="detail-grid">
                    <div class="detail-section">
                        <h4><i class="fas fa-calendar"></i> Data/Hora</h4>
                        <p>${formatDateTime(a.data_hora_atendimento)}</p>
                    </div>
                    <div class="detail-section">
                        <h4><i class="fas fa-ambulance"></i> Encaminhamento</h4>
                        <p>${a.encaminhamento || 'Não informado'}</p>
                    </div>
                </div>
                <div class="detail-section">
                    <h4><i class="fas fa-file-prescription"></i> Diagnóstico</h4>
                    <p>${escapeHtml(a.diagnostico) || '—'}</p>
                </div>
                <div class="detail-grid">
                    <div class="detail-section">
                        <h4><i class="fas fa-capsules"></i> Prescrição</h4>
                        <p>${escapeHtml(a.prescricao) || 'Nenhuma'}</p>
                    </div>
                    <div class="detail-section">
                        <h4><i class="fas fa-flask"></i> Exames</h4>
                        <p>${escapeHtml(a.exames_solicitados) || 'Nenhum'}</p>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('imprimirAtendimentoBtn').onclick = () => window.print();
        openModal('modalAtendimento');
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        hideLoading();
    }
}

async function exportarAtendimentos() {
    try {
        const response = await fetch('api/atendimentos.php');
        const data = await response.json();
        if (data.success && data.atendimentos) {
            const csv = convertToCSV(data.atendimentos.map(a => ({
                'Data': formatDateTime(a.data_hora_atendimento),
                'Paciente': a.paciente_nome,
                'Médico': a.medico_responsavel,
                'Diagnóstico': a.diagnostico,
                'Encaminhamento': a.encaminhamento || ''
            })));
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `atendimentos_${new Date().toISOString().slice(0,10)}.csv`;
            link.click();
            URL.revokeObjectURL(link.href);
            showToast('Exportado com sucesso!', 'success');
        }
    } catch (e) {
        showToast('Erro ao exportar', 'error');
    }
}

function imprimirAtendimentos() {
    const tbody = document.getElementById('atendimentosTableBody');
    if (!tbody || !tbody.querySelector('td:not(.empty-state)')) {
        showToast('Não há dados para imprimir', 'error');
        return;
    }
    const html = document.getElementById('historicoContainer').outerHTML;
    const title = document.querySelector('#historicoTitle')?.textContent || 'Histórico de Atendimentos';
    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html><html lang="pt-AO"><head><meta charset="UTF-8"><title>${title}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
      body { font-family: 'Inter', Arial, sans-serif; margin: 40px; color: #1a1a2e; }
      h2 { color: #0ea5e9; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; }
      table { width: 100%; border-collapse: collapse; margin-top: 20px; }
      th { background: #0ea5e9; color: white; padding: 10px; text-align: left; }
      td { padding: 8px 10px; border-bottom: 1px solid #eee; }
      .footer { margin-top: 30px; text-align: center; color: #999; font-size: 11px; border-top: 1px solid #eee; padding-top: 15px; }
    </style></head><body><h2>${title}</h2><p>Gerado em: ${new Date().toLocaleString('pt-AO')}</p>${html.replace(/<button[^>]*>.*?<\/button>/gs, '')}
    <div class="footer">Hospital do Ramiros — Luanda, Angola</div></body></html>`);
    win.document.close();
    win.print();
}

// Utilitários
function formatDateTime(dateString) {
    if (!dateString) return '—';
    const d = new Date(dateString);
    return d.toLocaleDateString('pt-AO') + ' ' + d.toLocaleTimeString('pt-AO', {hour:'2-digit',minute:'2-digit'});
}

function truncate(str, len) {
    if (!str) return '—';
    return str.length > len ? str.substring(0, len) + '...' : str;
}

// escapeHtml, convertToCSV - definidos em main.js

function handleViewParam() {
    const params = new URLSearchParams(window.location.search);
    const viewId = parseInt(params.get('view'), 10);
    if (viewId) {
        verDetalhes(viewId);
    }
}


