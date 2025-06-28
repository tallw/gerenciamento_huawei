<?php
require_once 'config/database.php';

class PerfilONU {
    private $conn;
    private $table_name = "perfis_onu";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($nome_perfil, $gemport, $lineprofile_srvprofile, $vlan) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome_perfil=:nome_perfil, gemport=:gemport, lineprofile_srvprofile=:lineprofile_srvprofile, vlan=:vlan";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nome_perfil", $nome_perfil);
        $stmt->bindParam(":gemport", $gemport);
        $stmt->bindParam(":lineprofile_srvprofile", $lineprofile_srvprofile);
        $stmt->bindParam(":vlan", $vlan);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nome_perfil";
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

    public function update($id, $nome_perfil, $gemport, $lineprofile_srvprofile, $vlan) {
        $query = "UPDATE " . $this->table_name . " 
                  SET nome_perfil=:nome_perfil, gemport=:gemport, lineprofile_srvprofile=:lineprofile_srvprofile, vlan=:vlan 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":nome_perfil", $nome_perfil);
        $stmt->bindParam(":gemport", $gemport);
        $stmt->bindParam(":lineprofile_srvprofile", $lineprofile_srvprofile);
        $stmt->bindParam(":vlan", $vlan);

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

