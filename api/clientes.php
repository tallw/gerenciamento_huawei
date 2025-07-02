<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../classes/Cliente.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $cliente = new Cliente();
    
    if (isset($_GET['id'])) {
        $cliente_data = $cliente->readOne($_GET['id']);
        if ($cliente_data) {
            echo json_encode($cliente_data);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Cliente não encontrado"));
        }
    } else {
        $stmt = $cliente->read();
        $clientes = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $clientes[] = $row;
        }
        echo json_encode($clientes);
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido"));
}
?>

