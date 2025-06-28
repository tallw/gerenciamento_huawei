-- Tabela para armazenar informações dos clientes (ONUs provisionadas)
CREATE TABLE sis_cliente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    onu_ont VARCHAR(255) NOT NULL UNIQUE, -- SN da ONU
    switch VARCHAR(255) NOT NULL, -- onu_id + descrição do equipamento
    porta_olt VARCHAR(255) NOT NULL, -- Porta de saída da interface GPON
    interface VARCHAR(255) NOT NULL, -- VLAN escolhida (301 ou 600)
    data_provisionamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela para armazenar os perfis de ONU
CREATE TABLE perfis_onu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_perfil VARCHAR(255) NOT NULL UNIQUE,
    gemport VARCHAR(255) NOT NULL,
    lineprofile_srvprofile VARCHAR(255) NOT NULL,
    vlan VARCHAR(255) NOT NULL
);

-- Tabela para armazenar configurações da OLT
CREATE TABLE config_olt (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_config VARCHAR(255) NOT NULL UNIQUE,
    ip_olt VARCHAR(255) NOT NULL,
    porta_olt INT NOT NULL DEFAULT 22,
    usuario_olt VARCHAR(255) NOT NULL,
    senha_olt VARCHAR(255) NOT NULL,
    tipo_conexao ENUM('ssh', 'telnet') NOT NULL DEFAULT 'ssh',
    ativa BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_ultima_conexao TIMESTAMP NULL,
    status_conexao ENUM('conectado', 'desconectado', 'erro') DEFAULT 'desconectado'
);

-- Inserir configuração padrão
INSERT INTO config_olt (nome_config, ip_olt, porta_olt, usuario_olt, senha_olt, tipo_conexao, ativa) 
VALUES ('OLT Principal', '192.168.1.1', 22, 'admin', 'admin', 'ssh', TRUE);

