<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../classes/ConfigOLT.php';

$configOLT = new ConfigOLT();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            $config = $configOLT->readOne($_GET['id']);
            if ($config) {
                // Não retornar senha por segurança
                unset($config['senha_olt']);
                echo json_encode($config);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Configuração não encontrada"));
            }
        } elseif (isset($_GET['active'])) {
            $config = $configOLT->getActive();
            if ($config) {
                // Não retornar senha por segurança
                unset($config['senha_olt']);
                echo json_encode($config);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Nenhuma configuração ativa encontrada"));
            }
        } else {
            $stmt = $configOLT->read();
            $configs = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Não retornar senha por segurança
                unset($row['senha_olt']);
                $configs[] = $row;
            }
            echo json_encode($configs);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->nome_config) && !empty($data->ip_olt) && !empty($data->porta_olt) && 
            !empty($data->usuario_olt) && !empty($data->senha_olt) && !empty($data->tipo_conexao)) {
            
            if ($configOLT->create($data->nome_config, $data->ip_olt, $data->porta_olt, 
                                 $data->usuario_olt, $data->senha_olt, $data->tipo_conexao)) {
                http_response_code(201);
                echo json_encode(array("message" => "Configuração criada com sucesso"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Erro ao criar configuração"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Dados incompletos"));
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        
        if (isset($data->set_active) && !empty($data->id)) {
            // Definir configuração como ativa
            if ($configOLT->setActive($data->id)) {
                http_response_code(200);
                echo json_encode(array("message" => "Configuração ativada com sucesso"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Erro ao ativar configuração"));
            }
        } elseif (!empty($data->id) && !empty($data->nome_config) && !empty($data->ip_olt) && 
                  !empty($data->porta_olt) && !empty($data->usuario_olt) && !empty($data->senha_olt) && 
                  !empty($data->tipo_conexao)) {
            
            if ($configOLT->update($data->id, $data->nome_config, $data->ip_olt, $data->porta_olt, 
                                  $data->usuario_olt, $data->senha_olt, $data->tipo_conexao)) {
                http_response_code(200);
                echo json_encode(array("message" => "Configuração atualizada com sucesso"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Erro ao atualizar configuração"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Dados incompletos"));
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->id)) {
            if ($configOLT->delete($data->id)) {
                http_response_code(200);
                echo json_encode(array("message" => "Configuração deletada com sucesso"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Erro ao deletar configuração"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "ID não fornecido"));
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido"));
        break;
}
?>

