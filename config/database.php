<?php

// Configurar timezone para São Paulo, Brasil
date_default_timezone_set('America/Sao_Paulo');

class Database {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $port;
    private $queryParams;
    private $pdo = null;
    
    public function __construct() {
        // Parse DATABASE_URL from environment
        $databaseUrl = $_ENV['DATABASE_URL'] ?? '';
        if ($databaseUrl) {
            $urlParts = parse_url($databaseUrl);
            if ($urlParts === false) {
                throw new Exception("Invalid DATABASE_URL format");
            }
            
            $this->host = isset($urlParts['host']) ? urldecode($urlParts['host']) : ($_ENV['PGHOST'] ?? 'localhost');
            $this->port = $urlParts['port'] ?? $_ENV['PGPORT'] ?? 5432;
            
            $dbPath = isset($urlParts['path']) ? ltrim(urldecode($urlParts['path']), '/') : '';
            $this->dbname = $dbPath ?: ($_ENV['PGDATABASE'] ?? 'access_control');
            if (empty($this->dbname)) {
                throw new Exception("Database name is required");
            }
            
            $this->username = isset($urlParts['user']) ? urldecode($urlParts['user']) : ($_ENV['PGUSER'] ?? 'postgres');
            $this->password = isset($urlParts['pass']) ? urldecode($urlParts['pass']) : ($_ENV['PGPASSWORD'] ?? '');
            
            // Handle query parameters (SSL, etc.)
            $this->queryParams = '';
            if (isset($urlParts['query'])) {
                $query = [];
                parse_str($urlParts['query'], $query);
                $supportedParams = ['sslmode', 'sslcert', 'sslkey', 'sslrootcert'];
                $params = [];
                foreach ($supportedParams as $param) {
                    if (isset($query[$param])) {
                        $params[] = "$param=" . $query[$param];
                    }
                }
                $this->queryParams = implode(';', $params);
            }
        } else {
            // Fallback to individual environment variables
            $this->host = $_ENV['PGHOST'] ?? 'localhost';
            $this->port = $_ENV['PGPORT'] ?? 5432;
            $this->dbname = $_ENV['PGDATABASE'] ?? 'access_control';
            $this->username = $_ENV['PGUSER'] ?? 'postgres';
            $this->password = $_ENV['PGPASSWORD'] ?? '';
            $this->queryParams = '';
        }
        
        // Ensure dbname has a default value
        if (empty($this->dbname)) {
            $this->dbname = 'access_control';
        }
    }
    
    public function connect() {
        if ($this->pdo === null) {
            try {
                // Check if PDO PostgreSQL extension is available
                if (!extension_loaded('pdo_pgsql')) {
                    throw new Exception("PDO PostgreSQL extension (pdo_pgsql) is not installed. Please install php-pgsql extension.");
                }
                
                $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";
                if (!empty($this->queryParams)) {
                    $dsn .= ";" . $this->queryParams;
                }
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                
                // Configurar timezone do PostgreSQL para São Paulo
                $this->pdo->exec("SET timezone = 'America/Sao_Paulo'");
            } catch (PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function lastInsertId() {
        return $this->connect()->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connect()->beginTransaction();
    }
    
    public function commit() {
        return $this->connect()->commit();
    }
    
    public function rollback() {
        return $this->connect()->rollback();
    }
}