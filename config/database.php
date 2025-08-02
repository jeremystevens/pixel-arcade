<?php
/**
 * Database Configuration for Retro Arcade High Scores
 * SQLite database setup and connection management
 */

class Database {
    private static $instance = null;
    private $connection;
    private $dbPath;
    
    private function __construct() {
        $this->dbPath = __DIR__ . '/../data/highscores.db';
        $this->initializeDatabase();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        if ($this->connection === null) {
            try {
                // Ensure data directory exists
                $dataDir = dirname($this->dbPath);
                if (!is_dir($dataDir)) {
                    mkdir($dataDir, 0755, true);
                }
                
                // Create SQLite connection
                $this->connection = new PDO('sqlite:' . $this->dbPath);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                
                // Enable foreign keys
                $this->connection->exec('PRAGMA foreign_keys = ON');
                
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                throw new Exception('Database connection failed');
            }
        }
        
        return $this->connection;
    }
    
    /**
     * Initialize database schema
     */
    private function initializeDatabase() {
        try {
            $conn = $this->getConnection();
            
            // Create games table
            $conn->exec("
                CREATE TABLE IF NOT EXISTS games (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    slug VARCHAR(50) UNIQUE NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    year INTEGER,
                    developer VARCHAR(100),
                    genre VARCHAR(50),
                    description TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Create high_scores table
            $conn->exec("
                CREATE TABLE IF NOT EXISTS high_scores (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    game_slug VARCHAR(50) NOT NULL,
                    player_name VARCHAR(50) NOT NULL,
                    score INTEGER NOT NULL,
                    level_reached VARCHAR(20),
                    date_achieved DATE NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (game_slug) REFERENCES games(slug)
                )
            ");
            
            // Create indexes for better performance
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_game_slug ON high_scores(game_slug)");
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_score ON high_scores(score DESC)");
            $conn->exec("CREATE INDEX IF NOT EXISTS idx_date ON high_scores(date_achieved DESC)");
            
            // Insert default games if table is empty
            $this->insertDefaultGames();
            
        } catch (PDOException $e) {
            error_log('Database initialization failed: ' . $e->getMessage());
            throw new Exception('Database initialization failed');
        }
    }
    
    /**
     * Insert default games data
     */
    private function insertDefaultGames() {
        try {
            $conn = $this->getConnection();
            
            // Check if games already exist
            $stmt = $conn->query("SELECT COUNT(*) as count FROM games");
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return; // Games already exist
            }
            
            // Default games data
            $games = [
                [
                    'slug' => 'contra',
                    'name' => 'Contra',
                    'year' => 1987,
                    'developer' => 'Konami',
                    'genre' => 'Run and Gun',
                    'description' => 'Classic side-scrolling shooter with the famous Konami Code.'
                ],
                [
                    'slug' => 'pacman',
                    'name' => 'Pac-Man',
                    'year' => 1980,
                    'developer' => 'Namco',
                    'genre' => 'Maze',
                    'description' => 'The legendary dot-eating arcade game that started it all.'
                ],
                [
                    'slug' => 'galaga',
                    'name' => 'Galaga',
                    'year' => 1981,
                    'developer' => 'Namco',
                    'genre' => 'Shoot em up',
                    'description' => 'Space shooter with challenging enemy formations and bonus stages.'
                ],
                [
                    'slug' => 'donkey-kong',
                    'name' => 'Donkey Kong',
                    'year' => 1981,
                    'developer' => 'Nintendo',
                    'genre' => 'Platform',
                    'description' => 'Mario\'s first adventure climbing construction sites to save Pauline.'
                ]
            ];
            
            $insertStmt = $conn->prepare("
                INSERT INTO games (slug, name, year, developer, genre, description) 
                VALUES (:slug, :name, :year, :developer, :genre, :description)
            ");
            
            foreach ($games as $game) {
                $insertStmt->execute($game);
            }
            
            error_log('Default games inserted successfully');
            
        } catch (PDOException $e) {
            error_log('Failed to insert default games: ' . $e->getMessage());
        }
    }
    
    /**
     * Execute a prepared statement safely
     */
    public function execute($sql, $params = []) {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Database query failed: ' . $e->getMessage());
            throw new Exception('Database query failed');
        }
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->getConnection()->rollback();
    }
    
    /**
     * Close database connection
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * Get database file path (for backup purposes)
     */
    public function getDatabasePath() {
        return $this->dbPath;
    }
}

/**
 * Get database instance (helper function)
 */
function getDatabase() {
    return Database::getInstance();
}

/**
 * Test database connection
 */
function testDatabaseConnection() {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Simple test query
        $stmt = $conn->query("SELECT COUNT(*) as count FROM games");
        $result = $stmt->fetch();
        
        return [
            'success' => true,
            'message' => 'Database connection successful',
            'games_count' => $result['count']
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
    }
}

// Initialize database on include
try {
    Database::getInstance();
} catch (Exception $e) {
    error_log('Failed to initialize database: ' . $e->getMessage());
}
?>
