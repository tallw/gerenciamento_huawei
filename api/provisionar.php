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
require_once '../classes/PerfilONU.php';
require_once '../classes/ConfigOLT.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->interface) && !empty($data->onu_id) && 
        !empty($data->sn) && !empty($data->perfil_id)) {

        try {
            // Buscar configuração ativa da OLT
            $configOLT = new ConfigOLT();
            $config = $configOLT->getActive();
            
            if (!$config) {
                http_response_code(400);
                echo json_encode(array("message" => "Nenhuma configuração de OLT ativa encontrada"));
                exit;
            }

            // Buscar perfil
            $perfilONU = new PerfilONU();
            $perfil = $perfilONU->readOne($data->perfil_id);
            
            if (!$perfil) {
                http_response_code(400);
                echo json_encode(array("message" => "Perfil não encontrado"));
                exit;
            }

            // Conectar à OLT
            $olt = new OLTConnection($config['ip_olt'], $config['porta_olt'], 
                                    $config['usuario_olt'], $config['senha_olt'], 
                                    $config['tipo_conexao']);
            $olt->connect();

            // Preparar dados do perfil
            $profile_data = [
                'lineprofile' => explode('-', $perfil['lineprofile_srvprofile'])[0],
                'srvprofile' => explode('-', $perfil['lineprofile_srvprofile'])[1],
                'vlan' => $perfil['vlan'],
                'gemport' => $perfil['gemport']
            ];

            // Provisionar ONU
            $result = $olt->provisionONU($data->interface, $data->onu_id, $data->sn, $profile_data);

            // Salvar no banco
            $cliente = new Cliente();
            $switch_info = $data->onu_id . " - " . (isset($data->descricao_equipamento) ? $data->descricao_equipamento : "ONU");
            
            if ($cliente->create($data->sn, $switch_info, $data->interface, $perfil['vlan'])) {
                $olt->disconnect();
                
                // Atualizar status da conexão
                $configOLT->updateConnectionStatus($config['id'], 'conectado', true);
                
                http_response_code(201);
                echo json_encode(array(
                    "message" => "ONU provisionada com sucesso",
                    "olt_output" => $result
                ));
            } else {
                $olt->disconnect();
                http_response_code(503);
                echo json_encode(array("message" => "Erro ao salvar no banco de dados"));
            }

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

