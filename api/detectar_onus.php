<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../classes/OLTConnection.php';
require_once '../classes/ConfigOLT.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    try {
        // Buscar configuração ativa da OLT
        $configOLT = new ConfigOLT();
        $config = $configOLT->getActive();
        
        if (!$config) {
            http_response_code(400);
            echo json_encode(array("message" => "Nenhuma configuração de OLT ativa encontrada"));
            exit;
        }

        // Conectar à OLT
        $olt = new OLTConnection($config['ip_olt'], $config['porta_olt'], 
                                $config['usuario_olt'], $config['senha_olt'], 
                                $config['tipo_conexao']);
        
        $olt->connect();

        // Detectar ONUs
        $interface = isset($data->interface) ? $data->interface : 'all';
        $onus_detectadas = $olt->detectONUs($interface);

        $olt->disconnect();

        // Atualizar status da conexão
        $configOLT->updateConnectionStatus($config['id'], 'conectado', true);

        http_response_code(200);
        echo json_encode(array(
            "message" => "Detecção de ONUs concluída",
            "total_onus" => count($onus_detectadas),
            "onus" => $onus_detectadas,
            "interface_pesquisada" => $interface
        ));

    } catch (Exception $e) {
        // Atualizar status de erro se houver configuração
        if (isset($config)) {
            $configOLT->updateConnectionStatus($config['id'], 'erro');
        }
        
        http_response_code(500);
        echo json_encode(array("message" => "Erro ao detectar ONUs: " . $e->getMessage()));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido"));
}
?>

