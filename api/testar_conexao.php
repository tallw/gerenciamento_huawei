<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit(0);
}

require_once '../classes/ConfigOLT.php';
require_once '../classes/OLTConnection.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['config_id'])) {
        throw new Exception('ID da configuração é obrigatório');
    }
    
    $configOLT = new ConfigOLT();
    $config = $configOLT->readOne($input['config_id']);    
    if (!$config) {
        throw new Exception('Configuração não encontrada');
    }
    
    // Criar conexão OLT
    $olt = new OLTConnection(
        $config['ip_olt'],
        $config['porta_olt'],
        $config['usuario_olt'],
        $config['senha_olt'],
        $config['tipo_conexao']
    );
    
    // Testar conexão
    $resultado = $olt->testConnection();
    
    // Atualizar status no banco de dados
    $status = $resultado['success'] ? 'conectado' : 'erro';
    $configOLT->updateConnectionStatus($config['id'], $status, $resultado['connection_type']);
    
    // Preparar resposta detalhada
    $response = array(
        'success' => $resultado['success'],
        'message' => $resultado['message'],
        'connection_type' => $resultado['connection_type'],
        'details' => array()
    );
    
    // Adicionar detalhes específicos baseados no resultado
    if ($resultado['success']) {
        $response['details'] = array(
            'host' => $config['ip_olt'],
            'port' => $config['porta_olt'],
            'method' => strtoupper($resultado['connection_type']),
            'status' => 'Conexão estabelecida com sucesso',
            'timestamp' => date('Y-m-d H:i:s')
        );
        
        // Se tiver output do comando de teste, incluir
        if (isset($resultado['output']) && !empty($resultado['output'])) {
            $response['details']['test_output'] = substr($resultado['output'], 0, 200) . '...';
        }
    } else {
        // Análise detalhada do erro
        $error_analysis = analyzeConnectionError($resultado['message'], $config);
        $response['details'] = $error_analysis;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage(),
        'details' => array(
            'error_type' => 'exception',
            'timestamp' => date('Y-m-d H:i:s')
        )
    ));
}

function analyzeConnectionError($error_message, $config) {
    $details = array(
        'host' => $config['ip_olt'],
        'port' => $config['porta_olt'],
        'attempted_method' => strtoupper($config['tipo_conexao']),
        'error_type' => 'unknown',
        'suggestions' => array(),
        'timestamp' => date('Y-m-d H:i:s')
    );
    
    $error_lower = strtolower($error_message);
    
    // Análise específica para erros SSH
    if (strpos($error_lower, 'no matching key exchange') !== false) {
        $details['error_type'] = 'ssh_key_exchange';
        $details['description'] = 'Métodos de troca de chave SSH incompatíveis';
        $details['suggestions'] = array(
            'A OLT usa métodos de criptografia SSH antigos (diffie-hellman-group1-sha1)',
            'O sistema tentará automaticamente usar Telnet como alternativa',
            'Considere configurar a conexão como Telnet se SSH continuar falhando',
            'Verifique se a porta SSH está correta (geralmente 22)'
        );
    } elseif (strpos($error_lower, 'no matching cipher') !== false) {
        $details['error_type'] = 'ssh_cipher';
        $details['description'] = 'Algoritmos de criptografia SSH incompatíveis';
        $details['suggestions'] = array(
            'A OLT usa algoritmos de criptografia SSH antigos',
            'O sistema incluiu suporte para 3DES e Blowfish',
            'Tente usar Telnet se SSH continuar falhando'
        );
    } elseif (strpos($error_lower, 'connection refused') !== false) {
        $details['error_type'] = 'connection_refused';
        $details['description'] = 'Conexão recusada pelo servidor';
        $details['suggestions'] = array(
            'Verifique se o IP da OLT está correto: ' . $config['ip_olt'],
            'Verifique se a porta está correta: ' . $config['porta_olt'],
            'Confirme se o serviço SSH/Telnet está ativo na OLT',
            'Verifique conectividade de rede (ping)'
        );
    } elseif (strpos($error_lower, 'timeout') !== false || strpos($error_lower, 'timed out') !== false) {
        $details['error_type'] = 'timeout';
        $details['description'] = 'Timeout na conexão';
        $details['suggestions'] = array(
            'Verifique conectividade de rede com a OLT',
            'Confirme se o IP está correto: ' . $config['ip_olt'],
            'Verifique se não há firewall bloqueando a conexão',
            'Tente aumentar o timeout de conexão'
        );
    } elseif (strpos($error_lower, 'authentication') !== false || strpos($error_lower, 'login') !== false) {
        $details['error_type'] = 'authentication';
        $details['description'] = 'Falha na autenticação';
        $details['suggestions'] = array(
            'Verifique o usuário: ' . $config['usuario_olt'],
            'Confirme se a senha está correta',
            'Verifique se a conta não está bloqueada na OLT',
            'Confirme se o usuário tem privilégios adequados'
        );
    } elseif (strpos($error_lower, 'ssh2') !== false && strpos($error_lower, 'not') !== false) {
        $details['error_type'] = 'ssh2_extension';
        $details['description'] = 'Extensão SSH2 do PHP não está disponível';
        $details['suggestions'] = array(
            'Instale a extensão SSH2 do PHP: apt-get install php-ssh2',
            'Reinicie o servidor web após a instalação',
            'Use Telnet como alternativa temporária',
            'Verifique se a extensão está habilitada no php.ini'
        );
    } elseif (strpos($error_lower, 'host key') !== false) {
        $details['error_type'] = 'host_key';
        $details['description'] = 'Problema com chave do host SSH';
        $details['suggestions'] = array(
            'A OLT pode estar usando chaves SSH antigas',
            'O sistema foi configurado para aceitar ssh-rsa e ssh-dss',
            'Tente usar Telnet se o problema persistir'
        );
    }
    
    return $details;
}
?>

