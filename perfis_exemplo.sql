-- Exemplos de Perfis ONU para Importação
-- Execute estes comandos após a instalação do sistema

USE onu_management;

-- Perfil para VLAN 301 (Residencial)
INSERT INTO perfis_onu (nome_perfil, gemport, lineprofile_srvprofile, vlan) VALUES
('Residencial_301', '1001', '1-1', '301');

-- Perfil para VLAN 600 (Empresarial)
INSERT INTO perfis_onu (nome_perfil, gemport, lineprofile_srvprofile, vlan) VALUES
('Empresarial_600', '1002', '2-2', '600');

-- Perfil para VLAN 301 (Alta Velocidade)
INSERT INTO perfis_onu (nome_perfil, gemport, lineprofile_srvprofile, vlan) VALUES
('Alta_Velocidade_301', '1003', '3-3', '301');

-- Perfil para VLAN 600 (Corporativo)
INSERT INTO perfis_onu (nome_perfil, gemport, lineprofile_srvprofile, vlan) VALUES
('Corporativo_600', '1004', '4-4', '600');

-- Verificar perfis criados
SELECT * FROM perfis_onu;

