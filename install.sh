#!/bin/bash

# Script de Instalação do Sistema de Gerenciamento ONU
# Para Ubuntu/Debian

echo "=== Sistema de Gerenciamento ONU - Instalação ==="
echo ""

# Verificar se está rodando como root
if [ "$EUID" -ne 0 ]; then
    echo "Por favor, execute como root (sudo)"
    exit 1
fi

# Atualizar sistema
echo "Atualizando sistema..."
apt update && apt upgrade -y

# Instalar Apache, PHP e MySQL
echo "Instalando Apache, PHP e MySQL..."
apt install -y apache2 php php-mysql php-ssh2 php-curl php-json php-mbstring mysql-server

# Habilitar módulos Apache
echo "Habilitando módulos Apache..."
a2enmod rewrite
a2enmod ssl

# Criar diretório do sistema
echo "Criando diretório do sistema..."
mkdir -p /var/www/html/sistema-onu

# Definir permissões
echo "Configurando permissões..."
chown -R www-data:www-data /var/www/html/sistema-onu
chmod -R 755 /var/www/html/sistema-onu

# Configurar MySQL
echo "Configurando MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS onu_management;"
mysql -e "CREATE USER IF NOT EXISTS 'onu_user'@'localhost' IDENTIFIED BY 'onu_password_123';"
mysql -e "GRANT ALL PRIVILEGES ON onu_management.* TO 'onu_user'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Importar esquema do banco
if [ -f "database_schema.sql" ]; then
    echo "Importando esquema do banco de dados..."
    mysql onu_management < database_schema.sql
fi

# Copiar arquivos do sistema
echo "Copiando arquivos do sistema..."
cp -r * /var/www/html/sistema-onu/

# Configurar Apache
echo "Configurando Apache..."
if [ -f "apache-config.conf" ]; then
    cp apache-config.conf /etc/apache2/sites-available/sistema-onu.conf
    a2ensite sistema-onu.conf
fi

# Reiniciar serviços
echo "Reiniciando serviços..."
systemctl restart apache2
systemctl restart mysql

# Configurar firewall (se UFW estiver ativo)
if command -v ufw &> /dev/null; then
    echo "Configurando firewall..."
    ufw allow 80/tcp
    ufw allow 443/tcp
fi

echo ""
echo "=== Instalação Concluída ==="
echo ""
echo "Acesse o sistema em: http://localhost/sistema-onu"
echo ""
echo "Configurações do banco de dados:"
echo "  Host: localhost"
echo "  Database: onu_management"
echo "  Username: onu_user"
echo "  Password: onu_password_123"
echo ""
echo "Lembre-se de:"
echo "1. Configurar os dados da OLT no sistema"
echo "2. Criar perfis de ONU antes de provisionar"
echo "3. Verificar conectividade SSH com a OLT"
echo ""
echo "Para produção, configure HTTPS e altere as senhas padrão!"
echo ""

