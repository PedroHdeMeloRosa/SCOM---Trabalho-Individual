<?php
// Arquivo: php/config/database.php

class Database {
    // Configurações isoladas do banco de dados
    private $host = "127.0.0.1";
    private $db_name = "war_thunder_wiki";
    private $username = "root"; // Usuário padrão do XAMPP
    private $password = "";     // Senha padrão do XAMPP (vazia)
    public $conn;

    // Método para estabelecer a conexão
    public function getConnection() {
        $this->conn = null;

        try {
            // Criação da instância PDO com proteção de charset e tratamento de erros
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            
            // Configura o PDO para lançar exceções em caso de erros severos
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch(PDOException $exception) {
            // Tratamento centralizado de erros
            echo "Erro de Conexão Tática: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>