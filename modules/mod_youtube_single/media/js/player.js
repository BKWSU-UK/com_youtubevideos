/**
 * @package     Joomla.Site
 * @subpackage  mod_youtube_single
 *
 * @copyright   Copyright (C) 2025 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

document.addEventListener('DOMContentLoaded', function() {
    // Find all clickable thumbnails that should open a player
    const thumbnails = document.querySelectorAll('.mod-youtube-single [data-youtube-id]');
    
    thumbnails.forEach(function(thumbnail) {
        thumbnail.addEventListener('click', function(e) {
            e.preventDefault();
            
            const youtubeId = this.dataset.youtubeId;
            const title = this.dataset.videoTitle || 'YouTube Video';
            const autoplay = this.dataset.autoplay || '1';
            const aspectRatioPercent = parseFloat(this.dataset.aspectRatio) || 56.25; // Default to 16:9 if not set
            
            // Find the container to replace
            let container = this.closest('.card, .youtube-thumbnail');
            if (!container) {
                container = this.parentElement;
            }
            
            // Build YouTube URL parameters
            const youtubeParams = 'rel=0&autoplay=' + autoplay;
            
            // Create the iframe HTML with the video's aspect ratio
            const iframeHtml = `
                <div class="youtube-player-container" style="position: relative; width: 100%; padding-bottom: ${aspectRatioPercent}%;">
                    <iframe 
                        src="https://www.youtube.com/embed/${youtubeId}?${youtubeParams}" 
                        title="${escapeHtml(title)}"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"
                        class="rounded">
                    </iframe>
                </div>
            `;
            
            // Replace the container content with the iframe
            container.innerHTML = iframeHtml;
            
            // Add a loaded class to the module wrapper
            const moduleWrapper = container.closest('.mod-youtube-single');
            if (moduleWrapper) {
                moduleWrapper.classList.add('player-loaded');
            }
        });
    });
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

