<?php
class Database {
    private $host = 'localhost';
    private $username = 'root';  // Replace with your MySQL username
    private $password = '';      // Replace with your MySQL password 
    private $dbname = 'user_auth';
    public $conn;

    public function __construct() {
        // Create connection
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);

        // Check connection
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function closeConnection() {
        $this->conn->close();
    }
}
?>
