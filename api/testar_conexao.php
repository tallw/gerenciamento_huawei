<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../classes/OLTConnection.php';
require_once '../classes/ConfigOLT.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->config_id)) {
        // Testar usando configuração salva
        $configOLT = new ConfigOLT();
        $config = $configOLT->readOne($data->config_id);
        
        if (!$config) {
            http_response_code(404);
            echo json_encode(array("message" => "Configuração não encontrada"));
            exit;
        }
        
        $olt = new OLTConnection($config['ip_olt'], $config['porta_olt'], 
                                $config['usuario_olt'], $config['senha_olt'], 
                                $config['tipo_conexao']);
        
        $result = $olt->testConnection();
        
        // Atualizar status da conexão no banco
        if ($result['success']) {
            $configOLT->updateConnectionStatus($data->config_id, 'conectado', true);
        } else {
            $configOLT->updateConnectionStatus($data->config_id, 'erro');
        }
        
        echo json_encode($result);
        
    } elseif (!empty($data->ip_olt) && !empty($data->porta_olt) && !empty($data->usuario_olt) && 
              !empty($data->senha_olt) && !empty($data->tipo_conexao)) {
        
        // Testar usando dados fornecidos diretamente
        $olt = new OLTConnection($data->ip_olt, $data->porta_olt, 
                                $data->usuario_olt, $data->senha_olt, 
                                $data->tipo_conexao);
        
        $result = $olt->testConnection();
        echo json_encode($result);
        
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Dados incompletos"));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido"));
}
?>

