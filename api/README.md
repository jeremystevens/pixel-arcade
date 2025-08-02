# Retro Arcade High Scores API

This API provides endpoints for submitting and retrieving high scores for classic arcade games.

## Base URL
```
http://your-domain.com/api/
```

## Authentication
No authentication required for current endpoints.

## Endpoints

### Submit Score
**POST** `/api/submit_score.php`

Submit a new high score for a game.

#### Request Body (JSON)
```json
{
  "game_slug": "pacman",
  "initials": "ABC",
  "score": 1250000,
  "level_reached": "12"
}
```

#### Supported Fields
- `game_slug` (required): Game identifier - one of: `contra`, `pacman`, `galaga`, `donkey-kong`
- `initials` OR `player_name` (required): Player initials (1-20 chars) or full name (3-20 chars)
- `score` (required): Score value (positive integer)
- `level_reached` (optional): Level reached in the game

#### Score Limits (Anti-cheat)
- Contra: 10,000,000 max
- Pac-Man: 5,000,000 max  
- Galaga: 3,000,000 max
- Donkey Kong: 2,000,000 max

#### Success Response (200)
```json
{
  "success": true,
  "message": "Score submitted successfully!",
  "data": {
    "score_id": 70,
    "rank": 16,
    "total_scores": 17,
    "is_new_high_score": false,
    "player_name": "ABC",
    "score": "1,250,000",
    "game": "pacman",
    "date": "2025-08-02"
  }
}
```

#### Error Response (400)
```json
{
  "success": false,
  "error": "Error message describing the issue"
}
```

### Get Scores
**GET** `/api/get_scores.php`

Retrieve high scores for games.

#### Query Parameters
- `game` (optional): Filter by game slug (`contra`, `pacman`, `galaga`, `donkey-kong`)
- `limit` (optional): Number of scores to return (default: 50, max: 100)
- `offset` (optional): Offset for pagination (default: 0)

#### Examples
```
GET /api/get_scores.php?game=pacman&limit=10
GET /api/get_scores.php?limit=5&offset=10
GET /api/get_scores.php
```

#### Success Response (200)
```json
{
  "success": true,
  "data": {
    "scores": [
      {
        "id": 21,
        "game_slug": "pacman",
        "player_name": "BILLY",
        "score": 3333360,
        "formatted_score": "3,333,360",
        "level_reached": null,
        "date_achieved": "2024-12-14",
        "created_at": "2025-08-02 02:43:59"
      }
    ],
    "pagination": {
      "total": 16,
      "limit": 5,
      "offset": 0,
      "has_more": true
    },
    "game": "pacman",
    "count": 5
  }
}
```

## BizHawk Lua Script Integration

Your Lua script should send POST requests to `/api/submit_score.php` with this JSON format:

```lua
-- Example for BizHawk Lua script
local json = require('json')
local http = require('socket.http')

function submitScore(gameSlug, initials, score, level)
    local data = {
        game_slug = gameSlug,
        initials = initials,
        score = score,
        level_reached = level
    }
    
    local jsonData = json.encode(data)
    
    local response = http.request{
        url = "http://your-domain.com/api/submit_score.php",
        method = "POST",
        headers = {
            ["Content-Type"] = "application/json",
            ["Content-Length"] = string.len(jsonData)
        },
        source = ltn12.source.string(jsonData)
    }
    
    return response
end

-- Usage example
submitScore("pacman", "ABC", 1250000, "12")
```

## Error Codes

- `400 Bad Request`: Invalid input data or validation errors
- `405 Method Not Allowed`: Wrong HTTP method used
- `500 Internal Server Error`: Server-side error

## Database Storage

Scores are stored in SQLite database at `data/highscores.db` with the following structure:

### Tables
- `games`: Game metadata (slug, name, year, developer, genre)
- `high_scores`: Score records (game_slug, player_name, score, level_reached, date_achieved)

### Automatic Features
- Rank calculation for new scores
- Duplicate score handling
- Transaction safety
- Input sanitization
- Score validation and limits

## Rate Limiting

No rate limiting currently implemented. Consider implementing if needed for production use.

## CORS

API includes CORS headers to allow cross-origin requests from web applications.