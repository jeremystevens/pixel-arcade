<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Pagination settings
$gamesPerPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $gamesPerPage;

// Get total games count
$totalGames = getTotalGamesCount();
$totalPages = ceil($totalGames / $gamesPerPage);

// Get games for current page
$games = getGamesPaginated($offset, $gamesPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Games - Retro Arcade High Scores</title>
    
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
            <span class="text-neon-pink">All Games</span>
        </nav>

        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-6xl text-neon-pink mb-4 glow-text">
                <i class="fas fa-list mr-4"></i>
                ALL GAMES
            </h1>
            <p class="text-xl text-neon-blue mb-2">Nintendo Arcade Collection</p>
            <div class="retro-line mx-auto"></div>
            <p class="text-sm text-gray-400 mt-4">
                Page <?php echo $page; ?> of <?php echo $totalPages; ?> • <?php echo $totalGames; ?> Total Games
            </p>
        </div>

        <?php if (empty($games)): ?>
            <div class="text-center py-16">
                <i class="fas fa-gamepad text-6xl text-neon-purple mb-4"></i>
                <p class="text-xl text-neon-blue">No games found</p>
            </div>
        <?php else: ?>
            <!-- Games List -->
            <div class="space-y-6 mb-12">
                <?php foreach ($games as $game): ?>
                    <?php
                    // Get top score for this game
                    $topScore = getTopScore($game['slug']);
                    ?>
                    <div class="retro-box bg-dark-purple border-2 border-neon-purple p-6 rounded-lg hover:border-neon-pink transition-all duration-300">
                        <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                            <!-- Box Art -->
                            <div class="flex-shrink-0">
                                <a href="/game/<?php echo htmlspecialchars($game['slug']); ?>" class="block">
                                    <div class="w-24 h-24 bg-gray-800 rounded-lg overflow-hidden">
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
                                                 class="w-full h-full object-contain pixel-perfect hover:scale-105 transition-transform duration-300">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center">
                                                <i class="fas fa-gamepad text-2xl text-neon-purple"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                            
                            <!-- Game Info -->
                            <div class="flex-1">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                    <div>
                                        <h3 class="text-2xl text-neon-pink mb-2 glow-text">
                                            <a href="/game/<?php echo htmlspecialchars($game['slug']); ?>" class="hover:text-neon-green transition-colors">
                                                <?php echo htmlspecialchars($game['name']); ?>
                                            </a>
                                        </h3>
                                        <div class="space-y-1 text-sm">
                                            <div class="flex items-center">
                                                <span class="text-neon-blue mr-2">Year:</span>
                                                <span class="text-neon-green"><?php echo htmlspecialchars($game['year']); ?></span>
                                            </div>
                                            <div class="flex items-center">
                                                <span class="text-neon-blue mr-2">Developer:</span>
                                                <span class="text-neon-green"><?php echo htmlspecialchars($game['developer']); ?></span>
                                            </div>
                                            <div class="flex items-center">
                                                <span class="text-neon-blue mr-2">Genre:</span>
                                                <span class="text-neon-green"><?php echo htmlspecialchars($game['genre']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- High Score Display -->
                                    <div class="text-center md:text-right">
                                        <?php if ($topScore): ?>
                                            <div class="bg-black bg-opacity-50 border border-neon-blue rounded-lg p-4">
                                                <div class="text-xs text-neon-blue mb-1">HIGH SCORE</div>
                                                <div class="text-xl text-neon-green font-bold"><?php echo number_format($topScore['score']); ?></div>
                                                <div class="text-sm text-neon-purple"><?php echo htmlspecialchars($topScore['player_name']); ?></div>
                                                <div class="text-xs text-gray-400"><?php echo date('M j, Y', strtotime($topScore['date_achieved'])); ?></div>
                                            </div>
                                        <?php else: ?>
                                            <div class="bg-black bg-opacity-50 border border-gray-600 rounded-lg p-4">
                                                <div class="text-xs text-gray-500 mb-1">HIGH SCORE</div>
                                                <div class="text-lg text-gray-500">No scores yet</div>
                                                <div class="text-xs text-gray-500">Be the first!</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <?php if (!empty($game['description'])): ?>
                                    <p class="text-sm text-gray-300 mt-3 leading-relaxed">
                                        <?php echo htmlspecialchars($game['description']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <!-- Action Button -->
                                <div class="mt-4">
                                    <a href="/game/<?php echo htmlspecialchars($game['slug']); ?>" 
                                       class="inline-flex items-center px-4 py-2 bg-neon-purple hover:bg-neon-pink text-white rounded-lg transition-colors duration-300 border border-neon-purple hover:border-neon-pink">
                                        <i class="fas fa-trophy mr-2"></i>
                                        View Leaderboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center items-center space-x-2">
                    <!-- Previous Page -->
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" 
                           class="px-4 py-2 bg-neon-purple hover:bg-neon-pink text-white rounded-lg transition-colors duration-300 border border-neon-purple hover:border-neon-pink">
                            <i class="fas fa-chevron-left mr-1"></i>
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <!-- Page Numbers -->
                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    ?>
                    
                    <?php if ($startPage > 1): ?>
                        <a href="?page=1" class="px-3 py-2 bg-dark-purple border border-neon-blue text-neon-blue rounded hover:border-neon-pink hover:text-neon-pink transition-colors">1</a>
                        <?php if ($startPage > 2): ?>
                            <span class="text-neon-purple">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="px-3 py-2 bg-neon-pink text-black rounded font-bold"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>" class="px-3 py-2 bg-dark-purple border border-neon-blue text-neon-blue rounded hover:border-neon-pink hover:text-neon-pink transition-colors">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="text-neon-purple">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $totalPages; ?>" class="px-3 py-2 bg-dark-purple border border-neon-blue text-neon-blue rounded hover:border-neon-pink hover:text-neon-pink transition-colors">
                            <?php echo $totalPages; ?>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Next Page -->
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" 
                           class="px-4 py-2 bg-neon-purple hover:bg-neon-pink text-white rounded-lg transition-colors duration-300 border border-neon-purple hover:border-neon-pink">
                            Next
                            <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Page Info -->
                <div class="text-center mt-4 text-sm text-gray-400">
                    Showing games <?php echo $offset + 1; ?>-<?php echo min($offset + $gamesPerPage, $totalGames); ?> of <?php echo $totalGames; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    
    <?php include 'components/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>