# MIDI Music Directory

This directory contains background music files for each arcade game.

## Directory Structure

```
assets/music/
├── contra.mid          # MIDI file for Contra
├── pacman.mid          # MIDI file for Pac-Man  
├── galaga.mid          # MIDI file for Galaga
├── donkey-kong.mid     # MIDI file for Donkey Kong
└── README.md           # This file
```

## Supported Formats

### Primary Format: MP3 (.mp3)
- **Best Choice**: Universal browser support
- **File Size**: Compressed audio (typically 1-5MB)
- **Quality**: High quality audio with good compression
- **Browser Support**: Native HTML5 audio support

### Alternative Formats
- **OGG**: Alternative compressed audio format
- **WAV**: Uncompressed audio (larger file sizes)
- **MIDI**: Convert to MP3 for browser compatibility

## File Naming Convention

Use the exact game slug as the filename:
- `contra.mid` or `contra.mp3`
- `pacman.mid` or `pacman.mp3` 
- `galaga.mid` or `galaga.mp3`
- `donkey-kong.mid` or `donkey-kong.mp3`

## How to Add Music

1. **MP3 Files**: Place `.mp3` files directly in this directory
   - Example: `assets/music/contra.mp3`
   
2. **Convert MIDI to MP3**: Use online converters or audio software
   - Upload your `.mid` file to an online MIDI to MP3 converter
   - Download the MP3 version and place it in this directory

## MIDI Player Features

The website includes a custom MIDI player that:
- Automatically loops background music
- Provides volume controls
- Shows playback progress
- Falls back to synthetic sounds if MIDI unavailable

## Current Music Loading

The website looks for music files in this order:
1. `assets/music/{game-slug}.mp3` (MP3 - preferred)
2. Synthetic chiptune sounds (fallback)

## Finding MIDI Files

### Legal Sources for Game Music
- **Original Game Soundtracks**: Convert from original game files
- **Public Domain**: Classical music or expired copyrights
- **Creative Commons**: CC-licensed chiptune music
- **Original Compositions**: Create your own retro-style music

### MIDI Conversion Tools
- **Online Converters**: Convert MP3/WAV to MIDI
- **Music Software**: Create MIDI files from scratch
- **Game Rippers**: Extract from original game files (legal grey area)

## Technical Notes

- MIDI files are played using Web Audio API
- Synthetic fallback sounds are generated procedurally
- All music loops automatically during gameplay
- Volume is user-controllable and persistent

## Example File Paths

```
/assets/music/contra.mid       ← Main MIDI file
/assets/music/contra.mp3       ← Fallback audio
/assets/music/pacman.mid       ← Pac-Man MIDI
/assets/music/galaga.mid       ← Galaga MIDI
/assets/music/donkey-kong.mid  ← Donkey Kong MIDI
```