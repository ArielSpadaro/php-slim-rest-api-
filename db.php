<?php 
class DB {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;

    public function connect() {
        try{
            $conn = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->user, $this->pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        }catch(PDOException $e){
            header('Content-Type: application/json');
            http_response_code(500);
            $error = array(
                "code" => 500,
                "message" => $e->getMessage()
            );
            echo json_encode($error);
            exit;
        }      
    }
}
?>