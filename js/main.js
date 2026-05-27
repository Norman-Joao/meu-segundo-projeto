/**
 * main.js - Funções compartilhadas do sistema
 * Hospital do Ramiros - Gestão Hospitalar
 */

// --- CSRF Token global ---
let CSRF_TOKEN = localStorage.getItem('csrf_token') || '';

// Injeta token CSRF em todas as requisições POST/PUT/DELETE
(function patchFetch() {
    const original = window.fetch;
    window.fetch = function(url, opts = {}) {
        opts = opts || {};
        const method = (opts.method || 'GET').toUpperCase();
        if (method !== 'GET' && CSRF_TOKEN) {
            opts.headers = opts.headers || {};
            if (opts.headers instanceof Headers) {
                opts.headers.set('X-CSRF-Token', CSRF_TOKEN);
            } else {
                opts.headers['X-CSRF-Token'] = CSRF_TOKEN;
            }
        }
        return original.call(this, url, opts);
    };
})();

// --- Verificação de autenticação ---
(async function checkAuth() {
    try {
        const res = await fetch('api/check_session.php');
        const data = await res.json();
        if (!data.logado) {
            window.location.href = 'login.html';
            return;
        }
        CSRF_TOKEN = data.csrf_token || '';
        if (CSRF_TOKEN) localStorage.setItem('csrf_token', CSRF_TOKEN);
        if (data.nome_exibicao) {
            const el = document.getElementById('userName');
            if (el) el.textContent = data.nome_exibicao;
        }
        // Preenche informação do médico/usuário no header
        const doctorName = document.getElementById('doctorName');
        const doctorTitle = document.getElementById('doctorTitle');
        if (doctorName) doctorName.textContent = data.nome_exibicao || 'Usuário';
        if (doctorTitle) {
            const niveis = { admin: 'Administrador', medico: 'Médico', enfermeiro: 'Enfermeiro(a)', recepcionista: 'Recepcionista' };
            doctorTitle.textContent = niveis[data.nivel] || 'Usuário';
        }
    } catch (e) {
        // Se não conseguir verificar, redireciona
        window.location.href = 'login.html';
    }
})();

// --- Sidebar toggle (mobile) ---
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
        // Fecha sidebar ao clicar fora (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }

    // Logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            try {
                await fetch('api/logout.php');
            } catch (err) { /* ignore */ }
            localStorage.removeItem('csrf_token');
            CSRF_TOKEN = '';
            window.location.href = 'login.html';
        });
    }
});

/**
 * Exibe notificação toast
 */
function showToast(message, type = 'info') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    const msg = escapeHtml(message);
    toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><div class="toast-content">${msg}</div><button class="toast-close"><i class="fas fa-xmark"></i></button>`;
    toast.querySelector('.toast-close').addEventListener('click', () => toast.remove());
    container.appendChild(toast);

    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }
    }, 4000);
}

/**
 * Abre modal pelo ID
 */
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

/**
 * Fecha modal pelo ID
 */
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

/**
 * Mostra/esconde loading overlay
 */
function showLoading(show = true) {
    const el = document.getElementById('loadingOverlay');
    if (el) {
        el.classList.toggle('active', show);
    }
}
function hideLoading() {
    const el = document.getElementById('loadingOverlay');
    if (el) {
        el.classList.remove('active');
    }
}

/**
 * Formata BI: 000000000AA000
 */
function formatBI(value) {
    return value.toUpperCase().replace(/[^0-9A-Z]/g, '').slice(0, 14);
}

/**
 * Formata telefone Angola: +244 XXX XXX XXX
 */
function formatPhone(value) {
    let digits = value.replace(/[^0-9]/g, '');
    if (digits.startsWith('244')) {
        digits = digits.slice(3);
    }
    digits = digits.slice(0, 9);
    if (digits.length >= 7) {
        return digits.replace(/(\d{3})(\d{3})(\d{3})/, '+244 $1 $2 $3');
    }
    return digits ? '+244 ' + digits : '';
}

/**
 * Valida BI angolano (9 dígitos + 2 letras + 3 dígitos)
 */
function validateBI(bi) {
    const val = bi.replace(/[^0-9A-Za-z]/g, '').toUpperCase();
    if (val.length < 10 || val.length > 14) return false;
    return /^\d{9}[A-Z]{2}\d{3}$/.test(val);
}

/**
 * Busca genérica via API
 */
async function apiFetch(url, options = {}) {
    const headers = options.headers || {};
    if (options.method && options.method !== 'GET') {
        headers['X-CSRF-Token'] = CSRF_TOKEN;
    }
    const config = {
        headers: { 'Content-Type': 'application/json', ...headers },
        ...options,
    };
    if (config.body && typeof config.body === 'object') {
        config.body = JSON.stringify(config.body);
    }
    const res = await fetch(url, config);
    const data = await res.json();
    if (!res.ok && data.erro) {
        throw new Error(data.erro);
    }
    return data;
}

/**
 * Escape HTML para prevenir XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Converte array de objetos para CSV
 */
function convertToCSV(data) {
    if (!data || !data.length) return '';
    const headers = Object.keys(data[0]);
    const rows = [headers.join(',')];
    for (const row of data) {
        rows.push(headers.map(h => `"${String(row[h]).replace(/"/g, '""')}"`).join(','));
    }
    return rows.join('\n');
}

// --- Notificações ---
async function carregarNotificacoes() {
    try {
        const response = await fetch('api/notificacoes.php');
        const data = await response.json();
        if (!data.success) return;

        const badge = document.querySelector('.badge');
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown) return;

        if (badge) badge.textContent = data.total;

        const list = dropdown.querySelector('.notification-list');
        if (!list) return;

        if (data.notificacoes.length === 0) {
            list.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-check-circle"></i>
                    <p>Nenhuma notificação</p>
                </div>`;
            return;
        }

        list.innerHTML = data.notificacoes.map(n => `
            <div class="notification-item">
                <div class="notification-item-icon" style="background:${escapeHtml(n.cor)}">
                    <i class="fas ${escapeHtml(n.icone)}"></i>
                </div>
                <div class="notification-item-content">
                    <p>${escapeHtml(n.mensagem)}</p>
                    <small>${formatarTempoNotificacao(n.data)}</small>
                </div>
                <span class="notification-item-priority" style="color:${escapeHtml(n.cor)}">${escapeHtml(n.prioridade)}</span>
            </div>
        `).join('');
    } catch (e) {
        console.error('Erro notificacoes:', e);
    }
}

function formatarTempoNotificacao(dataString) {
    const data = new Date(dataString);
    const agora = new Date();
    const diffMs = agora - data;
    const diffMin = Math.floor(diffMs / 60000);
    if (diffMin < 1) return 'Agora mesmo';
    if (diffMin < 60) return `${diffMin} min atrás`;
    const horas = Math.floor(diffMin / 60);
    if (horas < 24) return `${horas}h atrás`;
    return data.toLocaleDateString('pt-AO');
}

document.addEventListener('DOMContentLoaded', function() {
    const bell = document.querySelector('.notification-badge');
    const dropdown = document.getElementById('notificationDropdown');
    if (bell && dropdown) {
        bell.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('active');
            carregarNotificacoes();
        });
        document.addEventListener('click', function() {
            dropdown.classList.remove('active');
        });
        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
        carregarNotificacoes();
        setInterval(carregarNotificacoes, 30000);
    }
});

// --- Auto-format e validação de BI e Telefone ---
document.addEventListener('input', function(e) {
    if (e.target.id === 'telefone' || e.target.id === 'telefone_emergencia') {
        e.target.value = formatPhone(e.target.value);
    }
    if (e.target.id === 'cpf') {
        const raw = formatBI(e.target.value);
        e.target.value = raw;
        const isValid = validateBI(raw);
        e.target.classList.toggle('field-valid', isValid);
        e.target.classList.toggle('field-invalid', !isValid && raw.length > 0);
    }
});
