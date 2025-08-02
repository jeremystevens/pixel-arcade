# Retro Arcade High Scores Website

A retro 80s-styled PHP website for displaying classic video game high scores with authentic neon aesthetics, MIDI background music, and clean URLs.

## Features

- **Retro 80s Design**: Neon glow effects, pixel fonts, and authentic arcade styling
- **Clean URLs**: `/game/contra` instead of `/game/contra.php`
- **MIDI Music Player**: Background chiptune music with synthetic fallback
- **Responsive Design**: Mobile-friendly with hamburger menu
- **SQLite Database**: Stores games and high scores
- **Game Pages**: Individual pages for Contra, Pac-Man, Galaga, and Donkey Kong

## URL Routing

This project supports clean URLs through two methods:

### Production (Apache with mod_rewrite)
The `.htaccess` file handles URL rewriting automatically:
```apache
RewriteEngine On
RewriteRule ^game/([a-zA-Z0-9_-]+)$ /game/$1.php [L]
```

### Development (PHP Built-in Server)
Use the `router.php` file for development:
```bash
php -S 0.0.0.0:5000 -t . router.php
```

The router provides the same clean URL functionality as .htaccess for development environments.

## Project Structure

```
/
├── assets/
│   ├── css/retro.css          # Retro 80s styling
│   ├── js/main.js             # Interactive features
│   ├── js/midi-player.js      # Music player
│   ├── images/                # Game assets (box art, screenshots)
│   └── music/                 # MIDI background music
├── game/
│   ├── contra.php             # Individual game pages
│   ├── pacman.php
│   ├── galaga.php
│   └── donkey-kong.php
├── components/
│   ├── header.php             # Reusable header
│   └── footer.php             # Reusable footer
├── config/
│   └── database.php           # SQLite database setup
├── includes/
│   └── functions.php          # Core functions
├── api/                       # Reserved for external API
├── index.php                  # Homepage
├── .htaccess                  # Apache URL rewriting
└── router.php                 # PHP development router
```

## Setup Instructions

### Requirements
- PHP 8.1+
- SQLite (included with PHP)
- Web server (Apache recommended for production)

### Development Setup
1. Clone/download the project
2. Run with PHP built-in server:
   ```bash
   php -S 0.0.0.0:5000 -t . router.php
   ```
3. Visit `http://localhost:5000`

### Production Setup
1. Upload files to web server
2. Ensure Apache has `mod_rewrite` enabled
3. Verify `.htaccess` is allowed (`AllowOverride All`)
4. The clean URLs will work automatically

## Database

The SQLite database is automatically created with default games:
- **Contra** (1987) - Konami
- **Pac-Man** (1980) - Namco  
- **Galaga** (1981) - Namco
- **Donkey Kong** (1981) - Nintendo

High scores are stored and displayed on each game page.

## Customization

### Adding New Games
1. Add game data to `config/database.php` in `insertDefaultGames()`
2. Create new PHP file in `/game/` directory
3. Add game assets to `/assets/images/[game-slug]/`
4. Add MIDI music to `/assets/music/[game-slug].mid`

### Styling
- Main retro styles: `assets/css/retro.css`
- Tailwind CSS for layout and responsive design
- Custom CSS properties for neon colors

### Music
- MIDI files for authentic chiptune sounds
- Automatic fallback to synthetic oscillator sounds
- Volume control and progress tracking

## Technology Stack

- **Backend**: PHP 8.1, SQLite
- **Frontend**: HTML5, Tailwind CSS, JavaScript
- **Audio**: Web Audio API, MIDI.js concepts
- **Fonts**: Press Start 2P (Google Fonts)
- **Icons**: FontAwesome 6

## Browser Compatibility

- Modern browsers with Web Audio API support
- Graceful degradation for older browsers
- Mobile-responsive design for all screen sizes

## Contributing

When adding features:
1. Maintain the retro 80s aesthetic
2. Ensure both .htaccess and router.php support new URLs
3. Test on both development and production environments
4. Follow the existing code structure and naming conventions

## License

Open source project - feel free to modify and use for your own retro gaming projects!