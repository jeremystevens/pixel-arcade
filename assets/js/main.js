/**
 * Main JavaScript for Retro Arcade High Scores
 * Handles global functionality and interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Retro Arcade initialized');
    
    // Initialize mobile menu
    initializeMobileMenu();
    
    // Initialize retro effects
    initializeRetroEffects();
    
    // Initialize scroll animations
    initializeScrollAnimations();
    
    // Initialize keyboard navigation
    initializeKeyboardNavigation();
    
    // Add retro sound effects
    initializeSoundEffects();
    
    // Initialize high scores functionality
    initializeHighScores();
});

/**
 * Initialize mobile menu functionality
 */
function initializeMobileMenu() {
    const toggleButton = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (toggleButton && mobileMenu) {
        toggleButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
            mobileMenu.classList.toggle('show');
            
            // Animate the hamburger icon
            const icon = toggleButton.querySelector('i');
            if (icon) {
                if (mobileMenu.classList.contains('show')) {
                    icon.className = 'fas fa-times';
                } else {
                    icon.className = 'fas fa-bars';
                }
            }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!toggleButton.contains(e.target) && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.remove('show');
                
                const icon = toggleButton.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-bars';
                }
            }
        });
    }
}

/**
 * Initialize retro visual effects
 */
function initializeRetroEffects() {
    // Add random glitch effect to game cards
    const gameCards = document.querySelectorAll('.retro-card');
    
    gameCards.forEach(card => {
        // Random hover delay for more organic feel
        card.addEventListener('mouseenter', function() {
            setTimeout(() => {
                this.style.transform = `translateY(-5px) scale(1.02) rotate(${Math.random() * 2 - 1}deg)`;
            }, Math.random() * 100);
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
    
    // Add typing effect to main title
    addTypingEffect();
    
    // Add random sparkle effects
    addSparkleEffects();
}

/**
 * Add typing effect to main title
 */
function addTypingEffect() {
    const title = document.querySelector('h1');
    if (!title || title.dataset.typed) return;
    
    title.dataset.typed = 'true';
    const originalText = title.textContent;
    const iconMatch = originalText.match(/<i[^>]*>.*?<\/i>/);
    let icon = '';
    let text = originalText;
    
    if (iconMatch) {
        icon = iconMatch[0];
        text = originalText.replace(iconMatch[0], '').trim();
    }
    
    title.innerHTML = icon;
    
    let i = 0;
    const typeSpeed = 100;
    
    function typeWriter() {
        if (i < text.length) {
            title.innerHTML = icon + text.substring(0, i + 1);
            i++;
            setTimeout(typeWriter, typeSpeed);
        }
    }
    
    setTimeout(typeWriter, 500);
}

/**
 * Add sparkle effects randomly
 */
function addSparkleEffects() {
    function createSparkle() {
        const sparkle = document.createElement('div');
        sparkle.style.cssText = `
            position: fixed;
            width: 4px;
            height: 4px;
            background: #00ffff;
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            box-shadow: 0 0 10px #00ffff;
            animation: sparkle 2s linear forwards;
        `;
        
        sparkle.style.left = Math.random() * window.innerWidth + 'px';
        sparkle.style.top = Math.random() * window.innerHeight + 'px';
        
        document.body.appendChild(sparkle);
        
        // Remove after animation
        setTimeout(() => {
            if (sparkle.parentNode) {
                sparkle.parentNode.removeChild(sparkle);
            }
        }, 2000);
    }
    
    // Add sparkle animation CSS
    if (!document.getElementById('sparkle-styles')) {
        const style = document.createElement('style');
        style.id = 'sparkle-styles';
        style.textContent = `
            @keyframes sparkle {
                0% {
                    opacity: 0;
                    transform: scale(0);
                }
                50% {
                    opacity: 1;
                    transform: scale(1);
                }
                100% {
                    opacity: 0;
                    transform: scale(0);
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Create sparkles randomly
    setInterval(createSparkle, 3000);
}

/**
 * Initialize scroll animations
 */
function initializeScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe retro boxes and cards
    const animatedElements = document.querySelectorAll('.retro-box, .retro-card');
    animatedElements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
        observer.observe(el);
    });
}

/**
 * Initialize keyboard navigation
 */
function initializeKeyboardNavigation() {
    let currentFocus = -1;
    const focusableElements = document.querySelectorAll('a, button, input, select, textarea');
    
    document.addEventListener('keydown', function(e) {
        // Arrow key navigation
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            
            if (e.key === 'ArrowDown') {
                currentFocus = Math.min(currentFocus + 1, focusableElements.length - 1);
            } else {
                currentFocus = Math.max(currentFocus - 1, 0);
            }
            
            if (focusableElements[currentFocus]) {
                focusableElements[currentFocus].focus();
            }
        }
        
        // Escape key to close mobile menu
        if (e.key === 'Escape') {
            const mobileMenu = document.getElementById('mobile-menu');
            if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.remove('show');
            }
        }
        
        // Space bar for play/pause MIDI
        if (e.key === ' ' && e.target.tagName !== 'INPUT') {
            e.preventDefault();
            const playBtn = document.getElementById('play-btn');
            if (playBtn && !playBtn.disabled) {
                playBtn.click();
            }
        }
    });
}

/**
 * Initialize retro sound effects
 */
function initializeSoundEffects() {
    // Create audio context for sound effects
    let audioContext;
    
    try {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    } catch (e) {
        console.log('Web Audio API not supported');
        return;
    }
    
    /**
     * Play a retro beep sound
     */
    function playBeep(frequency = 800, duration = 100, type = 'square') {
        if (!audioContext) return;
        
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(frequency, audioContext.currentTime);
        oscillator.type = type;
        
        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + duration / 1000);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + duration / 1000);
    }
    
    // Add hover sounds to interactive elements
    const interactiveElements = document.querySelectorAll('a, button, .retro-card');
    
    interactiveElements.forEach(element => {
        element.addEventListener('mouseenter', () => {
            playBeep(1200, 50);
        });
        
        element.addEventListener('click', () => {
            playBeep(800, 100);
        });
    });
    
    // Add special sounds for game cards
    const gameCards = document.querySelectorAll('.retro-card');
    gameCards.forEach((card, index) => {
        card.addEventListener('mouseenter', () => {
            const frequencies = [440, 554, 659, 783]; // Musical notes
            playBeep(frequencies[index % frequencies.length], 150);
        });
    });
}

/**
 * Initialize high scores functionality
 */
function initializeHighScores() {
    const toggleButton = document.getElementById('toggleScores');
    const allScoresTable = document.getElementById('allScoresTable');
    const toggleText = document.getElementById('toggleText');
    
    if (!toggleButton || !allScoresTable || !toggleText) {
        return; // Elements not found on this page
    }
    
    let isExpanded = false;
    
    toggleButton.addEventListener('click', function() {
        isExpanded = !isExpanded;
        
        if (isExpanded) {
            // Show all scores
            allScoresTable.classList.remove('hidden');
            allScoresTable.style.opacity = '0';
            allScoresTable.style.transform = 'translateY(-20px)';
            
            // Animate in
            setTimeout(() => {
                allScoresTable.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                allScoresTable.style.opacity = '1';
                allScoresTable.style.transform = 'translateY(0)';
            }, 10);
            
            toggleText.textContent = 'HIDE ALL SCORES';
            const icon = toggleButton.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-eye-slash mr-2';
            }
        } else {
            // Hide all scores
            allScoresTable.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            allScoresTable.style.opacity = '0';
            allScoresTable.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                allScoresTable.classList.add('hidden');
                allScoresTable.style.transition = '';
            }, 300);
            
            toggleText.textContent = 'SHOW ALL SCORES';
            const icon = toggleButton.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-eye mr-2';
            }
        }
        
        // Play retro sound effect
        if (window.playBeep) {
            playBeep(isExpanded ? 1000 : 600, 80);
        }
    });
}

/**
 * Utility function to format numbers with retro styling
 */
function formatRetroNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Utility function to create retro loading animation
 */
function showRetroLoader(element) {
    if (!element) return;
    
    const loader = document.createElement('div');
    loader.className = 'retro-loader';
    loader.innerHTML = `
        <div class="text-neon-blue text-2xl">
            <span class="loading-dots">●</span>
            <span class="loading-dots animation-delay-200">●</span>
            <span class="loading-dots animation-delay-400">●</span>
        </div>
        <div class="text-sm text-neon-purple mt-2">LOADING...</div>
    `;
    
    element.appendChild(loader);
    return loader;
}

/**
 * Utility function to remove retro loader
 */
function hideRetroLoader(loader) {
    if (loader && loader.parentNode) {
        loader.parentNode.removeChild(loader);
    }
}

/**
 * Error handling with retro styling
 */
function showRetroError(message, container) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'retro-error text-center py-8';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle text-6xl error-state mb-4"></i>
        <p class="text-xl error-state">${message}</p>
        <p class="text-sm text-gray-400 mt-2">Press CONTINUE to retry</p>
    `;
    
    if (container) {
        container.appendChild(errorDiv);
    }
    
    return errorDiv;
}

/**
 * Success message with retro styling
 */
function showRetroSuccess(message, container) {
    const successDiv = document.createElement('div');
    successDiv.className = 'retro-success text-center py-8';
    successDiv.innerHTML = `
        <i class="fas fa-check-circle text-6xl success-state mb-4"></i>
        <p class="text-xl success-state">${message}</p>
    `;
    
    if (container) {
        container.appendChild(successDiv);
    }
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (successDiv.parentNode) {
            successDiv.parentNode.removeChild(successDiv);
        }
    }, 3000);
    
    return successDiv;
}

// Export utilities for use in other scripts
window.RetroUtils = {
    formatNumber: formatRetroNumber,
    showLoader: showRetroLoader,
    hideLoader: hideRetroLoader,
    showError: showRetroError,
    showSuccess: showRetroSuccess
};
