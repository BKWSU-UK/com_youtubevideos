/**
 * @package     Joomla.Site
 * @subpackage  mod_youtube_single
 *
 * @copyright   Copyright (C) 2025 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

(function(window) {
    'use strict';
    
    // Prevent multiple executions
    if (window.ModYoutubeSinglePlayer) {
        return;
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Initialize YouTube player for a thumbnail
     */
    function initializePlayer(thumbnail) {
        // Check if already initialized to prevent double-binding
        if (thumbnail.dataset.playerInitialized === 'true') {
            return;
        }
        
        thumbnail.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const youtubeId = this.dataset.youtubeId;
            const title = this.dataset.videoTitle || 'YouTube Video';
            const autoplay = this.dataset.autoplay || '1';
            const aspectRatioPercent = parseFloat(this.dataset.aspectRatio) || 56.25; // Default to 16:9 if not set
            
            // Get the module wrapper before replacing the element
            const moduleWrapper = this.closest('.mod-youtube-single');
            
            // Sanitize autoplay parameter (must be 0 or 1)
            const sanitizedAutoplay = (autoplay === '1' || autoplay === 1) ? '1' : '0';
            
            // Build YouTube URL parameters
            const youtubeParams = 'rel=0&autoplay=' + sanitizedAutoplay;
            
            // Create the iframe HTML with the video's aspect ratio
            const iframeHtml = `
                <div class="youtube-player-container" style="position: relative; width: 100%; padding-bottom: ${aspectRatioPercent}%;">
                    <iframe 
                        src="https://www.youtube.com/embed/${escapeHtml(youtubeId)}?${youtubeParams}" 
                        title="${escapeHtml(title)}"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                        class="rounded">
                    </iframe>
                </div>
            `;
            
            // Replace only the thumbnail link (this element) with the iframe
            this.outerHTML = iframeHtml;
            
            // Add a loaded class to the module wrapper
            if (moduleWrapper) {
                moduleWrapper.classList.add('player-loaded');
            }
        });
        
        // Mark as initialized
        thumbnail.dataset.playerInitialized = 'true';
    }
    
    /**
     * Initialize all YouTube players on the page
     */
    function initializeAllPlayers() {
        // Find all clickable thumbnails that should open a player
        const thumbnails = document.querySelectorAll('.mod-youtube-single [data-youtube-id]');
        
        thumbnails.forEach(function(thumbnail) {
            initializePlayer(thumbnail);
        });
    }
    
    // Expose initialization function globally
    window.ModYoutubeSinglePlayer = {
        init: initializeAllPlayers,
        version: '1.2.10'
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeAllPlayers);
    } else {
        // DOM already loaded (script loaded dynamically)
        initializeAllPlayers();
    }
    
    // Re-initialize if new modules are added dynamically
    if (window.MutationObserver) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            // Check if the added node or its children contain YouTube thumbnails
                            const thumbnails = node.querySelectorAll ? 
                                node.querySelectorAll('.mod-youtube-single [data-youtube-id]') : [];
                            
                            // Also check if the node itself is a thumbnail
                            if (node.matches && node.matches('.mod-youtube-single [data-youtube-id]')) {
                                initializePlayer(node);
                            }
                            
                            thumbnails.forEach(function(thumbnail) {
                                initializePlayer(thumbnail);
                            });
                        }
                    });
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
})(window);

