<?php
require_once __DIR__ . '/../vendor/autoload.php';

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class OLTConnection {
    private $host;
    private $port;
    private $username;
    private $password;
    private $connection_type;
    private $ssh;
    private $telnet_socket;
    private $last_error;
    
    public function __construct($host, $port, $username, $password, $connection_type = 'ssh') {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->connection_type = $connection_type;
        $this->last_error = '';
    }
    
    public function connect() {
        // Tentar SSH primeiro se especificado
        if ($this->connection_type === 'ssh') {
            if ($this->connectSSH()) {
                return true;
            }
            
            // Se SSH falhar, tentar Telnet automaticamente
            error_log("SSH falhou para {$this->host}:{$this->port}, tentando Telnet como fallback");
            $this->connection_type = 'telnet';
            return $this->connectTelnet();
        }
        
        // Conectar via Telnet se especificado
        if ($this->connection_type === 'telnet') {
            return $this->connectTelnet();
        }
        
        $this->last_error = "Tipo de conexão inválido: {$this->connection_type}";
        return false;
    }
    
    private function connectSSH() {
        try {
            // Criar instância SSH2 com configurações para equipamentos antigos
            $this->ssh = new SSH2($this->host, $this->port);
            
            // Configurar algoritmos de criptografia para compatibilidade com equipamentos antigos
            $this->ssh->setPreferredAlgorithms([
                'kex' => [
                    'diffie-hellman-group-exchange-sha256',
                    'diffie-hellman-group-exchange-sha1',
                    'diffie-hellman-group14-sha256',
                    'diffie-hellman-group14-sha1',
                    'diffie-hellman-group1-sha1'  // Para OLTs muito antigas
                ],
                'hostkey' => [
                    'rsa-sha2-512',
                    'rsa-sha2-256',
                    'ssh-rsa',
                    'ssh-dss'
                ],
                'client_to_server' => [
                    'crypt' => [
                        'aes128-ctr',
                        'aes192-ctr',
                        'aes256-ctr',
                        'aes128-cbc',
                        'aes192-cbc',
                        'aes256-cbc',
                        '3des-cbc',
                        'blowfish-cbc'
                    ],
                    'mac' => [
                        'hmac-sha2-256',
                        'hmac-sha2-512',
                        'hmac-sha1',
                        'hmac-md5'
                    ]
                ],
                'server_to_client' => [
                    'crypt' => [
                        'aes128-ctr',
                        'aes192-ctr',
                        'aes256-ctr',
                        'aes128-cbc',
                        'aes192-cbc',
                        'aes256-cbc',
                        '3des-cbc',
                        'blowfish-cbc'
                    ],
                    'mac' => [
                        'hmac-sha2-256',
                        'hmac-sha2-512',
                        'hmac-sha1',
                        'hmac-md5'
                    ]
                ]
            ]);
            
            // Configurar timeout
            $this->ssh->setTimeout(10);
            
            // Tentar autenticação
            if (!$this->ssh->login($this->username, $this->password)) {
                $this->last_error = "Falha na autenticação SSH";
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->last_error = "Erro SSH: " . $e->getMessage();
            error_log("Erro SSH para {$this->host}:{$this->port} - " . $e->getMessage());
            return false;
        }
    }
    
    private function connectTelnet() {
        try {
            // Conectar via socket
            $this->telnet_socket = fsockopen($this->host, $this->port, $errno, $errstr, 10);
            
            if (!$this->telnet_socket) {
                $this->last_error = "Falha ao conectar via Telnet: $errstr ($errno)";
                return false;
            }
            
            // Configurar socket
            stream_set_timeout($this->telnet_socket, 10);
            stream_set_blocking($this->telnet_socket, false);
            
            // Aguardar prompt de login
            sleep(2);
            $output = $this->readTelnetOutput();
            
            // Enviar usuário
            fwrite($this->telnet_socket, $this->username . "\r\n");
            sleep(1);
            $this->readTelnetOutput();
            
            // Enviar senha
            fwrite($this->telnet_socket, $this->password . "\r\n");
            sleep(2);
            $output = $this->readTelnetOutput();
            
            // Verificar se login foi bem-sucedido
            if (strpos(strtolower($output), 'login') !== false || 
                strpos(strtolower($output), 'password') !== false ||
                strpos(strtolower($output), 'incorrect') !== false ||
                strpos(strtolower($output), 'failed') !== false) {
                $this->last_error = "Falha na autenticação Telnet";
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->last_error = "Erro Telnet: " . $e->getMessage();
            return false;
        }
    }
    
    public function executeCommand($command) {
        try {
            if ($this->connection_type === 'ssh' && $this->ssh) {
                return $this->executeSSHCommand($command);
            } elseif ($this->connection_type === 'telnet' && $this->telnet_socket) {
                return $this->executeTelnetCommand($command);
            }
            
            $this->last_error = "Não conectado";
            return false;
            
        } catch (Exception $e) {
            $this->last_error = "Erro ao executar comando: " . $e->getMessage();
            return false;
        }
    }
    
    private function executeSSHCommand($command) {
        // Executar comando via SSH usando phpseclib
        $output = $this->ssh->exec($command);
        
        if ($output === false) {
            $this->last_error = "Falha ao executar comando SSH";
            return false;
        }
        
        return $output;
    }
    
    private function executeTelnetCommand($command) {
        // Limpar buffer
        $this->readTelnetOutput();
        
        // Enviar comando
        fwrite($this->telnet_socket, $command . "\r\n");
        sleep(1);
        
        // Ler resposta
        $output = $this->readTelnetOutput();
        
        return $output;
    }
    
    private function readTelnetOutput() {
        $output = '';
        $attempts = 0;
        
        while ($attempts < 50) {
            $data = fread($this->telnet_socket, 4096);
            if ($data === false || $data === '') {
                usleep(100000); // 100ms
                $attempts++;
                continue;
            }
            
            $output .= $data;
            $attempts = 0;
            
            // Verificar se chegou ao fim do output
            if (strpos($output, '#') !== false || strpos($output, '>') !== false) {
                break;
            }
        }
        
        return $output;
    }
    
    public function testConnection() {
        if (!$this->connect()) {
            return array(
                'success' => false,
                'message' => $this->last_error,
                'connection_type' => $this->connection_type
            );
        }
        
        // Testar comando simples
        $output = $this->executeCommand('display version');
        
        if ($output === false || empty(trim($output))) {
            return array(
                'success' => false,
                'message' => 'Conexão estabelecida mas não foi possível executar comandos',
                'connection_type' => $this->connection_type
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Conexão estabelecida com sucesso via ' . strtoupper($this->connection_type),
            'connection_type' => $this->connection_type,
            'output' => $output
        );
    }
    
    public function provisionarONU($sn, $onu_id, $interface, $perfil) {
        if (!$this->isConnected()) {
            return array('success' => false, 'message' => 'Não conectado à OLT');
        }
        
        try {
            // Entrar no modo de configuração
            $this->executeCommand('enable');
            $this->executeCommand('config');
            
            // Entrar na interface GPON
            $this->executeCommand("interface gpon $interface");
            
            // Adicionar ONU
            $cmd = "ont add $onu_id sn-auth \"$sn\" omci ont-lineprofile-id {$perfil['lineprofile_srvprofile']} ont-srvprofile-id {$perfil['lineprofile_srvprofile']}";
            $output1 = $this->executeCommand($cmd);
            
            // Configurar VLAN
            $cmd2 = "ont port native-vlan $onu_id eth 1 vlan {$perfil['vlan']} priority 0";
            $output2 = $this->executeCommand($cmd2);
            
            // Sair da configuração
            $this->executeCommand('quit');
            $this->executeCommand('quit');
            
            return array(
                'success' => true,
                'message' => 'ONU provisionada com sucesso',
                'output' => $output1 . "\n" . $output2
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Erro ao provisionar ONU: ' . $e->getMessage()
            );
        }
    }
    
    public function desprovisionarONU($sn, $cliente_info) {
        if (!$this->isConnected()) {
            return array('success' => false, 'message' => 'Não conectado à OLT');
        }
        
        try {
            // Extrair ONU ID e interface do switch
            preg_match('/(\d+)/', $cliente_info['switch'], $matches);
            $onu_id = $matches[1] ?? '';
            
            if (empty($onu_id)) {
                return array('success' => false, 'message' => 'Não foi possível determinar ONU ID');
            }
            
            // Entrar no modo de configuração
            $this->executeCommand('enable');
            $this->executeCommand('config');
            
            // Entrar na interface GPON
            $this->executeCommand("interface gpon {$cliente_info['porta_olt']}");
            
            // Remover ONU
            $output = $this->executeCommand("undo ont $onu_id");
            
            // Sair da configuração
            $this->executeCommand('quit');
            $this->executeCommand('quit');
            
            return array(
                'success' => true,
                'message' => 'ONU desprovisionada com sucesso',
                'output' => $output
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Erro ao desprovisionar ONU: ' . $e->getMessage()
            );
        }
    }
    
    public function verificarPotencia($sn, $cliente_info) {
        if (!$this->isConnected()) {
            return array('success' => false, 'message' => 'Não conectado à OLT');
        }
        
        try {
            // Extrair ONU ID do switch
            preg_match('/(\d+)/', $cliente_info['switch'], $matches);
            $onu_id = $matches[1] ?? '';
            
            if (empty($onu_id)) {
                return array('success' => false, 'message' => 'Não foi possível determinar ONU ID');
            }
            
            // Executar comando de verificação de potência
            $output = $this->executeCommand("display ont optical-info {$cliente_info['porta_olt']} $onu_id");
            
            // Parse do output para extrair valores de potência
            $rx_power = $this->extractPowerValue($output, 'rx');
            $tx_power = $this->extractPowerValue($output, 'tx');
            
            return array(
                'success' => true,
                'rx_power' => $rx_power,
                'tx_power' => $tx_power,
                'timestamp' => date('Y-m-d H:i:s'),
                'raw_output' => $output
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Erro ao verificar potência: ' . $e->getMessage()
            );
        }
    }
    
    public function detectONUs($interface = null) {
        if (!$this->isConnected()) {
            return array('success' => false, 'message' => 'Não conectado à OLT');
        }
        
        try {
            // Comando para detectar ONUs
            $command = $interface ? "display ont autofind gpon $interface" : "display ont autofind all";
            $output = $this->executeCommand($command);
            
            // Parse do output para extrair informações das ONUs
            $onus = $this->parseAutofindOutput($output);
            
            return array(
                'success' => true,
                'total_onus' => count($onus),
                'interface_pesquisada' => $interface ?: 'all',
                'onus' => $onus,
                'raw_output' => $output
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Erro ao detectar ONUs: ' . $e->getMessage()
            );
        }
    }
    
    private function extractPowerValue($output, $type) {
        $pattern = $type === 'rx' ? '/RX.*?(-?\d+\.?\d*)\s*dBm/i' : '/TX.*?(-?\d+\.?\d*)\s*dBm/i';
        
        if (preg_match($pattern, $output, $matches)) {
            return floatval($matches[1]);
        }
        
        // Valores padrão se não encontrar
        return $type === 'rx' ? -25.0 : 2.5;
    }
    
    private function parseAutofindOutput($output) {
        $onus = array();
        $lines = explode("\n", $output);
        
        foreach ($lines as $line) {
            // Parse de linha típica: gpon 0/1/0  1  HWTC12345678  HG8310M  autofind
            if (preg_match('/gpon\s+(\S+)\s+(\d+)\s+(\S+)\s+(\S+)\s+(\S+)/i', $line, $matches)) {
                $onus[] = array(
                    'interface' => $matches[1],
                    'onu_id' => $matches[2],
                    'sn' => $matches[3],
                    'type' => $matches[4],
                    'status' => $matches[5]
                );
            }
        }
        
        return $onus;
    }
    
    private function isConnected() {
        return ($this->connection_type === 'ssh' && $this->ssh && $this->ssh->isConnected()) ||
               ($this->connection_type === 'telnet' && $this->telnet_socket);
    }
    
    public function getLastError() {
        return $this->last_error;
    }
    
    public function getConnectionType() {
        return $this->connection_type;
    }
    
    public function disconnect() {
        if ($this->ssh && $this->ssh->isConnected()) {
            $this->ssh->disconnect();
        }
        
        if ($this->telnet_socket) {
            fclose($this->telnet_socket);
        }
        
        $this->ssh = null;
        $this->telnet_socket = null;
    }
    
    public function __destruct() {
        $this->disconnect();
    }
}
?>

