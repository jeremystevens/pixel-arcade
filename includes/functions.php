<?php
/**
 * Core Functions for Retro Arcade High Scores
 * Database interaction and utility functions
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get all available games with their basic information and high scores
 * @return array Array of games with basic info and high scores
 */
function getAvailableGames() {
    try {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                g.slug,
                g.name,
                g.year,
                g.developer,
                g.genre,
                g.description,
                COALESCE(MAX(hs.score), 0) as high_score,
                COUNT(hs.id) as score_count
            FROM games g
            LEFT JOIN high_scores hs ON g.slug = hs.game_slug
            GROUP BY g.slug, g.name, g.year, g.developer, g.genre, g.description
            ORDER BY g.name ASC
        ";
        
        $stmt = $db->execute($sql);
        $games = $stmt->fetchAll();
        
        // Ensure we have some basic games even if database is empty
        if (empty($games)) {
            error_log('No games found in database');
            return getDefaultGames();
        }
        
        return $games;
        
    } catch (Exception $e) {
        error_log('Error fetching available games: ' . $e->getMessage());
        return getDefaultGames();
    }
}

/**
 * Get detailed information for a specific game
 * @param string $gameSlug The game slug identifier
 * @return array|null Game information or null if not found
 */
function getGameInfo($gameSlug) {
    try {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                g.*,
                COALESCE(MAX(hs.score), 0) as high_score,
                COUNT(hs.id) as total_scores,
                COALESCE(AVG(hs.score), 0) as average_score
            FROM games g
            LEFT JOIN high_scores hs ON g.slug = hs.game_slug
            WHERE g.slug = :slug
            GROUP BY g.slug
        ";
        
        $stmt = $db->execute($sql, ['slug' => $gameSlug]);
        $game = $stmt->fetch();
        
        if (!$game) {
            error_log('Game not found: ' . $gameSlug);
            return null;
        }
        
        return $game;
        
    } catch (Exception $e) {
        error_log('Error fetching game info: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get high scores for a specific game
 * @param string $gameSlug The game slug identifier
 * @param int $limit Maximum number of scores to return
 * @return array Array of high scores
 */
function getGameScores($gameSlug, $limit = 50) {
    try {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                player_name,
                score,
                level_reached,
                date_achieved,
                created_at
            FROM high_scores 
            WHERE game_slug = :slug 
            ORDER BY score DESC, date_achieved ASC
            LIMIT :limit
        ";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bindValue(':slug', $gameSlug, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $scores = $stmt->fetchAll();
        
        return $scores;
        
    } catch (Exception $e) {
        error_log('Error fetching game scores: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get total number of scores across all games
 * @return int Total score count
 */
function getTotalScores() {
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT COUNT(*) as total FROM high_scores";
        $stmt = $db->execute($sql);
        $result = $stmt->fetch();
        
        return (int)$result['total'];
        
    } catch (Exception $e) {
        error_log('Error fetching total scores: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get total number of unique players
 * @return int Total player count
 */
function getTotalPlayers() {
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT COUNT(DISTINCT player_name) as total FROM high_scores";
        $stmt = $db->execute($sql);
        $result = $stmt->fetch();
        
        return (int)$result['total'];
        
    } catch (Exception $e) {
        error_log('Error fetching total players: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get recent high scores across all games
 * @param int $limit Maximum number of scores to return
 * @return array Array of recent scores with game info
 */
function getRecentScores($limit = 10) {
    try {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                hs.player_name,
                hs.score,
                hs.level_reached,
                hs.date_achieved,
                hs.game_slug,
                g.name as game_name
            FROM high_scores hs
            JOIN games g ON hs.game_slug = g.slug
            ORDER BY hs.created_at DESC
            LIMIT :limit
        ";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log('Error fetching recent scores: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get top players across all games
 * @param int $limit Maximum number of players to return
 * @return array Array of top players with their best scores
 */
function getTopPlayers($limit = 10) {
    try {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                player_name,
                MAX(score) as best_score,
                COUNT(*) as total_scores,
                AVG(score) as average_score,
                game_slug,
                g.name as game_name
            FROM high_scores hs
            JOIN games g ON hs.game_slug = g.slug
            GROUP BY player_name
            ORDER BY best_score DESC
            LIMIT :limit
        ";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log('Error fetching top players: ' . $e->getMessage());
        return [];
    }
}

/**
 * Add a new high score
 * @param string $gameSlug Game identifier
 * @param string $playerName Player name
 * @param int $score Score value
 * @param string $levelReached Level reached (optional)
 * @return bool Success status
 */
function addHighScore($gameSlug, $playerName, $score, $levelReached = null) {
    try {
        // Validate input
        if (empty($gameSlug) || empty($playerName) || !is_numeric($score)) {
            error_log('Invalid high score data provided');
            return false;
        }
        
        // Sanitize input
        $playerName = trim($playerName);
        $score = (int)$score;
        
        if (strlen($playerName) > 50) {
            $playerName = substr($playerName, 0, 50);
        }
        
        // Verify game exists
        $gameInfo = getGameInfo($gameSlug);
        if (!$gameInfo) {
            error_log('Cannot add score for non-existent game: ' . $gameSlug);
            return false;
        }
        
        $db = Database::getInstance();
        
        $sql = "
            INSERT INTO high_scores (game_slug, player_name, score, level_reached, date_achieved)
            VALUES (:game_slug, :player_name, :score, :level_reached, :date_achieved)
        ";
        
        $params = [
            'game_slug' => $gameSlug,
            'player_name' => $playerName,
            'score' => $score,
            'level_reached' => $levelReached,
            'date_achieved' => date('Y-m-d')
        ];
        
        $stmt = $db->execute($sql, $params);
        
        error_log("High score added: {$playerName} - {$score} for {$gameSlug}");
        return true;
        
    } catch (Exception $e) {
        error_log('Error adding high score: ' . $e->getMessage());
        return false;
    }
}

/**
 * Check if a score qualifies as a high score for a game
 * @param string $gameSlug Game identifier
 * @param int $score Score to check
 * @param int $maxScores Maximum scores to keep per game
 * @return bool True if score qualifies
 */
function isHighScore($gameSlug, $score, $maxScores = 50) {
    try {
        $currentScores = getGameScores($gameSlug, $maxScores);
        
        if (count($currentScores) < $maxScores) {
            return true; // Not enough scores yet
        }
        
        $lowestHighScore = end($currentScores);
        return $score > $lowestHighScore['score'];
        
    } catch (Exception $e) {
        error_log('Error checking high score: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get game statistics
 * @param string $gameSlug Game identifier
 * @return array Game statistics
 */
function getGameStats($gameSlug) {
    try {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                COUNT(*) as total_scores,
                MAX(score) as highest_score,
                MIN(score) as lowest_score,
                AVG(score) as average_score,
                COUNT(DISTINCT player_name) as unique_players
            FROM high_scores 
            WHERE game_slug = :slug
        ";
        
        $stmt = $db->execute($sql, ['slug' => $gameSlug]);
        $stats = $stmt->fetch();
        
        return [
            'total_scores' => (int)$stats['total_scores'],
            'highest_score' => (int)$stats['highest_score'],
            'lowest_score' => (int)$stats['lowest_score'],
            'average_score' => round((float)$stats['average_score'], 2),
            'unique_players' => (int)$stats['unique_players']
        ];
        
    } catch (Exception $e) {
        error_log('Error fetching game stats: ' . $e->getMessage());
        return [
            'total_scores' => 0,
            'highest_score' => 0,
            'lowest_score' => 0,
            'average_score' => 0,
            'unique_players' => 0
        ];
    }
}

/**
 * Search for games by name or developer
 * @param string $query Search query
 * @return array Array of matching games
 */
function searchGames($query) {
    try {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                g.slug,
                g.name,
                g.year,
                g.developer,
                g.genre,
                COALESCE(MAX(hs.score), 0) as high_score
            FROM games g
            LEFT JOIN high_scores hs ON g.slug = hs.game_slug
            WHERE g.name LIKE :query OR g.developer LIKE :query
            GROUP BY g.slug, g.name, g.year, g.developer, g.genre
            ORDER BY g.name ASC
        ";
        
        $searchTerm = '%' . trim($query) . '%';
        $stmt = $db->execute($sql, ['query' => $searchTerm]);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log('Error searching games: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get default games data (fallback when database is empty)
 * @return array Default games array
 */
function getDefaultGames() {
    return [
        [
            'slug' => 'contra',
            'name' => 'Contra',
            'year' => 1987,
            'developer' => 'Konami',
            'genre' => 'Run and Gun',
            'high_score' => 0,
            'score_count' => 0
        ],
        [
            'slug' => 'pacman',
            'name' => 'Pac-Man',
            'year' => 1980,
            'developer' => 'Namco',
            'genre' => 'Maze',
            'high_score' => 0,
            'score_count' => 0
        ],
        [
            'slug' => 'galaga',
            'name' => 'Galaga',
            'year' => 1981,
            'developer' => 'Namco',
            'genre' => 'Shoot em up',
            'high_score' => 0,
            'score_count' => 0
        ],
        [
            'slug' => 'donkey-kong',
            'name' => 'Donkey Kong',
            'year' => 1981,
            'developer' => 'Nintendo',
            'genre' => 'Platform',
            'high_score' => 0,
            'score_count' => 0
        ]
    ];
}

/**
 * Validate game slug format
 * @param string $slug Game slug to validate
 * @return bool True if valid
 */
function isValidGameSlug($slug) {
    return !empty($slug) && preg_match('/^[a-z0-9-]+$/', $slug) && strlen($slug) <= 50;
}

/**
 * Validate player name
 * @param string $name Player name to validate
 * @return bool True if valid
 */
function isValidPlayerName($name) {
    $name = trim($name);
    return !empty($name) && strlen($name) <= 50 && preg_match('/^[a-zA-Z0-9\s\-_\.]+$/', $name);
}

/**
 * Format score for display
 * @param int $score Score value
 * @return string Formatted score
 */
function formatScore($score) {
    return number_format((int)$score);
}

/**
 * Get leaderboard data for homepage
 * @param int $limit Number of top scores to return
 * @return array Leaderboard data
 */
function getLeaderboard($limit = 5) {
    try {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                hs.player_name,
                hs.score,
                hs.game_slug,
                g.name as game_name,
                hs.date_achieved
            FROM high_scores hs
            JOIN games g ON hs.game_slug = g.slug
            ORDER BY hs.score DESC
            LIMIT :limit
        ";
        
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log('Error fetching leaderboard: ' . $e->getMessage());
        return [];
    }
}

/**
 * Clean old high scores to maintain database size
 * Keep only top N scores per game
 * @param int $keepPerGame Number of scores to keep per game
 * @return bool Success status
 */
function cleanOldScores($keepPerGame = 100) {
    try {
        $db = Database::getInstance();
        $games = getAvailableGames();
        
        foreach ($games as $game) {
            $sql = "
                DELETE FROM high_scores 
                WHERE game_slug = :game_slug 
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM high_scores 
                        WHERE game_slug = :game_slug 
                        ORDER BY score DESC, date_achieved ASC 
                        LIMIT :keep_count
                    ) as top_scores
                )
            ";
            
            $stmt = $db->getConnection()->prepare($sql);
            $stmt->bindValue(':game_slug', $game['slug'], PDO::PARAM_STR);
            $stmt->bindValue(':keep_count', $keepPerGame, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log('Error cleaning old scores: ' . $e->getMessage());
        return false;
    }
}

/**
 * Export high scores to JSON
 * @param string $gameSlug Optional game slug to export specific game
 * @return string JSON data
 */
function exportScores($gameSlug = null) {
    try {
        $db = Database::getInstance();
        
        if ($gameSlug) {
            $sql = "
                SELECT hs.*, g.name as game_name
                FROM high_scores hs
                JOIN games g ON hs.game_slug = g.slug
                WHERE hs.game_slug = :slug
                ORDER BY hs.score DESC
            ";
            $stmt = $db->execute($sql, ['slug' => $gameSlug]);
        } else {
            $sql = "
                SELECT hs.*, g.name as game_name
                FROM high_scores hs
                JOIN games g ON hs.game_slug = g.slug
                ORDER BY hs.game_slug, hs.score DESC
            ";
            $stmt = $db->execute($sql);
        }
        
        $scores = $stmt->fetchAll();
        
        return json_encode([
            'export_date' => date('Y-m-d H:i:s'),
            'total_scores' => count($scores),
            'scores' => $scores
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        error_log('Error exporting scores: ' . $e->getMessage());
        return json_encode(['error' => 'Export failed']);
    }
}

/**
 * Sanitize input data for database operations
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    if (is_null($input)) {
        return null;
    }
    
    // Remove HTML tags and convert special characters
    $input = htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    
    // Remove any null bytes
    $input = str_replace("\0", '', $input);
    
    return $input;
}

/**
 * Validate and sanitize score input
 * @param mixed $score Score to validate
 * @return int Validated score
 */
function validateScore($score) {
    $score = (int) $score;
    return max(0, $score); // Ensure non-negative
}

/**
 * Generate a safe filename from input
 * @param string $input Input string
 * @return string Safe filename
 */
function generateSafeFilename($input) {
    $input = sanitizeInput($input);
    $input = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $input);
    return substr($input, 0, 255);
}

/**
 * Get total count of games for pagination
 * @return int Total number of games
 */
function getTotalGamesCount() {
    try {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) as count FROM games";
        $stmt = $db->execute($sql);
        $result = $stmt->fetch();
        return (int)$result['count'];
    } catch (Exception $e) {
        error_log('Error getting total games count: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get games with pagination
 * @param int $offset Starting position
 * @param int $limit Number of games to fetch
 * @return array Array of games
 */
function getGamesPaginated($offset, $limit) {
    try {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                g.slug,
                g.name,
                g.year,
                g.developer,
                g.genre,
                g.description
            FROM games g
            ORDER BY g.name ASC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $db->execute($sql, [
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log('Error fetching paginated games: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get the top score for a specific game
 * @param string $gameSlug Game identifier
 * @return array|null Top score data or null if no scores
 */
function getTopScore($gameSlug) {
    try {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                player_name,
                score,
                date_achieved
            FROM high_scores 
            WHERE game_slug = :game_slug 
            ORDER BY score DESC 
            LIMIT 1
        ";
        
        $stmt = $db->execute($sql, ['game_slug' => $gameSlug]);
        $result = $stmt->fetch();
        
        return $result ?: null;
        
    } catch (Exception $e) {
        error_log('Error fetching top score for ' . $gameSlug . ': ' . $e->getMessage());
        return null;
    }
}
?>
