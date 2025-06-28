<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../classes/OLTConnection.php';
require_once '../classes/Cliente.php';
require_once '../classes/ConfigOLT.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->sn)) {

        try {
            // Buscar configuração ativa da OLT
            $configOLT = new ConfigOLT();
            $config = $configOLT->getActive();
            
            if (!$config) {
                http_response_code(400);
                echo json_encode(array("message" => "Nenhuma configuração de OLT ativa encontrada"));
                exit;
            }

            // Buscar cliente no banco
            $cliente = new Cliente();
            $cliente_data = $cliente->findBySN($data->sn);
            
            if (!$cliente_data) {
                http_response_code(404);
                echo json_encode(array("message" => "ONU não encontrada no banco de dados"));
                exit;
            }

            // Conectar à OLT
            $olt = new OLTConnection($config['ip_olt'], $config['porta_olt'], 
                                    $config['usuario_olt'], $config['senha_olt'], 
                                    $config['tipo_conexao']);
            $olt->connect();

            // Extrair ONU ID do campo switch
            $onu_id = explode(' - ', $cliente_data['switch'])[0];

            // Verificar potência
            $power_data = $olt->getONUPower($cliente_data['porta_olt'], $onu_id);
            
            $olt->disconnect();

            // Atualizar status da conexão
            $configOLT->updateConnectionStatus($config['id'], 'conectado', true);

            http_response_code(200);
            echo json_encode(array(
                "message" => "Potência verificada com sucesso",
                "power_data" => $power_data,
                "cliente_info" => $cliente_data
            ));

        } catch (Exception $e) {
            // Atualizar status de erro se houver configuração
            if (isset($config)) {
                $configOLT->updateConnectionStatus($config['id'], 'erro');
            }
            
            http_response_code(500);
            echo json_encode(array("message" => "Erro: " . $e->getMessage()));
        }

    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos"));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido"));
}
?>

