<?php
// Arquivo: php/config/database.php

class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;

        // Caminho para o arquivo .env (na raiz do projeto, subindo duas pastas)
        $envPath = __DIR__ . '/../../.env';
        
        // Valores padrão (fallback) caso o arquivo .env não exista
        $host = '127.0.0.1';
        $db_name = 'war_thunder_wiki';
        $username = 'root';
        $password = '';

        // Se o .env existir, captura as credenciais blindadas
        if (file_exists($envPath)) {
            $env = parse_ini_file($envPath);
            if ($env) {
                $host = $env['DB_HOST'] ?? $host;
                $db_name = $env['DB_NAME'] ?? $db_name;
                $username = $env['DB_USER'] ?? $username;
                $password = $env['DB_PASS'] ?? $password;
            }
        }

        try {
            // Criação da instância PDO
            $this->conn = new PDO(
                "mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4",
                $username,
                $password
            );
            
            // Configura o PDO para lançar exceções em caso de erros severos
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch(PDOException $exception) {
            echo "Erro de Conexão Tática: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>