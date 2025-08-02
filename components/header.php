<header class="bg-black border-b-2 border-neon-purple sticky top-0 z-50">
    <nav class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <a href="/" class="text-2xl text-neon-pink hover:text-neon-blue transition-colors duration-300">
                    <i class="fas fa-gamepad mr-2"></i>
                    RETRO ARCADE
                </a>
            </div>
            
            <div class="hidden md:flex items-center space-x-6">
                <a href="/" class="text-neon-blue hover:text-neon-green transition-colors duration-300">
                    <i class="fas fa-home mr-1"></i>
                    HOME
                </a>
                <a href="/games" class="text-neon-blue hover:text-neon-green transition-colors duration-300">
                    <i class="fas fa-list mr-1"></i>
                    GAMES
                </a>
                <a href="/leaderboard" class="text-neon-blue hover:text-neon-green transition-colors duration-300">
                    <i class="fas fa-trophy mr-1"></i>
                    LEADERBOARD
                </a>
            </div>
            
            <div class="md:hidden">
                <button id="mobile-menu-toggle" class="text-neon-pink text-2xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden mt-4 pt-4 border-t border-neon-purple">
            <div class="flex flex-col space-y-3">
                <a href="/" class="text-neon-blue hover:text-neon-green transition-colors duration-300">
                    <i class="fas fa-home mr-2"></i>HOME
                </a>
                <a href="/games" class="text-neon-blue hover:text-neon-green transition-colors duration-300">
                    <i class="fas fa-list mr-2"></i>GAMES
                </a>
                <a href="/leaderboard" class="text-neon-blue hover:text-neon-green transition-colors duration-300">
                    <i class="fas fa-trophy mr-2"></i>LEADERBOARD
                </a>
            </div>
        </div>
    </nav>
</header>
