/**
 * MIDI Player for Retro Arcade
 * Handles MIDI file playback with Web Audio API
 */

class MIDIPlayer {
    constructor(midiFile) {
        this.midiFile = midiFile;
        this.audioContext = null;
        this.isPlaying = false;
        this.currentTime = 0;
        this.duration = 0;
        this.volume = 1.0;
        this.audio = null;
        this.animationFrame = null;
        
        // UI Elements
        this.playBtn = null;
        this.progressBar = null;
        this.volumeSlider = null;
    }
    
    /**
     * Initialize the MIDI player
     */
    init() {
        try {
            // Get UI elements
            this.playBtn = document.getElementById('play-btn');
            this.progressBar = document.getElementById('progress-bar');
            this.volumeSlider = document.querySelector('.volume-slider');
            
            if (!this.playBtn || !this.progressBar || !this.volumeSlider) {
                console.warn('MIDI Player: UI elements not found');
                return;
            }
            
            // Initialize Web Audio Context
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Set volume slider to 100%
            this.volumeSlider.value = 100;
            
            // Bind event listeners
            this.bindEvents();
            
            // Try to load MIDI file
            this.loadMIDI();
            
            console.log('MIDI Player initialized successfully');
        } catch (error) {
            console.error('Failed to initialize MIDI Player:', error);
            this.showError('MIDI player not supported in this browser');
        }
    }
    
    /**
     * Bind event listeners
     */
    bindEvents() {
        // Play/Pause button
        this.playBtn.addEventListener('click', () => {
            if (this.isPlaying) {
                this.pause();
            } else {
                this.play();
            }
        });
        
        // Volume control
        this.volumeSlider.addEventListener('input', (e) => {
            this.setVolume(e.target.value / 100);
        });
        
        // Progress bar click (seeking)
        this.progressBar.parentElement.addEventListener('click', (e) => {
            const rect = e.currentTarget.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const percentage = clickX / rect.width;
            this.seek(percentage);
        });
    }
    
    /**
     * Load MIDI file
     */
    async loadMIDI() {
        try {
            // Use MP3 files for browser compatibility
            const audioFile = this.midiFile.replace('.mid', '.mp3');
            
            // Check if audio file exists
            const response = await fetch(audioFile).catch(() => null);
            
            if (response && response.ok) {
                this.audio = new Audio(audioFile);
                this.audio.volume = this.volume;
                this.audio.loop = false; // Don't loop automatically
                this.setupAudioEvents();
                console.log('Audio file loaded:', audioFile);
                
                // Auto-play the music when loaded (once)
                this.autoPlay();
            } else {
                // Use synthetic retro sounds as fallback
                console.log('No MP3 file found, using synthetic sounds');
                this.createSynthetic();
            }
            
        } catch (error) {
            console.error('Failed to load audio file:', error);
            this.createSynthetic();
        }
    }
    
    /**
     * Setup audio events
     */
    setupAudioEvents() {
        if (!this.audio) return;
        
        this.audio.addEventListener('loadedmetadata', () => {
            this.duration = this.audio.duration;
            console.log('Audio loaded, duration:', this.duration);
        });
        
        this.audio.addEventListener('timeupdate', () => {
            this.currentTime = this.audio.currentTime;
            this.updateProgress();
        });
        
        this.audio.addEventListener('ended', () => {
            // When audio ends, reset to beginning but don't auto-replay
            this.isPlaying = false;
            this.updatePlayButton();
            this.stopProgressAnimation();
            this.currentTime = 0;
            this.audio.currentTime = 0;
            this.updateProgress();
        });
        
        this.audio.addEventListener('error', (e) => {
            console.error('Audio error:', e);
            this.createSynthetic();
        });
        
        // Set initial volume
        this.audio.volume = this.volume;
    }
    
    /**
     * Create synthetic retro sounds when MIDI is not available
     */
    createSynthetic() {
        if (!this.audioContext) return;
        
        console.log('Creating synthetic retro sounds...');
        
        // Create a simple retro-style melody using oscillators
        this.synthNodes = [];
        this.duration = 30; // 30 seconds of synthetic music
        
        // Mark as synthetic
        this.isSynthetic = true;
    }
    
    /**
     * Play MIDI/Audio
     */
    async play() {
        try {
            if (this.audioContext && this.audioContext.state === 'suspended') {
                await this.audioContext.resume();
            }
            
            if (this.audio) {
                await this.audio.play();
            } else if (this.isSynthetic) {
                this.playSynthetic();
            }
            
            this.isPlaying = true;
            this.updatePlayButton();
            this.startProgressAnimation();
            
        } catch (error) {
            console.error('Failed to play audio:', error);
            this.showError('Unable to play audio');
        }
    }
    
    /**
     * Pause playback
     */
    pause() {
        if (this.audio) {
            this.audio.pause();
        } else if (this.isSynthetic) {
            this.stopSynthetic();
        }
        
        this.isPlaying = false;
        this.updatePlayButton();
        this.stopProgressAnimation();
    }
    
    /**
     * Stop playback
     */
    stop() {
        this.pause();
        this.currentTime = 0;
        
        if (this.audio) {
            this.audio.currentTime = 0;
        }
        
        this.updateProgress();
    }
    
    /**
     * Set volume (0-1)
     */
    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume));
        
        if (this.audio) {
            this.audio.volume = this.volume;
        }
        
        // Update synthetic volume if applicable
        if (this.synthNodes) {
            this.synthNodes.forEach(node => {
                if (node.gain) {
                    node.gain.gain.value = this.volume * 0.1; // Lower volume for synthetic
                }
            });
        }
    }
    
    /**
     * Seek to position (0-1)
     */
    seek(percentage) {
        const targetTime = this.duration * percentage;
        
        if (this.audio) {
            this.audio.currentTime = targetTime;
        }
        
        this.currentTime = targetTime;
        this.updateProgress();
    }
    
    /**
     * Auto-play music when loaded
     */
    async autoPlay() {
        try {
            // Wait a bit for the audio to be fully loaded
            setTimeout(async () => {
                try {
                    await this.play();
                    console.log('Auto-playing music');
                } catch (error) {
                    // Browsers may block autoplay, that's normal
                    console.log('Autoplay blocked by browser (normal behavior)');
                }
            }, 500);
        } catch (error) {
            console.log('Autoplay not available');
        }
    }
    
    /**
     * Play synthetic retro sounds
     */
    playSynthetic() {
        if (!this.audioContext) return;
        
        // Create a simple retro melody pattern
        const notes = [261.63, 293.66, 329.63, 349.23, 392.00, 440.00, 493.88]; // C Major scale
        const pattern = [0, 2, 4, 2, 0, 2, 4, 2, 5, 4, 2, 0]; // Simple melody pattern
        
        this.synthStartTime = this.audioContext.currentTime;
        
        pattern.forEach((noteIndex, i) => {
            const startTime = this.audioContext.currentTime + (i * 0.5);
            this.playBeep(notes[noteIndex], startTime, 0.4);
        });
        
        // Loop the pattern
        this.synthTimeout = setTimeout(() => {
            if (this.isPlaying && this.isSynthetic) {
                this.playSynthetic();
            }
        }, pattern.length * 500);
    }
    
    /**
     * Play a single beep note
     */
    playBeep(frequency, startTime, duration) {
        if (!this.audioContext) return;
        
        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);
        
        oscillator.frequency.setValueAtTime(frequency, startTime);
        oscillator.type = 'square'; // Retro square wave sound
        
        gainNode.gain.setValueAtTime(0, startTime);
        gainNode.gain.linearRampToValueAtTime(this.volume * 0.1, startTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.001, startTime + duration);
        
        oscillator.start(startTime);
        oscillator.stop(startTime + duration);
        
        this.synthNodes.push({ oscillator, gain: gainNode });
    }
    
    /**
     * Stop synthetic sounds
     */
    stopSynthetic() {
        if (this.synthTimeout) {
            clearTimeout(this.synthTimeout);
        }
        
        if (this.synthNodes) {
            this.synthNodes.forEach(node => {
                if (node.oscillator) {
                    try {
                        node.oscillator.stop();
                    } catch (e) {
                        // Already stopped
                    }
                }
            });
            this.synthNodes = [];
        }
    }
    
    /**
     * Update play button icon
     */
    updatePlayButton() {
        if (!this.playBtn) return;
        
        const icon = this.playBtn.querySelector('i');
        if (icon) {
            icon.className = this.isPlaying ? 'fas fa-pause' : 'fas fa-play';
        }
    }
    
    /**
     * Update progress bar
     */
    updateProgress() {
        if (!this.progressBar || this.duration === 0) return;
        
        const percentage = (this.currentTime / this.duration) * 100;
        this.progressBar.style.width = `${Math.min(100, Math.max(0, percentage))}%`;
    }
    
    /**
     * Start progress animation
     */
    startProgressAnimation() {
        const updateProgress = () => {
            if (this.isSynthetic && this.isPlaying) {
                this.currentTime = (this.audioContext.currentTime - this.synthStartTime) % this.duration;
            }
            
            this.updateProgress();
            
            if (this.isPlaying) {
                this.animationFrame = requestAnimationFrame(updateProgress);
            }
        };
        
        updateProgress();
    }
    
    /**
     * Stop progress animation
     */
    stopProgressAnimation() {
        if (this.animationFrame) {
            cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
    }
    
    /**
     * Show error message
     */
    showError(message) {
        console.error('MIDI Player Error:', message);
        
        // Update UI to show error state
        if (this.playBtn) {
            this.playBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            this.playBtn.title = message;
            this.playBtn.disabled = true;
            this.playBtn.classList.add('error-state');
        }
    }
    
    /**
     * Cleanup resources
     */
    destroy() {
        this.stop();
        this.stopSynthetic();
        
        if (this.audio) {
            this.audio.src = '';
            this.audio = null;
        }
        
        if (this.audioContext) {
            this.audioContext.close();
            this.audioContext = null;
        }
    }
}

// Auto-initialize if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMIDIPlayer);
} else {
    initializeMIDIPlayer();
}

function initializeMIDIPlayer() {
    // This will be called from individual game pages
    console.log('MIDI Player library loaded');
}
