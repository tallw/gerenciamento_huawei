<?php
require_once 'config/database.php';

class ConfigOLT {
    private $conn;
    private $table_name = "config_olt";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($nome_config, $ip_olt, $porta_olt, $usuario_olt, $senha_olt, $tipo_conexao) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome_config=:nome_config, ip_olt=:ip_olt, porta_olt=:porta_olt, 
                      usuario_olt=:usuario_olt, senha_olt=:senha_olt, tipo_conexao=:tipo_conexao";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nome_config", $nome_config);
        $stmt->bindParam(":ip_olt", $ip_olt);
        $stmt->bindParam(":porta_olt", $porta_olt);
        $stmt->bindParam(":usuario_olt", $usuario_olt);
        $stmt->bindParam(":senha_olt", $senha_olt);
        $stmt->bindParam(":tipo_conexao", $tipo_conexao);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY data_criacao DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getActive() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE ativa = 1 LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $nome_config, $ip_olt, $porta_olt, $usuario_olt, $senha_olt, $tipo_conexao) {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome_config=:nome_config, ip_olt=:ip_olt, porta_olt=:porta_olt, 
                      usuario_olt=:usuario_olt, senha_olt=:senha_olt, tipo_conexao=:tipo_conexao 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":nome_config", $nome_config);
        $stmt->bindParam(":ip_olt", $ip_olt);
        $stmt->bindParam(":porta_olt", $porta_olt);
        $stmt->bindParam(":usuario_olt", $usuario_olt);
        $stmt->bindParam(":senha_olt", $senha_olt);
        $stmt->bindParam(":tipo_conexao", $tipo_conexao);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function setActive($id) {
        // Desativar todas as configurações
        $query = "UPDATE " . $this->table_name . " SET ativa = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        // Ativar a configuração selecionada
        $query = "UPDATE " . $this->table_name . " SET ativa = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function updateConnectionStatus($id, $status, $update_last_connection = false) {
        $query = "UPDATE " . $this->table_name . " SET status_conexao = :status";
        
        if ($update_last_connection) {
            $query .= ", data_ultima_conexao = NOW()";
        }
        
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>

