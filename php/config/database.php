<?php
// Arquivo: php/config/database.php

class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;
        $envPath = __DIR__ . '/../../.env';
        
        $host = '127.0.0.1';
        $db_name = 'war_thunder_wiki';
        $username = 'root';
        $password = '';

        if (file_exists($envPath)) {
            $env = parse_ini_file($envPath);
            if ($env) {
                $host = $env['DB_HOST'] ?? $host;
                $db_name = $env['DB_NAME'] ?? $db_name;
                $username = $env['DB_USER'] ?? $username;
                $password = $env['DB_PASS'] ?? $password;
            }
        }

        // Evita o atraso de 1.5s do DNS IPv6 do Windows quando escrito 'localhost'
        if (strtolower(trim($host)) === 'localhost') {
            $host = '127.0.0.1';
        }

        try {
            $this->conn = new PDO(
                "mysql:host={$host};dbname={$db_name};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT         => true // Reutiliza a conexão aberta (Ultra rápido)
                ]
            );
        } catch (PDOException $exception) {
            throw new Exception("Falha de conexão com a Base Central.");
        }

        return $this->conn;
    }
}
?>