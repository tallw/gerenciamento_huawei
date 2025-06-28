# Sistema de Gerenciamento ONU - Huawei OLT v2.0

Sistema completo em PHP para gerenciamento de ONUs em equipamentos OLT Huawei, com interface web moderna e funcionalidades avançadas.

## 🚀 Funcionalidades Principais

### ✅ Gerenciamento de ONUs
- **Provisionamento**: Adiciona ONUs na OLT com perfis personalizados
- **Desprovisionamento**: Remove ONUs da OLT e do banco de dados
- **Detecção Automática**: Comando `display ont autofind all` para detectar ONUs disponíveis
- **Verificação de Potência**: Consulta potência RX/TX com gráficos em tempo real

### ✅ Configuração da OLT
- **Múltiplas Configurações**: Salva várias configurações de OLT no banco de dados
- **Conexão SSH/Telnet**: Suporte para ambos os tipos de conexão
- **Teste de Conexão**: Verifica conectividade antes das operações
- **Configuração Ativa**: Sistema de ativação de configurações

### ✅ Gerenciamento de Perfis
- **CRUD Completo**: Criar, editar e excluir perfis de ONU
- **GEM Port**: Configuração de portas GEM
- **Line/Service Profiles**: Configuração de perfis de linha e serviço
- **VLANs**: Suporte para VLANs 301 e 600

### ✅ Interface Web Moderna
- **Design Responsivo**: Bootstrap 5 com tema escuro
- **Dashboard**: Estatísticas em tempo real
- **Gráficos Interativos**: Chart.js para visualização de potência
- **Navegação por Abas**: Interface intuitiva e organizada

## 📋 Requisitos do Sistema

### Servidor Web
- **Apache/Nginx** com suporte a PHP
- **PHP 7.4+** com extensões:
  - PDO MySQL
  - SSH2 (para conexões SSH)
  - Sockets (para conexões Telnet)

### Banco de Dados
- **MySQL 5.7+** ou **MariaDB 10.3+**

### Dependências PHP
```bash
# Ubuntu/Debian
sudo apt-get install php-ssh2 php-mysql php-sockets

# CentOS/RHEL
sudo yum install php-ssh2 php-mysql php-sockets
```

## 🛠️ Instalação

### 1. Configuração do Banco de Dados
```sql
-- Execute o script database_schema.sql
mysql -u root -p < database_schema.sql
```

### 2. Configuração do Apache
```bash
# Copie o arquivo de configuração
sudo cp apache-config.conf /etc/apache2/sites-available/onu-manager.conf
sudo a2ensite onu-manager
sudo systemctl reload apache2
```

### 3. Configuração do Sistema
```bash
# Execute o script de instalação
chmod +x install.sh
sudo ./install.sh
```

### 4. Configuração Manual
Edite o arquivo `config/database.php` com suas credenciais:
```php
private $host = "localhost";
private $db_name = "seu_banco";
private $username = "seu_usuario";
private $password = "sua_senha";
```

## 📊 Estrutura do Banco de Dados

### Tabela `sis_cliente`
Armazena informações dos clientes/ONUs provisionadas:
- `onu_ont`: Serial Number da ONU
- `switch`: ONU ID + descrição do equipamento
- `porta_olt`: Interface GPON onde a ONU está conectada
- `interface`: VLAN utilizada (301 ou 600)

### Tabela `perfis_onu`
Armazena perfis de configuração das ONUs:
- `nome_perfil`: Nome identificador do perfil
- `gemport`: Configuração da porta GEM
- `lineprofile_srvprofile`: Line Profile e Service Profile
- `vlan`: VLAN associada ao perfil

### Tabela `config_olt`
Armazena configurações de acesso às OLTs:
- `nome_config`: Nome da configuração
- `ip_olt`, `porta_olt`: Endereço e porta da OLT
- `usuario_olt`, `senha_olt`: Credenciais de acesso
- `tipo_conexao`: SSH ou Telnet
- `ativa`: Configuração ativa no momento

## 🎯 Como Usar

### 1. Configurar OLT
1. Acesse a aba **Configurações**
2. Clique em **Nova Configuração**
3. Preencha os dados da OLT (IP, porta, usuário, senha)
4. Escolha o tipo de conexão (SSH/Telnet)
5. Teste a conexão e ative a configuração

### 2. Criar Perfis
1. Acesse a aba **Perfis**
2. Clique em **Novo Perfil**
3. Configure GEM Port, Line Profile, Service Profile e VLAN
4. Salve o perfil para uso posterior

### 3. Detectar ONUs
1. Na aba **Provisionar**, use a seção **Detectar ONUs**
2. Especifique uma interface ou deixe vazio para todas
3. Clique em **Detectar ONUs**
4. Use o botão **Usar** para preencher automaticamente os dados

### 4. Provisionar ONU
1. Preencha Serial Number, ONU ID e Interface
2. Selecione um perfil criado anteriormente
3. Adicione descrição do equipamento (opcional)
4. Clique em **Provisionar ONU**

### 5. Verificar Potência
1. Na aba **Verificar Potência**
2. Informe o Serial Number da ONU
3. Visualize os valores RX/TX e o gráfico histórico

### 6. Desprovisionar ONU
1. Na aba **Desprovisionar**
2. Informe o Serial Number
3. Confirme a operação

## 🔧 Comandos OLT Huawei Utilizados

### Detecção de ONUs
```
display ont autofind all
display ont autofind gpon 0/1/0
```

### Provisionamento
```
interface gpon 0/1/0
ont add 1 sn-auth "SERIAL_NUMBER" omci ont-lineprofile-id 1 ont-srvprofile-id 1
ont port native-vlan 1 eth 1 vlan 301 priority 0
```

### Verificação de Potência
```
display ont optical-info 1 1
```

### Desprovisionamento
```
interface gpon 0/1/0
undo ont 1
```

## 🎨 Personalização

### Temas e Cores
Edite o arquivo `css/style.css` para personalizar:
- Cores do tema
- Layout dos componentes
- Animações e transições

### Comandos OLT
Modifique a classe `OLTConnection.php` para:
- Adicionar novos comandos
- Adaptar para outros modelos de OLT
- Personalizar parsing de respostas

## 🔒 Segurança

### Recomendações
- Use HTTPS em produção
- Configure firewall para restringir acesso
- Use senhas fortes para banco de dados
- Mantenha backups regulares
- Monitore logs de acesso

### Arquivo .htaccess
O sistema inclui proteções básicas:
- Bloqueio de acesso a arquivos sensíveis
- Headers de segurança
- Proteção contra ataques comuns

## 📝 Logs e Monitoramento

### Logs do Sistema
- Conexões com OLT são registradas
- Status de operações são salvos no banco
- Erros são logados para debugging

### Monitoramento
- Dashboard com estatísticas em tempo real
- Gráficos de potência históricos
- Status de conexão das configurações

## 🆘 Solução de Problemas

### Erro de Conexão SSH/Telnet
1. Verifique se a extensão PHP está instalada
2. Teste conectividade de rede
3. Confirme credenciais de acesso
4. Verifique logs do sistema

### Erro no Banco de Dados
1. Confirme credenciais em `config/database.php`
2. Verifique se as tabelas foram criadas
3. Teste conexão com MySQL

### Interface não Carrega
1. Verifique configuração do Apache
2. Confirme permissões de arquivos
3. Verifique logs do Apache

## 📞 Suporte

Para suporte técnico ou dúvidas:
- Verifique a documentação completa
- Consulte os logs do sistema
- Teste as configurações passo a passo

## 🔄 Atualizações

### Versão Atual: 2.0
- ✅ Suporte SSH/Telnet
- ✅ Detecção automática de ONUs
- ✅ Configurações múltiplas da OLT
- ✅ Interface aprimorada
- ✅ Correção do arquivo .htaccess

### Próximas Funcionalidades
- Backup/Restore de configurações
- Relatórios avançados
- Integração com sistemas externos
- Suporte a outros modelos de OLT

---

**Sistema desenvolvido para gerenciamento profissional de redes GPON com equipamentos Huawei.**

