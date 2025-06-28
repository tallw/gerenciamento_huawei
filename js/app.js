// Sistema de Gerenciamento ONU - JavaScript Principal

// Configurações globais
let potenciaChart = null;
let potenciaData = [];

// Inicialização do sistema
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    loadConfigAtiva();
    loadClientes();
    loadPerfis();
    loadConfiguracoes();
    setupEventListeners();
});

// Inicializar aplicação
function initializeApp() {
    // Mostrar seção dashboard por padrão
    showSection('dashboard');
    
    // Configurar navegação
    setupNavigation();
    
    // Inicializar gráfico de potência
    initializePotenciaChart();
}

// Configurar navegação entre seções
function setupNavigation() {
    const navLinks = document.querySelectorAll('[data-section]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            showSection(section);
            
            // Atualizar estado ativo
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
}

// Mostrar seção específica
function showSection(sectionName) {
    // Esconder todas as seções
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    
    // Mostrar seção selecionada
    const targetSection = document.getElementById(sectionName + '-section');
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Carregar dados específicos da seção
    switch(sectionName) {
        case 'dashboard':
            loadClientes();
            updateStats();
            break;
        case 'perfis':
            loadPerfis();
            break;
        case 'provisionar':
            loadPerfilsSelect();
            break;
        case 'configuracoes':
            loadConfiguracoes();
            loadConfigAtiva();
            break;
    }
}

// Configurar event listeners
function setupEventListeners() {
    // Form de provisionamento
    document.getElementById('provisionar-form').addEventListener('submit', function(e) {
        e.preventDefault();
        provisionarONU();
    });
    
    // Form de verificação de potência
    document.getElementById('verificar-form').addEventListener('submit', function(e) {
        e.preventDefault();
        verificarPotencia();
    });
    
    // Form de desprovisionamento
    document.getElementById('desprovisionar-form').addEventListener('submit', function(e) {
        e.preventDefault();
        desprovisionarONU();
    });
}

// Carregar configuração ativa da OLT
async function loadConfigAtiva() {
    try {
        const response = await fetch('api/config_olt.php?active=1');
        if (response.ok) {
            const config = await response.json();
            displayConfigAtiva(config);
        } else {
            document.getElementById('config-ativa-info').innerHTML = 
                '<p class="text-warning">Nenhuma configuração ativa encontrada</p>';
        }
    } catch (error) {
        console.error('Erro ao carregar configuração ativa:', error);
        document.getElementById('config-ativa-info').innerHTML = 
            '<p class="text-danger">Erro ao carregar configuração</p>';
    }
}

// Exibir configuração ativa
function displayConfigAtiva(config) {
    const statusClass = config.status_conexao === 'conectado' ? 'success' : 
                       config.status_conexao === 'erro' ? 'danger' : 'warning';
    
    const lastConnection = config.data_ultima_conexao ? 
        new Date(config.data_ultima_conexao).toLocaleString('pt-BR') : 'Nunca';
    
    document.getElementById('config-ativa-info').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>${config.nome_config}</h6>
                <p class="mb-1"><strong>IP:</strong> ${config.ip_olt}:${config.porta_olt}</p>
                <p class="mb-1"><strong>Usuário:</strong> ${config.usuario_olt}</p>
                <p class="mb-1"><strong>Tipo:</strong> ${config.tipo_conexao.toUpperCase()}</p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong>Status:</strong> 
                    <span class="badge bg-${statusClass}">${config.status_conexao}</span>
                </p>
                <p class="mb-1"><strong>Última Conexão:</strong> ${lastConnection}</p>
            </div>
        </div>
    `;
}

// Carregar todas as configurações
async function loadConfiguracoes() {
    try {
        const response = await fetch('api/config_olt.php');
        const configs = await response.json();
        updateConfigsTable(configs);
    } catch (error) {
        console.error('Erro ao carregar configurações:', error);
        showAlert('danger', 'Erro ao carregar configurações');
    }
}

// Atualizar tabela de configurações
function updateConfigsTable(configs) {
    const tbody = document.getElementById('configs-tbody');
    tbody.innerHTML = '';
    
    configs.forEach(config => {
        const statusClass = config.status_conexao === 'conectado' ? 'success' : 
                           config.status_conexao === 'erro' ? 'danger' : 'warning';
        
        const row = document.createElement('tr');
        row.className = 'fade-in-up';
        row.innerHTML = `
            <td><strong>${config.nome_config}</strong></td>
            <td>${config.ip_olt}</td>
            <td>${config.porta_olt}</td>
            <td>${config.usuario_olt}</td>
            <td><span class="badge bg-info">${config.tipo_conexao.toUpperCase()}</span></td>
            <td><span class="badge bg-${statusClass}">${config.status_conexao}</span></td>
            <td>${config.ativa ? '<i class="fas fa-check text-success"></i>' : ''}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="testarConexaoConfig(${config.id})">
                    <i class="fas fa-plug"></i>
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="ativarConfig(${config.id})" 
                        ${config.ativa ? 'disabled' : ''}>
                    <i class="fas fa-check"></i>
                </button>
                <button class="btn btn-sm btn-outline-warning" onclick="editarConfig(${config.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deletarConfig(${config.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Salvar configuração da OLT
async function salvarConfigOLT() {
    const configData = {
        id: document.getElementById('config-id').value,
        nome_config: document.getElementById('nome-config').value,
        ip_olt: document.getElementById('ip-olt').value,
        porta_olt: document.getElementById('porta-olt').value,
        usuario_olt: document.getElementById('usuario-olt').value,
        senha_olt: document.getElementById('senha-olt').value,
        tipo_conexao: document.getElementById('tipo-conexao').value
    };
    
    const method = configData.id ? 'PUT' : 'POST';
    const url = 'api/config_olt.php';
    
    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(configData)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showAlert('success', result.message);
            document.getElementById('config-form').reset();
            document.getElementById('config-id').value = '';
            bootstrap.Modal.getInstance(document.getElementById('configModal')).hide();
            loadConfiguracoes();
            loadConfigAtiva();
        } else {
            showAlert('danger', result.message);
        }
    } catch (error) {
        showAlert('danger', 'Erro de conexão');
    }
}

// Testar conexão da configuração ativa
async function testarConexaoAtiva() {
    try {
        const response = await fetch('api/config_olt.php?active=1');
        if (!response.ok) {
            showAlert('warning', 'Nenhuma configuração ativa encontrada');
            return;
        }
        
        const config = await response.json();
        testarConexaoConfig(config.id);
    } catch (error) {
        showAlert('danger', 'Erro ao buscar configuração ativa');
    }
}

// Testar conexão de uma configuração específica
async function testarConexaoConfig(configId) {
    try {
        const response = await fetch('api/testar_conexao.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ config_id: configId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
        } else {
            showAlert('danger', result.message);
        }
        
        // Recarregar configurações para atualizar status
        loadConfiguracoes();
        loadConfigAtiva();
        
    } catch (error) {
        showAlert('danger', 'Erro de conexão');
    }
}

// Ativar configuração
async function ativarConfig(configId) {
    try {
        const response = await fetch('api/config_olt.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: configId, set_active: true })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showAlert('success', result.message);
            loadConfiguracoes();
            loadConfigAtiva();
        } else {
            showAlert('danger', result.message);
        }
    } catch (error) {
        showAlert('danger', 'Erro de conexão');
    }
}

// Editar configuração
async function editarConfig(id) {
    try {
        const response = await fetch(`api/config_olt.php?id=${id}`);
        const config = await response.json();
        
        document.getElementById('config-id').value = config.id;
        document.getElementById('nome-config').value = config.nome_config;
        document.getElementById('ip-olt').value = config.ip_olt;
        document.getElementById('porta-olt').value = config.porta_olt;
        document.getElementById('usuario-olt').value = config.usuario_olt;
        document.getElementById('senha-olt').value = config.senha_olt;
        document.getElementById('tipo-conexao').value = config.tipo_conexao;
        
        new bootstrap.Modal(document.getElementById('configModal')).show();
    } catch (error) {
        showAlert('danger', 'Erro ao carregar configuração');
    }
}

// Deletar configuração
async function deletarConfig(id) {
    if (!confirm('Tem certeza que deseja deletar esta configuração?')) {
        return;
    }
    
    try {
        const response = await fetch('api/config_olt.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showAlert('success', result.message);
            loadConfiguracoes();
            loadConfigAtiva();
        } else {
            showAlert('danger', result.message);
        }
    } catch (error) {
        showAlert('danger', 'Erro de conexão');
    }
}

// Detectar ONUs
async function detectarONUs() {
    const interface_detectar = document.getElementById('interface-detectar').value;
    const resultDiv = document.getElementById('onus-detectadas');
    
    resultDiv.innerHTML = '<div class="loading"></div> Detectando ONUs...';
    
    try {
        const response = await fetch('api/detectar_onus.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                interface: interface_detectar || 'all'
            })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            displayONUsDetectadas(result);
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    } catch (error) {
        resultDiv.innerHTML = '<div class="alert alert-danger">Erro de conexão</div>';
    }
}

// Exibir ONUs detectadas
function displayONUsDetectadas(result) {
    const resultDiv = document.getElementById('onus-detectadas');
    
    if (result.total_onus === 0) {
        resultDiv.innerHTML = '<div class="alert alert-info">Nenhuma ONU detectada</div>';
        return;
    }
    
    let html = `
        <div class="alert alert-success">
            <strong>${result.total_onus} ONU(s) detectada(s)</strong> na interface ${result.interface_pesquisada}
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Interface</th>
                        <th>ONU ID</th>
                        <th>Serial Number</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    result.onus.forEach(onu => {
        html += `
            <tr>
                <td>${onu.interface}</td>
                <td>${onu.onu_id}</td>
                <td><strong>${onu.sn}</strong></td>
                <td>${onu.type}</td>
                <td><span class="badge bg-info">${onu.status}</span></td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="preencherDadosONU('${onu.sn}', '${onu.onu_id}', '${onu.interface}')">
                        <i class="fas fa-arrow-down"></i> Usar
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    resultDiv.innerHTML = html;
}

// Preencher dados da ONU detectada no formulário
function preencherDadosONU(sn, onu_id, interface) {
    document.getElementById('sn').value = sn;
    document.getElementById('onu-id').value = onu_id;
    document.getElementById('interface').value = interface;
    
    showAlert('success', 'Dados da ONU preenchidos automaticamente');
}

// Carregar lista de clientes
async function loadClientes() {
    try {
        const response = await fetch('api/clientes.php');
        const clientes = await response.json();
        
        updateClientesTable(clientes);
        updateStats(clientes);
    } catch (error) {
        console.error('Erro ao carregar clientes:', error);
        showAlert('danger', 'Erro ao carregar lista de clientes');
    }
}

// Atualizar tabela de clientes
function updateClientesTable(clientes) {
    const tbody = document.getElementById('onus-tbody');
    tbody.innerHTML = '';
    
    clientes.forEach(cliente => {
        const row = document.createElement('tr');
        row.className = 'fade-in-up';
        row.innerHTML = `
            <td><strong>${cliente.onu_ont}</strong></td>
            <td>${cliente.switch}</td>
            <td>${cliente.porta_olt}</td>
            <td><span class="badge bg-${cliente.interface === '301' ? 'success' : 'info'}">${cliente.interface}</span></td>
            <td>${formatDate(cliente.data_provisionamento)}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="verificarPotenciaCliente('${cliente.onu_ont}')">
                    <i class="fas fa-chart-line"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="desprovisionarCliente('${cliente.onu_ont}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Atualizar estatísticas do dashboard
function updateStats(clientes = []) {
    const totalOnus = clientes.length;
    const vlan301 = clientes.filter(c => c.interface === '301').length;
    const vlan600 = clientes.filter(c => c.interface === '600').length;
    
    document.getElementById('total-onus').textContent = totalOnus;
    document.getElementById('vlan-301').textContent = vlan301;
    document.getElementById('vlan-600').textContent = vlan600;
    
    // Animar números
    animateNumber('total-onus', totalOnus);
    animateNumber('vlan-301', vlan301);
    animateNumber('vlan-600', vlan600);
}

// Carregar perfis
async function loadPerfis() {
    try {
        const response = await fetch('api/perfis.php');
        const perfis = await response.json();
        
        updatePerfisTable(perfis);
        document.getElementById('total-perfis').textContent = perfis.length;
        animateNumber('total-perfis', perfis.length);
    } catch (error) {
        console.error('Erro ao carregar perfis:', error);
        showAlert('danger', 'Erro ao carregar perfis');
    }
}

// Atualizar tabela de perfis
function updatePerfisTable(perfis) {
    const tbody = document.getElementById('perfis-tbody');
    tbody.innerHTML = '';
    
    perfis.forEach(perfil => {
        const row = document.createElement('tr');
        row.className = 'fade-in-up';
        row.innerHTML = `
            <td><strong>${perfil.nome_perfil}</strong></td>
            <td>${perfil.gemport}</td>
            <td>${perfil.lineprofile_srvprofile}</td>
            <td><span class="badge bg-${perfil.vlan === '301' ? 'success' : 'info'}">${perfil.vlan}</span></td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editarPerfil(${perfil.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deletarPerfil(${perfil.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Carregar perfis no select
async function loadPerfilsSelect() {
    try {
        const response = await fetch('api/perfis.php');
        const perfis = await response.json();
        
        const select = document.getElementById('perfil');
        select.innerHTML = '<option value="">Selecione um perfil</option>';
        
        perfis.forEach(perfil => {
            const option = document.createElement('option');
            option.value = perfil.id;
            option.textContent = `${perfil.nome_perfil} (VLAN ${perfil.vlan})`;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Erro ao carregar perfis:', error);
    }
}

// Provisionar ONU
async function provisionarONU() {
    const formData = {
        sn: document.getElementById('sn').value,
        onu_id: document.getElementById('onu-id').value,
        interface: document.getElementById('interface').value,
        perfil_id: document.getElementById('perfil').value,
        descricao_equipamento: document.getElementById('descricao-equipamento').value
    };
    
    const statusDiv = document.getElementById('provisionar-status');
    statusDiv.innerHTML = '<div class="loading"></div> Provisionando ONU...';
    statusDiv.className = 'alert alert-info';
    
    try {
        const response = await fetch('api/provisionar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            statusDiv.innerHTML = '<i class="fas fa-check-circle me-1"></i>' + result.message;
            statusDiv.className = 'alert alert-success';
            document.getElementById('provisionar-form').reset();
            loadClientes(); // Atualizar lista
        } else {
            statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>' + result.message;
            statusDiv.className = 'alert alert-danger';
        }
    } catch (error) {
        statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Erro de conexão';
        statusDiv.className = 'alert alert-danger';
    }
}

// Verificar potência
async function verificarPotencia() {
    const sn = document.getElementById('sn-verificar').value;
    
    const resultDiv = document.getElementById('potencia-resultado');
    resultDiv.innerHTML = '<div class="loading"></div> Verificando potência...';
    
    try {
        const response = await fetch('api/verificar_potencia.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ sn: sn })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            displayPotenciaResult(result.power_data, result.cliente_info);
            updatePotenciaChart(result.power_data);
        } else {
            resultDiv.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
        }
    } catch (error) {
        resultDiv.innerHTML = '<div class="alert alert-danger">Erro de conexão</div>';
    }
}

// Exibir resultado da potência
function displayPotenciaResult(powerData, clienteInfo) {
    const resultDiv = document.getElementById('potencia-resultado');
    
    const rxPowerClass = getPowerClass(powerData.rx_power);
    const txPowerClass = getPowerClass(powerData.tx_power);
    
    resultDiv.innerHTML = `
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">ONU: ${clienteInfo.onu_ont}</h6>
                <div class="row">
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="${rxPowerClass}">${powerData.rx_power} dBm</h4>
                            <small class="text-muted">RX Power</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <h4 class="${txPowerClass}">${powerData.tx_power} dBm</h4>
                            <small class="text-muted">TX Power</small>
                        </div>
                    </div>
                </div>
                <hr>
                <small class="text-muted">Última verificação: ${powerData.timestamp}</small>
            </div>
        </div>
    `;
}

// Determinar classe CSS baseada no nível de potência
function getPowerClass(power) {
    if (power >= -15) return 'power-excellent';
    if (power >= -20) return 'power-good';
    if (power >= -25) return 'power-warning';
    return 'power-critical';
}

// Desprovisionar ONU
async function desprovisionarONU() {
    const sn = document.getElementById('sn-desprovisionar').value;
    
    if (!confirm('Tem certeza que deseja desprovisionar esta ONU?')) {
        return;
    }
    
    const statusDiv = document.getElementById('desprovisionar-status');
    statusDiv.innerHTML = '<div class="loading"></div> Desprovisionando ONU...';
    statusDiv.className = 'alert alert-info';
    
    try {
        const response = await fetch('api/desprovisionar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ sn: sn })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            statusDiv.innerHTML = '<i class="fas fa-check-circle me-1"></i>' + result.message;
            statusDiv.className = 'alert alert-success';
            document.getElementById('desprovisionar-form').reset();
            loadClientes(); // Atualizar lista
        } else {
            statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>' + result.message;
            statusDiv.className = 'alert alert-danger';
        }
    } catch (error) {
        statusDiv.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Erro de conexão';
        statusDiv.className = 'alert alert-danger';
    }
}

// Inicializar gráfico de potência
function initializePotenciaChart() {
    const ctx = document.getElementById('potencia-chart').getContext('2d');
    
    potenciaChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'RX Power (dBm)',
                data: [],
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'TX Power (dBm)',
                data: [],
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Histórico de Potência da ONU'
                },
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Potência (dBm)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Tempo'
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    });
}

// Atualizar gráfico de potência
function updatePotenciaChart(newData) {
    const now = new Date().toLocaleTimeString();
    
    // Adicionar novos dados
    potenciaChart.data.labels.push(now);
    potenciaChart.data.datasets[0].data.push(newData.rx_power);
    potenciaChart.data.datasets[1].data.push(newData.tx_power);
    
    // Manter apenas os últimos 20 pontos
    if (potenciaChart.data.labels.length > 20) {
        potenciaChart.data.labels.shift();
        potenciaChart.data.datasets[0].data.shift();
        potenciaChart.data.datasets[1].data.shift();
    }
    
    potenciaChart.update();
}

// Salvar perfil
async function salvarPerfil() {
    const perfilData = {
        id: document.getElementById('perfil-id').value,
        nome_perfil: document.getElementById('nome-perfil').value,
        gemport: document.getElementById('gemport').value,
        lineprofile_srvprofile: document.getElementById('lineprofile-srvprofile').value,
        vlan: document.getElementById('vlan').value
    };
    
    const method = perfilData.id ? 'PUT' : 'POST';
    const url = 'api/perfis.php';
    
    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(perfilData)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showAlert('success', result.message);
            document.getElementById('perfil-form').reset();
            document.getElementById('perfil-id').value = '';
            bootstrap.Modal.getInstance(document.getElementById('perfilModal')).hide();
            loadPerfis();
        } else {
            showAlert('danger', result.message);
        }
    } catch (error) {
        showAlert('danger', 'Erro de conexão');
    }
}

// Editar perfil
async function editarPerfil(id) {
    try {
        const response = await fetch(`api/perfis.php?id=${id}`);
        const perfil = await response.json();
        
        document.getElementById('perfil-id').value = perfil.id;
        document.getElementById('nome-perfil').value = perfil.nome_perfil;
        document.getElementById('gemport').value = perfil.gemport;
        document.getElementById('lineprofile-srvprofile').value = perfil.lineprofile_srvprofile;
        document.getElementById('vlan').value = perfil.vlan;
        
        new bootstrap.Modal(document.getElementById('perfilModal')).show();
    } catch (error) {
        showAlert('danger', 'Erro ao carregar perfil');
    }
}

// Deletar perfil
async function deletarPerfil(id) {
    if (!confirm('Tem certeza que deseja deletar este perfil?')) {
        return;
    }
    
    try {
        const response = await fetch('api/perfis.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showAlert('success', result.message);
            loadPerfis();
        } else {
            showAlert('danger', result.message);
        }
    } catch (error) {
        showAlert('danger', 'Erro de conexão');
    }
}

// Verificar potência de cliente específico
function verificarPotenciaCliente(sn) {
    document.getElementById('sn-verificar').value = sn;
    showSection('verificar');
    document.querySelector('[data-section="verificar"]').classList.add('active');
    document.querySelector('[data-section="dashboard"]').classList.remove('active');
    verificarPotencia();
}

// Desprovisionar cliente específico
function desprovisionarCliente(sn) {
    document.getElementById('sn-desprovisionar').value = sn;
    showSection('desprovisionar');
    document.querySelector('[data-section="desprovisionar"]').classList.add('active');
    document.querySelector('[data-section="dashboard"]').classList.remove('active');
}

// Mostrar alerta
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Remover automaticamente após 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

// Formatar data
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR');
}

// Animar números
function animateNumber(elementId, targetNumber) {
    const element = document.getElementById(elementId);
    const startNumber = 0;
    const duration = 1000;
    const startTime = performance.now();
    
    function updateNumber(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const currentNumber = Math.floor(startNumber + (targetNumber - startNumber) * progress);
        
        element.textContent = currentNumber;
        
        if (progress < 1) {
            requestAnimationFrame(updateNumber);
        }
    }
    
    requestAnimationFrame(updateNumber);
}

