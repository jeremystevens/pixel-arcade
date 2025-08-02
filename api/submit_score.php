<?php
/**
 * Submit Score API Endpoint
 * Handles new high score submissions for retro arcade games
 */

// CORS headers for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Get and validate input data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields - support both 'initials' and 'player_name'
    if (!isset($data['game_slug']) || empty($data['game_slug'])) {
        throw new Exception("Missing required field: game_slug");
    }
    
    if (!isset($data['score']) || empty($data['score'])) {
        throw new Exception("Missing required field: score");
    }
    
    // Support both 'initials' (for Lua script) and 'player_name' (for web form)
    $playerName = null;
    if (isset($data['initials']) && !empty($data['initials'])) {
        $playerName = sanitizeInput($data['initials']);
    } elseif (isset($data['player_name']) && !empty($data['player_name'])) {
        $playerName = sanitizeInput($data['player_name']);
    } else {
        throw new Exception("Missing required field: either 'initials' or 'player_name'");
    }
    
    // Sanitize and validate input
    $gameSlug = sanitizeInput($data['game_slug']);
    $score = (int) $data['score'];
    $levelReached = isset($data['level_reached']) ? sanitizeInput($data['level_reached']) : null;
    
    // Validate game slug exists
    $validGames = ['contra', 'pacman', 'galaga', 'donkey-kong'];
    if (!in_array($gameSlug, $validGames)) {
        throw new Exception('Invalid game slug');
    }
    
    // Validate player name/initials (1-20 characters for initials, 3-20 for full names)
    $minLength = (isset($data['initials']) && !empty($data['initials'])) ? 1 : 3;
    
    if (strlen($playerName) < $minLength || strlen($playerName) > 20) {
        throw new Exception("Player name must be {$minLength}-20 characters");
    }
    
    if (!preg_match('/^[A-Za-z0-9\s\-_\.]+$/', $playerName)) {
        throw new Exception('Player name can only contain letters, numbers, spaces, hyphens, underscores, and periods');
    }
    
    // Validate score (must be positive)
    if ($score <= 0) {
        throw new Exception('Score must be a positive number');
    }
    
    // Check for reasonable score limits (anti-cheat)
    $maxScores = [
        'contra' => 10000000,      // 10 million max
        'pacman' => 5000000,       // 5 million max  
        'galaga' => 3000000,       // 3 million max
        'donkey-kong' => 2000000   // 2 million max
    ];
    
    if ($score > $maxScores[$gameSlug]) {
        throw new Exception('Score exceeds maximum allowed for this game');
    }
    
    // Get database instance
    $db = getDatabase();
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Insert the new score
        $sql = "
            INSERT INTO high_scores (game_slug, player_name, score, level_reached, date_achieved) 
            VALUES (:game_slug, :player_name, :score, :level_reached, :date_achieved)
        ";
        
        $params = [
            ':game_slug' => $gameSlug,
            ':player_name' => $playerName,
            ':score' => $score,
            ':level_reached' => $levelReached,
            ':date_achieved' => date('Y-m-d')
        ];
        
        $stmt = $db->execute($sql, $params);
        $scoreId = $db->lastInsertId();
        
        // Get the rank of this score
        $rankSql = "
            SELECT COUNT(*) + 1 as rank 
            FROM high_scores 
            WHERE game_slug = :game_slug AND score > :score
        ";
        
        $rankStmt = $db->execute($rankSql, [
            ':game_slug' => $gameSlug,
            ':score' => $score
        ]);
        
        $rankResult = $rankStmt->fetch();
        $rank = $rankResult['rank'];
        
        // Get total scores for this game
        $totalSql = "SELECT COUNT(*) as total FROM high_scores WHERE game_slug = :game_slug";
        $totalStmt = $db->execute($totalSql, [':game_slug' => $gameSlug]);
        $totalResult = $totalStmt->fetch();
        $totalScores = $totalResult['total'];
        
        // Check if this is a new high score
        $highScoreSql = "
            SELECT MAX(score) as max_score 
            FROM high_scores 
            WHERE game_slug = :game_slug AND id != :score_id
        ";
        
        $highScoreStmt = $db->execute($highScoreSql, [
            ':game_slug' => $gameSlug,
            ':score_id' => $scoreId
        ]);
        
        $highScoreResult = $highScoreStmt->fetch();
        $isNewHighScore = ($highScoreResult['max_score'] === null || $score > $highScoreResult['max_score']);
        
        // Commit transaction
        $db->commit();
        
        // Return success response with additional data
        echo json_encode([
            'success' => true,
            'message' => 'Score submitted successfully!',
            'data' => [
                'score_id' => $scoreId,
                'rank' => $rank,
                'total_scores' => $totalScores,
                'is_new_high_score' => $isNewHighScore,
                'player_name' => $playerName,
                'score' => number_format($score),
                'game' => $gameSlug,
                'date' => date('Y-m-d')
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    // Log error for debugging
    error_log('Score submission error: ' . $e->getMessage());
}
?>