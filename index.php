<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get all games for homepage
$games = getAvailableGames();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retro Arcade High Scores</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts - Press Start 2P -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Retro CSS -->
    <link rel="stylesheet" href="assets/css/retro.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'pixel': ['"Press Start 2P"', 'monospace'],
                    },
                    colors: {
                        'neon-pink': '#ff00ff',
                        'neon-blue': '#00ffff',
                        'neon-green': '#00ff00',
                        'neon-purple': '#8000ff',
                        'dark-purple': '#1a0033',
                        'retro-bg': '#0d001a',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-retro-bg text-neon-blue font-pixel min-h-screen">
    <?php include 'components/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-6xl text-neon-pink mb-4 glow-text">
                <i class="fas fa-gamepad mr-4"></i>
                RETRO ARCADE
            </h1>
            <p class="text-xl text-neon-blue mb-2">HIGH SCORES</p>
            <div class="retro-line mx-auto"></div>
            <p class="text-sm text-gray-400 mt-4">Complete Nintendo arcade collection</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($games as $game): ?>
                <div class="retro-card bg-dark-purple border-2 border-neon-purple p-6 rounded-lg hover:border-neon-pink transition-all duration-300 hover:shadow-neon">
                    <a href="/game/<?php echo htmlspecialchars($game['slug']); ?>" class="block">
                        <div class="aspect-square bg-gray-800 rounded-lg mb-4 flex items-center justify-center overflow-hidden">
                            <?php 
                            $boxArtPath = null;
                            $possibleFormats = ['jpg', 'png', 'svg', 'webp'];
                            foreach ($possibleFormats as $format) {
                                if (file_exists("assets/images/games/{$game['slug']}/box-art.{$format}")) {
                                    $boxArtPath = "/assets/images/games/{$game['slug']}/box-art.{$format}";
                                    break;
                                }
                            }
                            ?>
                            <?php if ($boxArtPath): ?>
                                <img src="<?php echo htmlspecialchars($boxArtPath); ?>" 
                                     alt="<?php echo htmlspecialchars($game['name']); ?> Box Art"
                                     class="w-full h-full object-contain pixel-perfect">
                            <?php else: ?>
                                <i class="fas fa-gamepad text-6xl text-neon-purple"></i>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="text-lg text-neon-green mb-2 text-center">
                            <?php echo htmlspecialchars($game['name']); ?>
                        </h3>
                        
                        <div class="text-center text-sm text-neon-blue">
                            <i class="fas fa-trophy mr-2"></i>
                            High Score: <?php echo number_format($game['high_score']); ?>
                        </div>
                        
                        <div class="text-center text-xs text-gray-400 mt-2">
                            <i class="fas fa-calendar mr-1"></i>
                            <?php echo htmlspecialchars($game['year']); ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-12">
            <div class="mb-8">
                <a href="/games" class="inline-flex items-center px-6 py-3 bg-neon-purple hover:bg-neon-pink text-white rounded-lg transition-colors duration-300 border border-neon-purple hover:border-neon-pink">
                    <i class="fas fa-list mr-2"></i>
                    View All Games
                </a>
            </div>
            
            <div class="retro-box bg-dark-purple border-2 border-neon-blue p-6 rounded-lg inline-block">
                <h2 class="text-2xl text-neon-pink mb-4">
                    <i class="fas fa-info-circle mr-2"></i>
                    HOW TO PLAY
                </h2>
                <p class="text-sm text-neon-blue max-w-2xl">
                    Click on any game to view detailed high scores, screenshots, and relive the golden age of arcade gaming!
                    Each game page features authentic MIDI music from the era.
                </p>
            </div>
        </div>
    </main>
    
    <?php include 'components/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
