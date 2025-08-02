<footer class="bg-black border-t-2 border-neon-purple mt-16">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center md:text-left">
                <h3 class="text-xl text-neon-pink mb-4">
                    <i class="fas fa-gamepad mr-2"></i>
                    RETRO ARCADE
                </h3>
                <p class="text-sm text-neon-blue">
                    Celebrating the golden age of arcade gaming since 1980s.
                    High scores, pixel perfect graphics, and nostalgic sounds.
                </p>
            </div>
            
            <div class="text-center">
                <h4 class="text-lg text-neon-green mb-4">
                    <i class="fas fa-star mr-2"></i>
                    FEATURED GAMES
                </h4>
                <ul class="text-sm text-neon-blue space-y-2">
                    <li><a href="/game/contra" class="hover:text-neon-green transition-colors">Contra</a></li>
                    <li><a href="/game/pacman" class="hover:text-neon-green transition-colors">Pac-Man</a></li>
                    <li><a href="/game/galaga" class="hover:text-neon-green transition-colors">Galaga</a></li>
                    <li><a href="/game/donkey-kong" class="hover:text-neon-green transition-colors">Donkey Kong</a></li>
                </ul>
            </div>
            
            <div class="text-center md:text-right">
                <h4 class="text-lg text-neon-purple mb-4">
                    <i class="fas fa-info-circle mr-2"></i>
                    STATS
                </h4>
                <div class="text-sm text-neon-blue space-y-2">
                    <div>
                        <i class="fas fa-trophy mr-2"></i>
                        Total High Scores: <span class="text-neon-green"><?php echo getTotalScores(); ?></span>
                    </div>
                    <div>
                        <i class="fas fa-users mr-2"></i>
                        Players: <span class="text-neon-green"><?php echo getTotalPlayers(); ?></span>
                    </div>
                    <div>
                        <i class="fas fa-gamepad mr-2"></i>
                        Games: <span class="text-neon-green"><?php echo count(getAvailableGames()); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="border-t border-neon-purple mt-8 pt-6 text-center">
            <div class="retro-scanlines"></div>
            <p class="text-xs text-gray-400">
                © <?php echo date('Y'); ?> RETRO ARCADE HIGH SCORES. 
                <span class="text-neon-pink">INSERT COIN TO CONTINUE</span>
            </p>
        </div>
    </div>
</footer>
