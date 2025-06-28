<?php
require_once 'config/database.php';

class Cliente {
    private $conn;
    private $table_name = "sis_cliente";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($onu_ont, $switch, $porta_olt, $interface) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET onu_ont=:onu_ont, switch=:switch, porta_olt=:porta_olt, interface=:interface";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":onu_ont", $onu_ont);
        $stmt->bindParam(":switch", $switch);
        $stmt->bindParam(":porta_olt", $porta_olt);
        $stmt->bindParam(":interface", $interface);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY data_provisionamento DESC";
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

    public function findBySN($onu_ont) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE onu_ont = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $onu_ont);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    public function deleteBySN($onu_ont) {
        $query = "DELETE FROM " . $this->table_name . " WHERE onu_ont = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $onu_ont);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>

