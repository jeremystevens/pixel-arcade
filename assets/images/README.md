# Game Assets Directory Structure

This directory contains all visual assets for the retro arcade games.

## Directory Structure

```
assets/images/
├── games/
│   ├── contra/
│   │   ├── box-art.jpg         # Game box art (recommended: 300x400px)
│   │   ├── screenshot-1.jpg    # In-game screenshot
│   │   ├── screenshot-2.jpg    # In-game screenshot
│   │   └── logo.png           # Game logo (transparent background)
│   ├── pacman/
│   │   ├── box-art.jpg
│   │   ├── screenshot-1.jpg
│   │   ├── screenshot-2.jpg
│   │   └── logo.png
│   ├── galaga/
│   │   ├── box-art.jpg
│   │   ├── screenshot-1.jpg
│   │   ├── screenshot-2.jpg
│   │   └── logo.png
│   └── donkey-kong/
│       ├── box-art.jpg
│       ├── screenshot-1.jpg
│       ├── screenshot-2.jpg
│       └── logo.png
├── icons/
│   └── (UI icons and general graphics)
└── backgrounds/
    └── (Background textures and patterns)
```

## Image Specifications

### Box Art
- **Format**: JPG or PNG
- **Dimensions**: 300x400px (3:4 aspect ratio)
- **Quality**: High resolution for crisp display
- **Naming**: `box-art.jpg` or `box-art.png`

### Screenshots
- **Format**: JPG or PNG
- **Dimensions**: 640x480px or 320x240px (4:3 aspect ratio for authenticity)
- **Quality**: Pixel-perfect for retro feel
- **Naming**: `screenshot-1.jpg`, `screenshot-2.jpg`, etc.

### Logos
- **Format**: PNG with transparent background
- **Dimensions**: Variable (maintain aspect ratio)
- **Quality**: Vector-style or high resolution
- **Naming**: `logo.png`

## How to Add Assets

1. **Box Art**: Place main game box image as `box-art.jpg` in the game's folder
2. **Screenshots**: Add gameplay screenshots numbered sequentially
3. **Logos**: Add transparent game logos for headers and UI elements

## Current Asset Usage

The website automatically looks for these assets in the following locations:

- Box art: `/assets/images/games/{game-slug}/box-art.jpg`
- Screenshots: `/assets/images/games/{game-slug}/screenshot-1.jpg`
- Logos: `/assets/images/games/{game-slug}/logo.png`

If assets are missing, the site uses graceful fallbacks with themed placeholders.

## Supported Formats

- **Images**: JPG, PNG, WebP
- **Recommended**: JPG for photos, PNG for graphics with transparency
- **Optimization**: Compress images for web performance while maintaining quality

## Example File Paths

```
/assets/images/games/contra/box-art.jpg
/assets/images/games/pacman/screenshot-1.jpg
/assets/images/games/galaga/logo.png
/assets/images/games/donkey-kong/box-art.jpg
```