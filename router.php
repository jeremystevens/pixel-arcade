<?php
/**
 * Router for PHP built-in server
 * Handles clean URLs when .htaccess is not supported
 * 
 * Note: This router is only needed for development with PHP's built-in server.
 * On production Apache servers, the .htaccess file will handle URL rewriting.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Handle static assets (let PHP server handle them naturally)
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|mid|mp3)$/', $uri)) {
    return false; // Let PHP server handle static files
}

// Route /game/slug to /game/slug.php
if (preg_match('/^\/game\/([a-zA-Z0-9_-]+)$/', $uri, $matches)) {
    $gameSlug = $matches[1];
    $filePath = __DIR__ . "/game/{$gameSlug}.php";
    
    if (file_exists($filePath)) {
        include $filePath;
        return true;
    } else {
        // Game not found - show 404
        http_response_code(404);
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Game Not Found - Retro Arcade</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link href='https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <link rel='stylesheet' href='/assets/css/retro.css'>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 'pixel': ['\"Press Start 2P\"', 'monospace'] },
                    colors: {
                        'neon-pink': '#ff00ff', 'neon-blue': '#00ffff', 'neon-green': '#00ff00',
                        'neon-purple': '#8000ff', 'dark-purple': '#1a0033', 'retro-bg': '#0d001a'
                    }
                }
            }
        }
    </script>
</head>
<body class='bg-retro-bg text-neon-blue font-pixel min-h-screen flex items-center justify-center'>
    <div class='text-center'>
        <i class='fas fa-exclamation-triangle text-6xl text-neon-purple mb-4'></i>
        <h1 class='text-4xl text-neon-pink mb-4 glow-text'>GAME NOT FOUND</h1>
        <p class='text-xl text-neon-blue mb-6'>The game '{$gameSlug}' does not exist.</p>
        <a href='/' class='inline-block bg-dark-purple border-2 border-neon-blue px-6 py-3 text-neon-green hover:border-neon-pink hover:text-neon-pink transition-colors'>
            <i class='fas fa-home mr-2'></i>RETURN TO ARCADE
        </a>
    </div>
</body>
</html>";
        return true;
    }
}

// Route root to index.php
if ($uri === '/') {
    include __DIR__ . '/index.php';
    return true;
}

// Handle other clean URLs (like /about → /about.php)
$cleanPath = ltrim($uri, '/');
if (!empty($cleanPath) && !strpos($cleanPath, '/')) {
    $filePath = __DIR__ . "/{$cleanPath}.php";
    if (file_exists($filePath)) {
        include $filePath;
        return true;
    }
}

// Default: let PHP server handle the request normally
return false;
?>