// relatorios.js - Sistema de Relatórios do Hospital do Ramiros

let currentPeriod = 7;
let currentPage = 1;
let itemsPerPage = 10;
let totalAtendimentos = 0;
let atendimentosData = [];

let atendimentosChart, prioridadeChart, convenioChart, horariosChart;

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    loadRelatorios();
    setupEventListeners();
    updateDateTime();
    setInterval(updateDateTime, 1000);
});

function setupEventListeners() {
    // Filtros de período
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentPeriod = parseInt(this.dataset.period);
            loadRelatorios();
        });
    });
    
    // Botão aplicar data personalizada
    document.getElementById('applyDateBtn')?.addEventListener('click', () => {
        const inicio = document.getElementById('dataInicio').value;
        const fim = document.getElementById('dataFim').value;
        if (inicio && fim) {
            loadRelatoriosPersonalizados(inicio, fim);
        } else {
            showToast('Selecione as datas de início e fim', 'error');
        }
    });
    
    // Exportar atendimentos
    document.getElementById('exportAtendimentosBtn')?.addEventListener('click', exportAtendimentosCSV);
    document.getElementById('exportPDFBtn')?.addEventListener('click', exportHTML);
    
    // Relatórios rápidos
    document.querySelectorAll('.report-card').forEach(card => {
        card.addEventListener('click', () => {
            const reportType = card.dataset.report;
            gerarRelatorioRapido(reportType);
        });
    });
    
    // Botões do modal de relatório
    document.getElementById('printReportBtn')?.addEventListener('click', printReport);
    document.getElementById('downloadReportBtn')?.addEventListener('click', downloadReport);
    
    // Paginação
    document.getElementById('prevPageBtn')?.addEventListener('click', () => changePage(-1));
    document.getElementById('nextPageBtn')?.addEventListener('click', () => changePage(1));
    
    // Exportar gráficos
    document.querySelectorAll('.export-chart-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const chartType = this.dataset.chart;
            exportChartAsImage(chartType);
        });
    });
}

function updateDateTime() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric'
    };
    const dateElement = document.getElementById('currentDate');
    if (dateElement) {
        dateElement.textContent = now.toLocaleDateString('pt-AO', options);
    }
}

async function loadRelatorios() {
    showLoading();
    
    try {
        const response = await fetch(`api/relatorios.php?period=${currentPeriod}`);
        const data = await response.json();
        
        if (data.success) {
            updateSummaryCards(data.summary);
            updateCharts(data);
            updateAtendimentosTable(data.atendimentos);
        } else {
            showToast(data.erro || 'Erro ao carregar relatórios', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão com o servidor', 'error');
    } finally {
        hideLoading();
    }
}

async function loadRelatoriosPersonalizados(inicio, fim) {
    showLoading();
    
    try {
        const response = await fetch(`api/relatorios.php?inicio=${inicio}&fim=${fim}`);
        const data = await response.json();
        
        if (data.success) {
            updateSummaryCards(data.summary);
            updateCharts(data);
            updateAtendimentosTable(data.atendimentos);
        } else {
            showToast(data.erro || 'Erro ao carregar relatórios', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    } finally {
        hideLoading();
    }
}

function updateSummaryCards(summary) {
    // Animar valores
    animateValue('totalAtendimentos', 0, summary.total_atendimentos || 0, 500);
    animateValue('totalPacientes', 0, summary.total_pacientes || 0, 500);
    animateValue('mediaDiaria', 0, summary.media_diaria || 0, 500);
    animateValue('tempoMedio', 0, summary.tempo_medio_espera || 0, 500);
    
    // Atualizar tendências
    updateTrend('trendAtendimentos', summary.trend_atendimentos);
    updateTrend('trendPacientes', summary.trend_pacientes);
    updateTrend('trendMedia', summary.trend_media);
    updateTrend('trendTempo', summary.trend_tempo, true);
}

function animateValue(elementId, start, end, duration) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const startTime = performance.now();
    const updateValue = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        let value = Math.floor(start + (end - start) * progress);
        
        if (elementId === 'tempoMedio') {
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

function updateTrend(elementId, value, isInverse = false) {
    const element = document.getElementById(elementId);
    if (!element || value === undefined) return;
    
    const isPositive = value > 0;
    const trendClass = (isPositive && !isInverse) || (!isPositive && isInverse) ? 'trend-up' : 'trend-down';
    const icon = (isPositive && !isInverse) || (!isPositive && isInverse) ? 'fa-arrow-up' : 'fa-arrow-down';
    const text = `${Math.abs(value)}% vs período anterior`;
    
    element.innerHTML = `<i class="fas ${icon}"></i><span>${text}</span>`;
    element.className = `summary-trend ${trendClass}`;
}

function updateCharts(data) {
    updateAtendimentosChart(data.atendimentos_por_dia || []);
    updatePrioridadeChart(data.prioridades || {});
    updateConvenioChart(data.convenios || {});
    updateHorariosChart(data.horarios || []);
}

function updateAtendimentosChart(dailyData) {
    const ctx = document.getElementById('atendimentosChart')?.getContext('2d');
    if (!ctx) return;
    
    const labels = dailyData.map(d => d.data);
    const values = dailyData.map(d => d.total);
    
    if (atendimentosChart) atendimentosChart.destroy();
    
    atendimentosChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Atendimentos',
                data: values,
                borderColor: '#0056b3',
                backgroundColor: 'rgba(0, 86, 179, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#0056b3'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => `${ctx.raw} atendimentos` } }
            },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'Quantidade' } } }
        }
    });
}

function updatePrioridadeChart(prioridades) {
    const ctx = document.getElementById('prioridadeChart')?.getContext('2d');
    if (!ctx) return;
    
    if (prioridadeChart) prioridadeChart.destroy();
    
    prioridadeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Emergência', 'Alta', 'Média', 'Baixa'],
            datasets: [{
                data: [
                    prioridades.emergencia || 0,
                    prioridades.alta || 0,
                    prioridades.media || 0,
                    prioridades.baixa || 0
                ],
                backgroundColor: ['#c0392b', '#e74c3c', '#f39c12', '#27ae60'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function updateConvenioChart(convenios) {
    const ctx = document.getElementById('convenioChart')?.getContext('2d');
    if (!ctx) return;
    
    if (convenioChart) convenioChart.destroy();
    
    convenioChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(convenios),
            datasets: [{
                label: 'Atendimentos',
                data: Object.values(convenios),
                backgroundColor: '#00b4d8',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

function updateHorariosChart(horarios) {
    const ctx = document.getElementById('horariosChart')?.getContext('2d');
    if (!ctx) return;
    
    const horas = Array.from({length: 24}, (_, i) => `${i}:00`);
    const dados = horarios.length === 24 ? horarios : Array(24).fill(0);
    
    if (horariosChart) horariosChart.destroy();
    
    horariosChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: horas,
            datasets: [{
                label: 'Atendimentos',
                data: dados,
                backgroundColor: '#0056b3',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Quantidade' } },
                x: { ticks: { maxRotation: 45, autoSkip: true, maxTicksLimit: 12 } }
            }
        }
    });
}

function updateAtendimentosTable(atendimentos) {
    atendimentosData = atendimentos || [];
    totalAtendimentos = atendimentosData.length;
    renderTablePage();
    updatePagination();
}

function renderTablePage() {
    const tbody = document.getElementById('atendimentosTableBody');
    const start = (currentPage - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    const pageData = atendimentosData.slice(start, end);
    
    if (pageData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>Nenhum atendimento encontrado</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = pageData.map(att => `
        <tr>
            <td>${formatDate(att.data_atendimento)}</td>
            <td><strong>${escapeHtml(att.paciente_nome)}</strong></td>
            <td><span class="priority-badge priority-${att.prioridade}">${getPriorityLabel(att.prioridade)}</span></td>
            <td>${att.medico_responsavel || '—'}</td>
            <td>${truncate(att.diagnostico, 40) || '—'}</td>
            <td><span class="status-badge status-${att.status}">${getStatusLabel(att.status)}</span></td>
        </tr>
    `).join('');
}

function updatePagination() {
    const totalPages = Math.ceil(totalAtendimentos / itemsPerPage);
    const pageInfo = document.getElementById('pageInfo');
    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');
    
    pageInfo.textContent = `Página ${currentPage} de ${totalPages || 1}`;
    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;
}

function changePage(delta) {
    const newPage = currentPage + delta;
    const totalPages = Math.ceil(totalAtendimentos / itemsPerPage);
    
    if (newPage >= 1 && newPage <= totalPages) {
        currentPage = newPage;
        renderTablePage();
        updatePagination();
    }
}

function exportAtendimentosCSV() {
    if (atendimentosData.length === 0) {
        showToast('Não há dados para exportar', 'error');
        return;
    }
    
    const data = atendimentosData.map(att => ({
        'Data': formatDate(att.data_atendimento),
        'Paciente': att.paciente_nome,
        'Prioridade': getPriorityLabel(att.prioridade),
        'Médico': att.medico_responsavel || '—',
        'Diagnóstico': att.diagnostico || '—',
        'Prescrição': att.prescricao || '—',
        'Status': getStatusLabel(att.status)
    }));
    
    const csv = convertToCSV(data);
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.href = url;
    link.setAttribute('download', `atendimentos_${new Date().toISOString().slice(0,19)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    showToast('Relatório exportado com sucesso!', 'success');
}

function exportHTML() {
    if (atendimentosData.length === 0) {
        showToast('Não há dados para exportar', 'error');
        return;
    }

    const now = new Date();
    const dataStr = now.toLocaleDateString('pt-AO') + ' ' + now.toLocaleTimeString('pt-AO', {hour:'2-digit',minute:'2-digit'});
    const title = 'Relatório de Atendimentos - Hospital do Ramiros';

    let html = `<!DOCTYPE html>
<html lang="pt-AO">
<head><meta charset="UTF-8"><title>${title}</title>
<style>
  body { font-family: 'Inter', Arial, sans-serif; margin: 40px; color: #1a1a2e; }
  h1 { color: #0ea5e9; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; }
  .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
  .header small { color: #666; }
  table { width: 100%; border-collapse: collapse; margin-top: 20px; }
  th { background: #0ea5e9; color: white; padding: 10px; text-align: left; font-size: 13px; }
  td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 13px; }
  tr:hover { background: #f8fafc; }
  .footer { margin-top: 30px; text-align: center; color: #999; font-size: 11px; border-top: 1px solid #eee; padding-top: 15px; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
  .badge-emergencia { background: #fef2f2; color: #dc2626; }
  .badge-alta { background: #fff7ed; color: #ea580c; }
  .badge-media { background: #fefce8; color: #ca8a04; }
  .badge-baixa { background: #f0fdf4; color: #16a34a; }
</style></head>
<body>
  <div class="header">
    <div>
      <h1>${title}</h1>
      <small>Gerado em: ${dataStr}</small>
    </div>
  </div>
  <p><strong>Total de registros:</strong> ${atendimentosData.length}</p>
  <table>
    <thead><tr><th>Data</th><th>Paciente</th><th>Prioridade</th><th>Médico</th><th>Diagnóstico</th></tr></thead>
    <tbody>
      ${atendimentosData.map(att => `
        <tr>
          <td>${formatDate(att.data_atendimento)}</td>
          <td>${escapeHtml(att.paciente_nome)}</td>
          <td><span class="badge badge-${att.prioridade}">${getPriorityLabel(att.prioridade)}</span></td>
          <td>${att.medico_responsavel || '—'}</td>
          <td>${att.diagnostico || '—'}</td>
        </tr>
      `).join('')}
    </tbody>
  </table>
  <div class="footer">
    Hospital do Ramiros — Luanda, Angola &bull; contato@hospitaldoramiros.co.ao
  </div>
</body></html>`;

    const blob = new Blob([html], { type: 'text/html;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.href = url;
    link.setAttribute('download', `relatorio_atendimentos_${new Date().toISOString().slice(0,10)}.html`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    showToast('Relatório exportado com sucesso!', 'success');
}

function exportChartAsImage(chartType) {
    let chart;
    let filename;
    
    switch(chartType) {
        case 'atendimentos': chart = atendimentosChart; filename = 'grafico_atendimentos'; break;
        case 'prioridade': chart = prioridadeChart; filename = 'grafico_prioridades'; break;
        case 'convenio': chart = convenioChart; filename = 'grafico_convenios'; break;
        case 'horarios': chart = horariosChart; filename = 'grafico_horarios'; break;
        default: return;
    }
    
    if (chart) {
        const link = document.createElement('a');
        link.download = `${filename}.png`;
        link.href = chart.canvas.toDataURL();
        link.click();
        showToast('Gráfico exportado com sucesso!', 'success');
    }
}

function gerarRelatorioRapido(tipo) {
    showLoading();
    
    fetch(`api/relatorios.php?tipo=${tipo}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mostrarRelatorioModal(tipo, data);
            } else {
                showToast('Erro ao gerar relatório', 'error');
            }
        })
        .catch(error => showToast('Erro de conexão', 'error'))
        .finally(() => hideLoading());
}

function mostrarRelatorioModal(tipo, data) {
    const modalBody = document.getElementById('reportModalBody');
    let html = '';
    
    switch(tipo) {
        case 'pacientes':
            html = gerarRelatorioPacientes(data);
            break;
        case 'leitos':
            html = gerarRelatorioLeitos(data);
            break;
        case 'triagem':
            html = gerarRelatorioTriagem(data);
            break;
        case 'financeiro':
            html = gerarRelatorioFinanceiro(data);
            break;
    }
    
    modalBody.innerHTML = html;
    openModal('reportModal');
}

function printReport() {
    const content = document.getElementById('reportModalBody').innerHTML;
    if (!content) return;
    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html><html lang="pt-AO"><head><meta charset="UTF-8"><title>Hospital do Ramiros - Relatório</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
      body { font-family: 'Inter', Arial, sans-serif; margin: 40px; color: #1a1a2e; }
      h2 { color: #0ea5e9; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; }
      table { width: 100%; border-collapse: collapse; margin-top: 20px; }
      th { background: #0ea5e9; color: white; padding: 10px; text-align: left; font-size: 13px; }
      td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 13px; }
      .stats-summary { display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap; }
      .stats-summary div { background: #f8fafc; padding: 15px; border-radius: 8px; flex: 1; min-width: 150px; font-weight: 600; }
      .report-content { max-width: 1000px; margin: 0 auto; }
      .footer { margin-top: 30px; text-align: center; color: #999; font-size: 11px; border-top: 1px solid #eee; padding-top: 15px; }
    </style></head><body><div class="report-content">${content}</div>
    <div class="footer">Hospital do Ramiros — Luanda, Angola</div></body></html>`);
    win.document.close();
    win.print();
}

function downloadReport() {
    const content = document.getElementById('reportModalBody').innerHTML;
    if (!content) return;
    const html = `<!DOCTYPE html><html lang="pt-AO"><head><meta charset="UTF-8"><title>Hospital do Ramiros - Relatório</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
      body { font-family: 'Inter', Arial, sans-serif; margin: 40px; color: #1a1a2e; }
      h2 { color: #0ea5e9; border-bottom: 2px solid #0ea5e9; padding-bottom: 10px; }
      table { width: 100%; border-collapse: collapse; margin-top: 20px; }
      th { background: #0ea5e9; color: white; padding: 10px; text-align: left; font-size: 13px; }
      td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 13px; }
      .stats-summary { display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap; }
      .stats-summary div { background: #f8fafc; padding: 15px; border-radius: 8px; flex: 1; min-width: 150px; font-weight: 600; }
      .report-content { max-width: 1000px; margin: 0 auto; }
      .footer { margin-top: 30px; text-align: center; color: #999; font-size: 11px; border-top: 1px solid #eee; padding-top: 15px; }
    </style></head><body><div class="report-content">${content}</div>
    <div class="footer">Hospital do Ramiros — Luanda, Angola</div></body></html>`;
    const blob = new Blob([html], { type: 'text/html;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `relatorio_${new Date().toISOString().slice(0,10)}.html`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
    showToast('Relatório descarregado com sucesso!', 'success');
}

function gerarRelatorioPacientes(data) {
    return `
        <div class="report-content">
            <h2>Relatório de Pacientes</h2>
            <p>Gerado em: ${new Date().toLocaleString('pt-AO')}</p>
            <table class="report-table">
                <thead>
                    <tr><th>Nome</th><th>BI</th><th>Telefone</th><th>Convênio</th><th>Último Atendimento</th></tr>
                </thead>
                <tbody>
                    ${data.pacientes.map(p => `
                        <tr><td>${escapeHtml(p.nome_completo)}</td><td>${p.cpf}</td><td>${p.telefone}</td><td>${p.convenio || 'Particular'}</td><td>${formatDate(p.ultimo_atendimento)}</td></tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function gerarRelatorioLeitos(data) {
    return `
        <div class="report-content">
            <h2>Relatório de Ocupação de Leitos</h2>
            <p>Gerado em: ${new Date().toLocaleString()}</p>
            <div class="stats-summary">
                <div>Disponíveis: ${data.disponiveis}</div>
                <div>Ocupados: ${data.ocupados}</div>
                <div>Manutenção: ${data.manutencao}</div>
                <div>Taxa de Ocupação: ${data.taxa_ocupacao}%</div>
            </div>
            <table class="report-table">
                <thead><tr><th>Leito</th><th>Ala</th><th>Status</th><th>Paciente</th><th>Ocupado desde</th></tr></thead>
                <tbody>
                    ${data.leitos.map(l => `
                        <tr><td>#${l.numero_leito}</td><td>${l.ala}</td><td>${getStatusLabel(l.status)}</td><td>${l.paciente_nome || '—'}</td><td>${l.data_ocupacao || '—'}</td></tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function gerarRelatorioTriagem(data) {
    return `
        <div class="report-content">
            <h2>Relatório de Triagem</h2>
            <p>Gerado em: ${new Date().toLocaleString()}</p>
            <div class="stats-summary">
                <div>Total de Triagens: ${data.total}</div>
                <div>Tempo Médio de Espera: ${data.tempo_medio} min</div>
            </div>
            <table class="report-table">
                <thead><tr><th>Data</th><th>Paciente</th><th>Prioridade</th><th>Sinais Vitais</th><th>Sintomas</th></tr></thead>
                <tbody>
                    ${data.triagens.map(t => `
                        <tr>
                            <td>${formatDate(t.data_hora)}</td>
                            <td>${escapeHtml(t.paciente_nome)}</td>
                            <td>${getPriorityLabel(t.prioridade)}</td>
                            <td>PA:${t.pressao_arterial} | FC:${t.frequencia_cardiaca} | Sat:${t.saturacao_oxigenio}%</td>
                            <td>${truncate(t.sintomas, 30)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function gerarRelatorioFinanceiro(data) {
    return `
        <div class="report-content">
            <h2>Relatório Financeiro</h2>
            <p>Gerado em: ${new Date().toLocaleString()}</p>
            <div class="stats-summary">
                <div>Total de Atendimentos: ${data.total_atendimentos}</div>
                <div>Particular: ${data.particular} (${data.percent_particular}%)</div>
                <div>Convênios: ${data.convenios_total} (${data.percent_convenios}%)</div>
            </div>
            <h3>Distribuição por Convênio</h3>
            <table class="report-table">
                <thead><tr><th>Convênio</th><th>Atendimentos</th><th>Percentual</th></tr></thead>
                <tbody>
                    ${Object.entries(data.detalhe_convenios).map(([key, value]) => `
                        <tr><td>${escapeHtml(key)}</td><td>${value}</td><td>${((value/data.total_atendimentos)*100).toFixed(1)}%</td></tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function formatDate(dateString) {
    if (!dateString) return '—';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-AO');
}

function getPriorityLabel(priority) {
    const labels = { 'emergencia': 'Emergência', 'alta': 'Alta', 'media': 'Média', 'baixa': 'Baixa' };
    return labels[priority] || priority;
}

function getStatusLabel(status) {
    const labels = { 'aguardando': 'Aguardando', 'em_atendimento': 'Em Atendimento', 'finalizado': 'Finalizado' };
    return labels[status] || status;
}

function truncate(str, length) {
    if (!str) return '—';
    return str.length > length ? str.substring(0, length) + '...' : str;
}

// escapeHtml, convertToCSV, showToast, openModal, closeModal, showLoading, hideLoading - definidos em main.js