<?php
class OLTConnection {
    private $host;
    private $port;
    private $username;
    private $password;
    private $connection_type;
    private $connection;
    private $telnet_socket;

    public function __construct($host, $port, $username, $password, $connection_type = 'ssh') {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->connection_type = $connection_type;
    }

    public function connect() {
        if ($this->connection_type === 'ssh') {
            return $this->connectSSH();
        } else {
            return $this->connectTelnet();
        }
    }

    private function connectSSH() {
        if (!function_exists('ssh2_connect')) {
            throw new Exception("Extensão SSH2 não está instalada");
        }

        // Adicionando métodos de troca de chave mais antigos
        $methods = [
            'kex' => 'diffie-hellman-group1-sha1,diffie-hellman-group-exchange-sha1',
            // Você pode adicionar outros métodos se necessário
        ];

        $this->connection = ssh2_connect($this->host, $this->port, $methods);
        if (!$this->connection) {
            throw new Exception("Falha ao conectar via SSH com a OLT");
        }
        
        if (!ssh2_auth_password($this->connection, $this->username, $this->password)) {
            throw new Exception("Falha na autenticação SSH");
        }
        
        return true;
    }

    private function connectTelnet() {
        $this->telnet_socket = fsockopen($this->host, $this->port, $errno, $errstr, 10);
        if (!$this->telnet_socket) {
            throw new Exception("Falha ao conectar via Telnet: $errstr ($errno)");
        }

        // Aguardar prompt de login
        $this->waitForPrompt('login:', 5);
        
        // Enviar usuário
        fwrite($this->telnet_socket, $this->username . "\r\n");
        
        // Aguardar prompt de senha
        $this->waitForPrompt('Password:', 5);
        
        // Enviar senha
        fwrite($this->telnet_socket, $this->password . "\r\n");
        
        // Aguardar prompt do sistema
        $this->waitForPrompt('>', 10);
        
        return true;
    }

    private function waitForPrompt($expected, $timeout = 10) {
        $start_time = time();
        $buffer = '';
        
        while ((time() - $start_time) < $timeout) {
            $data = fread($this->telnet_socket, 1024);
            if ($data === false) {
                break;
            }
            $buffer .= $data;
            
            if (strpos($buffer, $expected) !== false) {
                return $buffer;
            }
            usleep(100000); // 100ms
        }
        
        throw new Exception("Timeout aguardando prompt: $expected");
    }

    public function executeCommand($command) {
        if ($this->connection_type === 'ssh') {
            return $this->executeSSHCommand($command);
        } else {
            return $this->executeTelnetCommand($command);
        }
    }

    private function executeSSHCommand($command) {
        if (!$this->connection) {
            throw new Exception("Conexão SSH não estabelecida");
        }

        $stream = ssh2_exec($this->connection, $command);
        if (!$stream) {
            throw new Exception("Falha ao executar comando SSH");
        }

        stream_set_blocking($stream, true);
        $output = stream_get_contents($stream);
        fclose($stream);

        return $output;
    }

    private function executeTelnetCommand($command) {
        if (!$this->telnet_socket) {
            throw new Exception("Conexão Telnet não estabelecida");
        }

        // Enviar comando
        fwrite($this->telnet_socket, $command . "\r\n");
        
        // Aguardar resposta
        usleep(500000); // 500ms
        
        $output = '';
        $start_time = time();
        
        while ((time() - $start_time) < 10) {
            $data = fread($this->telnet_socket, 4096);
            if ($data === false || $data === '') {
                break;
            }
            $output .= $data;
            
            // Verificar se chegou ao prompt
            if (preg_match('/[>#\$]\s*$/', $output)) {
                break;
            }
            usleep(100000); // 100ms
        }
        
        return $output;
    }

    public function testConnection() {
        try {
            $this->connect();
            $result = $this->executeCommand('display version');
            $this->disconnect();
            return ['success' => true, 'message' => 'Conexão estabelecida com sucesso', 'output' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function detectONUs($interface = 'all') {
        try {
            if ($interface === 'all') {
                $command = "enable\nconfig\ndisplay ont autofind all\nquit";
            } else {
                $command = "interface gpon $interface\ndisplay ont autofind all\nquit";
            }
            
            $output = $this->executeCommand($command);
            
            // Parse da saída para extrair ONUs encontradas
            $onus = [];
            $lines = explode("\n", $output);
            
            foreach ($lines as $line) {
                // Procurar por linhas que contenham informações de ONU
                if (preg_match('/\s+(\d+\/\d+\/\d+)\s+(\d+)\s+([A-F0-9]+)\s+(\w+)\s+(\w+)/', $line, $matches)) {
                    $onus[] = [
                        'interface' => $matches[1],
                        'onu_id' => $matches[2],
                        'sn' => $matches[3],
                        'type' => $matches[4],
                        'status' => $matches[5]
                    ];
                }
            }
            
            return $onus;
        } catch (Exception $e) {
            throw new Exception("Erro ao detectar ONUs: " . $e->getMessage());
        }
    }

    public function provisionONU($interface, $onu_id, $sn, $profile) {
        $commands = [
            "interface gpon $interface",
            "ont add $onu_id sn-auth $sn omci ont-lineprofile-id {$profile['lineprofile']} ont-srvprofile-id {$profile['srvprofile']}",
            "ont port native-vlan $onu_id eth 1 vlan {$profile['vlan']} priority 0",
            "service-port {$profile['gemport']} vlan {$profile['vlan']} gpon $interface ont $onu_id gemport 1 multi-service user-vlan {$profile['vlan']}",
            "quit"
        ];

        $results = [];
        foreach ($commands as $command) {
            $results[] = $this->executeCommand($command);
        }

        return $results;
    }

    public function deprovisionONU($interface, $onu_id) {
        $commands = [
            "interface gpon $interface",
            "ont delete $onu_id",
            "quit"
        ];

        $results = [];
        foreach ($commands as $command) {
            $results[] = $this->executeCommand($command);
        }

        return $results;
    }

    public function getONUPower($interface, $onu_id) {
        $command = "enable\nconfig\ndisplay ont optical-info $onu_id";
        $this->executeCommand("interface gpon $interface");
        $output = $this->executeCommand($command);
        $this->executeCommand("quit");

        // Parse da saída para extrair informações de potência
        preg_match('/Rx Power\s*:\s*([-\d.]+)\s*dBm/', $output, $rx_matches);
        preg_match('/Tx Power\s*:\s*([-\d.]+)\s*dBm/', $output, $tx_matches);

        return [
            'rx_power' => isset($rx_matches[1]) ? floatval($rx_matches[1]) : null,
            'tx_power' => isset($tx_matches[1]) ? floatval($tx_matches[1]) : null,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    public function disconnect() {
        if ($this->connection_type === 'ssh' && $this->connection) {
            ssh2_disconnect($this->connection);
            $this->connection = null;
        } elseif ($this->connection_type === 'telnet' && $this->telnet_socket) {
            fclose($this->telnet_socket);
            $this->telnet_socket = null;
        }
    }
}
?>

