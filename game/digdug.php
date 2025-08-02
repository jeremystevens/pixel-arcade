<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$gameSlug = 'digdug';
$game = getGameInfo($gameSlug);
$scores = getGameScores($gameSlug);

if (!$game) {
    header('HTTP/1.0 404 Not Found');
    include(__DIR__ . '/../404.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['name']); ?> - Retro Arcade High Scores</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts - Press Start 2P -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Retro CSS -->
    <link rel="stylesheet" href="/assets/css/retro.css">
    
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
    
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <main class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <a href="/" class="text-neon-blue hover:text-neon-green transition-colors">
                <i class="fas fa-home mr-2"></i>Home
            </a>
            <span class="text-neon-purple mx-2">/</span>
            <span class="text-neon-pink"><?php echo htmlspecialchars($game['name']); ?></span>
        </nav>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Game Info Panel -->
            <div class="lg:col-span-1">
                <div class="retro-box bg-dark-purple border-2 border-neon-purple p-6 rounded-lg mb-6">
                    <div class="aspect-square bg-gray-800 rounded-lg mb-4 overflow-hidden">
                        <?php 
                        $boxArtPath = null;
                        $extensions = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
                        foreach ($extensions as $ext) {
                            if (file_exists(__DIR__ . "/../assets/images/games/{$gameSlug}/box-art.{$ext}")) {
                                $boxArtPath = "/assets/images/games/{$gameSlug}/box-art.{$ext}";
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
                                <div class="text-6xl text-yellow-400">
                                    <i class="fas fa-mountain"></i>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="text-2xl text-neon-pink mb-4 text-center glow-text">
                        <?php echo htmlspecialchars($game['name']); ?>
                    </h1>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-neon-blue">Year:</span>
                            <span class="text-neon-green"><?php echo htmlspecialchars($game['year']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neon-blue">Developer:</span>
                            <span class="text-neon-green"><?php echo htmlspecialchars($game['developer']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neon-blue">Genre:</span>
                            <span class="text-neon-green"><?php echo htmlspecialchars($game['genre']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Music Player -->
                    <div class="mt-6 pt-4 border-t border-neon-purple">
                        <h3 class="text-lg text-neon-pink mb-3">
                            <i class="fas fa-music mr-2"></i>
                            SOUNDTRACK
                        </h3>
                        <div id="midi-player" class="bg-black p-3 rounded border border-neon-blue">
                            <div class="flex items-center justify-between">
                                <button id="play-btn" class="text-neon-green text-xl hover:text-neon-pink transition-colors">
                                    <i class="fas fa-play"></i>
                                </button>
                                <div class="flex-1 mx-3">
                                    <div class="h-2 bg-gray-700 rounded">
                                        <div id="progress-bar" class="h-full bg-neon-blue rounded" style="width: 0%"></div>
                                    </div>
                                </div>
                                <div id="volume-control" class="flex items-center">
                                    <i class="fas fa-volume-up text-neon-blue mr-2"></i>
                                    <input type="range" min="0" max="100" value="100" class="volume-slider">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Screenshots -->
                <div class="retro-box bg-dark-purple border-2 border-neon-purple p-6 rounded-lg">
                    <h3 class="text-lg text-neon-pink mb-4">
                        <i class="fas fa-images mr-2"></i>
                        SCREENSHOTS
                    </h3>
                    <div class="grid grid-cols-2 gap-2">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <?php if (file_exists(__DIR__ . "/../assets/images/games/{$gameSlug}/screenshot{$i}.jpg")): ?>
                                <img src="/assets/images/games/<?php echo htmlspecialchars($gameSlug); ?>/screenshot<?php echo $i; ?>.jpg" 
                                     alt="Screenshot <?php echo $i; ?>"
                                     class="w-full aspect-video object-cover rounded border border-neon-blue hover:border-neon-pink transition-colors cursor-pointer pixel-perfect">
                            <?php else: ?>
                                <div class="w-full aspect-video bg-gray-800 rounded border border-neon-blue flex items-center justify-center">
                                    <i class="fas fa-image text-2xl text-neon-purple"></i>
                                </div>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- High Scores Panel -->
            <div class="lg:col-span-2">
                <?php if (empty($scores)): ?>
                    <div class="retro-box bg-dark-purple border-2 border-neon-purple p-6 rounded-lg">
                        <h2 class="text-3xl text-neon-pink mb-6 text-center glow-text">
                            <i class="fas fa-trophy mr-3"></i>
                            HIGH SCORES
                        </h2>
                        <div class="text-center py-12">
                            <i class="fas fa-mountain text-6xl text-yellow-400 mb-4"></i>
                            <p class="text-xl text-neon-blue">NO SCORES RECORDED</p>
                            <p class="text-sm text-gray-400 mt-2">Dig deep and set the first underground record!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php 
                    $topTen = array_slice($scores, 0, 10);
                    $remainingScores = array_slice($scores, 10);
                    ?>
                    
                    <!-- TOP 10 LEADERBOARD -->
                    <div class="retro-box bg-dark-purple border-2 border-neon-pink p-6 rounded-lg mb-6">
                        <h2 class="text-3xl text-neon-pink mb-6 text-center glow-text">
                            <i class="fas fa-crown mr-3"></i>
                            TOP 10 LEGENDS
                        </h2>
                        
                        <!-- Champion Display -->
                        <div class="bg-gradient-to-r from-yellow-900 to-yellow-700 border-2 border-yellow-400 rounded-lg p-4 mb-6 text-center">
                            <div class="flex items-center justify-center mb-2">
                                <i class="fas fa-crown text-3xl text-yellow-400 mr-3"></i>
                                <span class="text-2xl text-yellow-100 font-bold">CHAMPION</span>
                                <i class="fas fa-crown text-3xl text-yellow-400 ml-3"></i>
                            </div>
                            <div class="text-4xl text-yellow-400 font-bold mb-1"><?php echo htmlspecialchars($topTen[0]['player_name']); ?></div>
                            <div class="text-3xl text-yellow-200"><?php echo number_format($topTen[0]['score']); ?> PTS</div>
                            <div class="text-sm text-yellow-300 mt-2"><?php echo date('M j, Y', strtotime($topTen[0]['date_achieved'])); ?></div>
                        </div>
                        
                        <!-- Top 10 List -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($topTen as $index => $score): ?>
                                <div class="flex items-center p-3 border border-neon-purple rounded-lg hover:border-neon-pink transition-colors bg-black bg-opacity-30">
                                    <div class="flex-shrink-0 mr-4">
                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-full font-bold
                                            <?php echo $index === 0 ? 'bg-yellow-500 text-black' : 
                                                      ($index === 1 ? 'bg-gray-400 text-black' : 
                                                      ($index === 2 ? 'bg-orange-500 text-white' : 'bg-neon-purple text-white')); ?>">
                                            <?php if ($index < 3): ?>
                                                <i class="fas fa-trophy"></i>
                                            <?php else: ?>
                                                <?php echo $index + 1; ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-neon-green font-bold text-lg"><?php echo htmlspecialchars($score['player_name']); ?></div>
                                        <div class="text-neon-blue text-xl"><?php echo number_format($score['score']); ?></div>
                                        <div class="text-gray-400 text-sm"><?php echo date('M j', strtotime($score['date_achieved'])); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($remainingScores)): ?>
                    <!-- ADDITIONAL SCORES -->
                    <div class="retro-box bg-dark-purple border-2 border-neon-blue p-6 rounded-lg">
                        <h3 class="text-2xl text-neon-blue mb-4 text-center">
                            <i class="fas fa-list mr-2"></i>
                            ALL SCORES (<?php echo count($scores); ?> TOTAL)
                        </h3>
                        
                        <!-- Show/Hide Toggle -->
                        <div class="text-center mb-4">
                            <button id="toggleScores" class="bg-neon-purple hover:bg-neon-pink text-white px-6 py-2 rounded-lg transition-colors border border-neon-purple hover:border-neon-pink">
                                <i class="fas fa-eye mr-2"></i>
                                <span id="toggleText">SHOW ALL SCORES</span>
                            </button>
                        </div>
                        
                        <!-- Expandable Score Table -->
                        <div id="allScoresTable" class="hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="border-b-2 border-neon-blue">
                                            <th class="py-2 px-3 text-neon-blue">RANK</th>
                                            <th class="py-2 px-3 text-neon-blue">PLAYER</th>
                                            <th class="py-2 px-3 text-neon-blue">SCORE</th>
                                            <th class="py-2 px-3 text-neon-blue">DATE</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($scores as $index => $score): ?>
                                            <tr class="border-b border-gray-600 hover:bg-black hover:bg-opacity-30 transition-colors">
                                                <td class="py-2 px-3 text-neon-purple">#<?php echo $index + 1; ?></td>
                                                <td class="py-2 px-3 text-neon-green"><?php echo htmlspecialchars($score['player_name']); ?></td>
                                                <td class="py-2 px-3 text-neon-blue"><?php echo number_format($score['score']); ?></td>
                                                <td class="py-2 px-3 text-gray-400"><?php echo date('M j, Y', strtotime($score['date_achieved'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Score Statistics -->
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 border border-neon-pink rounded-lg retro-box">
                            <div class="text-xl lg:text-2xl text-neon-pink glow-text break-words"><?php echo number_format($scores[0]['score']); ?></div>
                            <div class="text-sm text-neon-blue mt-1">RECORD HIGH</div>
                        </div>
                        <div class="text-center p-4 border border-neon-green rounded-lg retro-box">
                            <div class="text-xl lg:text-2xl text-neon-green glow-text break-words"><?php echo count($scores); ?></div>
                            <div class="text-sm text-neon-blue mt-1">TOTAL SCORES</div>
                        </div>
                        <div class="text-center p-4 border border-neon-purple rounded-lg retro-box">
                            <div class="text-xl lg:text-2xl text-neon-purple glow-text break-words"><?php echo number_format(array_sum(array_column($scores, 'score')) / count($scores)); ?></div>
                            <div class="text-sm text-neon-blue mt-1">AVERAGE</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../components/footer.php'; ?>
    
    <script src="/assets/js/midi-player.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        // Initialize MIDI player for this game
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof MIDIPlayer !== 'undefined') {
                const player = new MIDIPlayer('/assets/music/digdug.mp3');
                player.init();
            }
        });
    </script>
</body>
</html>