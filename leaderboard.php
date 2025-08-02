<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Pagination settings
$gamesPerPage = 6;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Get all games
$allGames = getAvailableGames();
$totalGames = count($allGames);
$totalPages = ceil($totalGames / $gamesPerPage);

// Get games for current page
$offset = ($currentPage - 1) * $gamesPerPage;
$games = array_slice($allGames, $offset, $gamesPerPage);

// Get top 3 players for each game on current page
$gameLeaderboards = [];
foreach ($games as $game) {
    $gameLeaderboards[$game['slug']] = getGameScores($game['slug'], 3);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Retro Arcade High Scores</title>
    
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
    <div class="fixed inset-0 retro-scanlines pointer-events-none z-40"></div>
    
    <?php include 'components/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <a href="/" class="text-neon-blue hover:text-neon-green transition-colors">
                <i class="fas fa-home mr-2"></i>Home
            </a>
            <span class="text-neon-purple mx-2">/</span>
            <span class="text-neon-pink">Leaderboard</span>
        </nav>

        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-6xl text-neon-pink mb-4 glow-text">
                <i class="fas fa-trophy mr-4"></i>
                LEADERBOARD
            </h1>
            <p class="text-xl text-neon-blue mb-2">Top 3 Champions</p>
            <div class="retro-line mx-auto"></div>
            <p class="text-sm text-gray-400 mt-4">
                <?php if ($totalPages > 1): ?>
                    Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?> • 
                <?php endif; ?>
                The greatest arcade legends across all Nintendo games
            </p>
        </div>

        <!-- Games Leaderboards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($games as $game): ?>
                <div class="retro-box bg-dark-purple border-2 border-neon-purple p-6 rounded-lg">
                    <!-- Game Header -->
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-800 rounded-lg overflow-hidden">
                            <?php 
                            $boxArtPath = null;
                            $extensions = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
                            foreach ($extensions as $ext) {
                                if (file_exists("assets/images/games/{$game['slug']}/box-art.{$ext}")) {
                                    $boxArtPath = "/assets/images/games/{$game['slug']}/box-art.{$ext}";
                                    break;
                                }
                            }
                            ?>
                            <?php if ($boxArtPath): ?>
                                <img src="<?php echo htmlspecialchars($boxArtPath); ?>" 
                                     alt="<?php echo htmlspecialchars($game['name']); ?> Box Art"
                                     class="w-full h-full object-contain pixel-perfect">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-gamepad text-xl text-neon-purple"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-lg text-neon-pink mb-2 glow-text">
                            <?php echo htmlspecialchars($game['name']); ?>
                        </h3>
                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($game['year']); ?></div>
                    </div>

                    <!-- Top 3 Scores -->
                    <?php if (empty($gameLeaderboards[$game['slug']])): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-trophy text-3xl text-gray-600 mb-3"></i>
                            <p class="text-sm text-gray-500">No scores recorded</p>
                            <p class="text-xs text-gray-600 mt-1">Be the first champion!</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($gameLeaderboards[$game['slug']] as $index => $score): ?>
                                <div class="flex items-center p-3 rounded-lg <?php echo $index === 0 ? 'bg-yellow-900 border border-yellow-500' : ($index === 1 ? 'bg-gray-800 border border-gray-500' : 'bg-orange-900 border border-orange-600'); ?>">
                                    <!-- Rank -->
                                    <div class="flex-shrink-0 mr-3">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full font-bold text-sm
                                            <?php echo $index === 0 ? 'bg-yellow-500 text-black' : 
                                                      ($index === 1 ? 'bg-gray-400 text-black' : 'bg-orange-500 text-white'); ?>">
                                            <?php if ($index < 3): ?>
                                                <i class="fas fa-trophy"></i>
                                            <?php else: ?>
                                                <?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Player Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-sm <?php echo $index === 0 ? 'text-yellow-200' : ($index === 1 ? 'text-gray-200' : 'text-orange-200'); ?> truncate">
                                            <?php echo htmlspecialchars($score['player_name']); ?>
                                        </div>
                                        <div class="text-lg font-bold <?php echo $index === 0 ? 'text-yellow-400' : ($index === 1 ? 'text-gray-300' : 'text-orange-300'); ?>">
                                            <?php echo number_format($score['score']); ?>
                                        </div>
                                        <div class="text-xs <?php echo $index === 0 ? 'text-yellow-500' : ($index === 1 ? 'text-gray-500' : 'text-orange-500'); ?>">
                                            <?php echo date('M j, Y', strtotime($score['date_achieved'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- View Full Leaderboard Link -->
                        <div class="text-center mt-4">
                            <a href="/game/<?php echo htmlspecialchars($game['slug']); ?>" 
                               class="text-xs text-neon-blue hover:text-neon-pink transition-colors">
                                <i class="fas fa-list mr-1"></i>
                                View Full Leaderboard
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center items-center mt-12 space-x-4">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?php echo $currentPage - 1; ?>" 
                       class="px-4 py-2 bg-dark-purple border-2 border-neon-blue text-neon-blue hover:border-neon-pink hover:text-neon-pink transition-colors rounded">
                        <i class="fas fa-chevron-left mr-2"></i>Previous
                    </a>
                <?php endif; ?>
                
                <div class="flex space-x-2">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="w-10 h-10 flex items-center justify-center rounded border-2 transition-colors
                                  <?php echo $i === $currentPage ? 'bg-neon-purple border-neon-purple text-white' : 'bg-dark-purple border-neon-blue text-neon-blue hover:border-neon-pink hover:text-neon-pink'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?php echo $currentPage + 1; ?>" 
                       class="px-4 py-2 bg-dark-purple border-2 border-neon-blue text-neon-blue hover:border-neon-pink hover:text-neon-pink transition-colors rounded">
                        Next<i class="fas fa-chevron-right ml-2"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Overall Stats -->
        <div class="mt-12 text-center">
            <div class="retro-box bg-dark-purple border-2 border-neon-blue p-6 rounded-lg inline-block">
                <h2 class="text-2xl text-neon-pink mb-4">
                    <i class="fas fa-chart-line mr-2"></i>
                    ARCADE STATISTICS
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                    <?php
                    $totalScores = 0;
                    $totalPlayers = 0;
                    $highestScore = 0;
                    $highestPlayer = '';
                    $highestGame = '';
                    
                    // Calculate stats across ALL games, not just current page
                    foreach ($allGames as $game) {
                        $gameScores = getGameScores($game['slug']);
                        $totalScores += count($gameScores);
                        
                        $uniquePlayers = array_unique(array_column($gameScores, 'player_name'));
                        $totalPlayers += count($uniquePlayers);
                        
                        if (!empty($gameScores) && $gameScores[0]['score'] > $highestScore) {
                            $highestScore = $gameScores[0]['score'];
                            $highestPlayer = $gameScores[0]['player_name'];
                            $highestGame = $game['name'];
                        }
                    }
                    ?>
                    
                    <div>
                        <div class="text-2xl text-neon-green glow-text"><?php echo $totalScores; ?></div>
                        <div class="text-sm text-neon-blue mt-1">Total Scores</div>
                    </div>
                    
                    <div>
                        <div class="text-2xl text-neon-purple glow-text"><?php echo $totalGames; ?></div>
                        <div class="text-sm text-neon-blue mt-1">Games Available</div>
                    </div>
                    
                    <?php if ($highestScore > 0): ?>
                        <div>
                            <div class="text-2xl text-neon-pink glow-text"><?php echo number_format($highestScore); ?></div>
                            <div class="text-sm text-neon-blue mt-1">Highest Score</div>
                            <div class="text-xs text-gray-400 mt-1">
                                <?php echo htmlspecialchars($highestPlayer); ?> - <?php echo htmlspecialchars($highestGame); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div>
                            <div class="text-2xl text-gray-500">0</div>
                            <div class="text-sm text-neon-blue mt-1">Highest Score</div>
                            <div class="text-xs text-gray-400 mt-1">No scores yet</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'components/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>