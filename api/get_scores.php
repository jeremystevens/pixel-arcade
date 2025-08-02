<?php
/**
 * Get Scores API Endpoint
 * Retrieves high scores for specific games or all games
 */

// CORS headers for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use GET.'
    ]);
    exit;
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Get query parameters
    $gameSlug = isset($_GET['game']) ? sanitizeInput($_GET['game']) : null;
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
    
    // Validate limit (max 100 records)
    if ($limit > 100) {
        $limit = 100;
    }
    
    // Get database instance
    $db = getDatabase();
    
    // Build SQL query
    $sql = "
        SELECT 
            id,
            game_slug,
            player_name,
            score,
            level_reached,
            date_achieved,
            created_at
        FROM high_scores
    ";
    
    $params = [];
    
    // Add game filter if specified
    if ($gameSlug) {
        $validGames = ['contra', 'pacman', 'galaga', 'donkey-kong'];
        if (!in_array($gameSlug, $validGames)) {
            throw new Exception('Invalid game slug');
        }
        
        $sql .= " WHERE game_slug = :game_slug";
        $params[':game_slug'] = $gameSlug;
    }
    
    // Order by score descending
    $sql .= " ORDER BY score DESC, date_achieved ASC";
    
    // Add limit and offset
    $sql .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    // Execute query
    $stmt = $db->execute($sql, $params);
    $scores = $stmt->fetchAll();
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM high_scores";
    $countParams = [];
    
    if ($gameSlug) {
        $countSql .= " WHERE game_slug = :game_slug";
        $countParams[':game_slug'] = $gameSlug;
    }
    
    $countStmt = $db->execute($countSql, $countParams);
    $countResult = $countStmt->fetch();
    $totalScores = $countResult['total'];
    
    // Format scores for response
    $formattedScores = array_map(function($score) {
        return [
            'id' => (int) $score['id'],
            'game_slug' => $score['game_slug'],
            'player_name' => $score['player_name'],
            'score' => (int) $score['score'],
            'formatted_score' => number_format($score['score']),
            'level_reached' => $score['level_reached'],
            'date_achieved' => $score['date_achieved'],
            'created_at' => $score['created_at']
        ];
    }, $scores);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'scores' => $formattedScores,
            'pagination' => [
                'total' => $totalScores,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalScores
            ],
            'game' => $gameSlug,
            'count' => count($formattedScores)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    
    // Log error for debugging
    error_log('Get scores error: ' . $e->getMessage());
}
?>