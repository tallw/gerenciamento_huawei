<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../classes/PerfilONU.php';

$perfilONU = new PerfilONU();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            $perfil = $perfilONU->readOne($_GET['id']);
            if ($perfil) {
                echo json_encode($perfil);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Perfil não encontrado"));
            }
        } else {
            $stmt = $perfilONU->read();
            $perfis = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $perfis[] = $row;
            }
            echo json_encode($perfis);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->nome_perfil) && !empty($data->gemport) && 
            !empty($data->lineprofile_srvprofile) && !empty($data->vlan)) {
            
            if ($perfilONU->create($data->nome_perfil, $data->gemport, $data->lineprofile_srvprofile, $data->vlan)) {
                http_response_code(201);
                echo json_encode(array("message" => "Perfil criado com sucesso"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Erro ao criar perfil"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Dados incompletos"));
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->id) && !empty($data->nome_perfil) && !empty($data->gemport) && 
            !empty($data->lineprofile_srvprofile) && !empty($data->vlan)) {
            
            if ($perfilONU->update($data->id, $data->nome_perfil, $data->gemport, $data->lineprofile_srvprofile, $data->vlan)) {
                http_response_code(200);
                echo json_encode(array("message" => "Perfil atualizado com sucesso"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Erro ao atualizar perfil"));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Dados incompletos"));
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->id)) {
            if ($perfilONU->delete($data->id)) {
                http_response_code(200);
                echo json_encode(array("message" => "Perfil deletado com sucesso"));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Erro ao deletar perfil"));
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

