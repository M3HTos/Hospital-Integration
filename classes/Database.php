<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    function __construct($host, $db, $user, $password)
    {
        $this->host = $host;
        $this->db_name = $db;
        $this->username = $user;
        $this->password = $password;
    }

    public function getConnection(){

        $this->conn = null;

        try {
            $this->conn = new PDO("pgsql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
        } catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

}
?>