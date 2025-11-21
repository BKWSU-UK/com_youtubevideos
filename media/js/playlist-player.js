/**
 * Playlist Player - AJAX Video Switching
 * 
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 * @since       1.0.0
 */

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        const playlistItems = document.querySelectorAll('.playlist-video-item');
        const iframe = document.getElementById('playlist-iframe');
        const videoTitle = document.getElementById('current-video-title');
        const videoMeta = document.getElementById('current-video-meta');
        const videoDescription = document.getElementById('current-video-description');

        if (!playlistItems.length || !iframe) {
            return;
        }

        // Get embed parameters from the current iframe URL
        function getEmbedParams() {
            const currentSrc = iframe.src;
            const urlParams = new URLSearchParams(currentSrc.split('?')[1] || '');
            return {
                autoplay: urlParams.get('autoplay') || '1', // Auto-play when switching videos
                rel: urlParams.get('rel') || '0',
                showinfo: urlParams.get('showinfo') || '1'
            };
        }

        // Build YouTube embed URL
        function buildEmbedUrl(youtubeId) {
            const params = getEmbedParams();
            params.autoplay = '1'; // Always autoplay when switching
            const queryString = new URLSearchParams(params).toString();
            return `https://www.youtube.com/embed/${youtubeId}?${queryString}`;
        }

        // Format number with commas
        function formatNumber(num) {
            return parseInt(num).toLocaleString();
        }

        // Format date
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString(undefined, { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }

        // Update video player and info
        function switchVideo(item) {
            const youtubeId = item.dataset.youtubeId;
            const title = item.dataset.videoTitle;
            const description = item.dataset.videoDescription;
            const created = item.dataset.videoCreated;
            const views = parseInt(item.dataset.videoViews) || 0;
            const likes = parseInt(item.dataset.videoLikes) || 0;
            const videoUrl = item.dataset.videoUrl;

            if (!youtubeId) {
                console.error('No YouTube ID found for video');
                return;
            }

            // Update iframe source
            iframe.src = buildEmbedUrl(youtubeId);
            iframe.title = title;

            // Update video title
            if (videoTitle) {
                videoTitle.textContent = title;
            }

            // Update video meta information
            if (videoMeta) {
                // Read display settings from data attributes
                const showDate = videoMeta.dataset.showDate !== '0';
                const showViews = videoMeta.dataset.showViews !== '0';
                
                let metaHtml = '';
                
                if (showDate && created) {
                    metaHtml += `<span class="me-3">
                        <span class="icon-calendar" aria-hidden="true"></span>
                        ${formatDate(created)}
                    </span>`;
                }
                
                if (showViews && views > 0) {
                    metaHtml += `<span class="me-3">
                        <span class="icon-eye" aria-hidden="true"></span>
                        ${formatNumber(views)} views
                    </span>`;
                }
                
                if (likes > 0) {
                    metaHtml += `<span>
                        <span class="icon-heart" aria-hidden="true"></span>
                        ${formatNumber(likes)}
                    </span>`;
                }
                
                videoMeta.innerHTML = metaHtml;
            }

            // Update video description
            if (videoDescription) {
                videoDescription.innerHTML = description || '';
            }

            // Update active state on playlist items
            playlistItems.forEach(function(playlistItem) {
                playlistItem.classList.remove('active');
                const card = playlistItem.querySelector('.card');
                if (card) {
                    card.classList.remove('border-primary', 'bg-light');
                }
                // Remove "Now Playing" badge
                const badge = playlistItem.querySelector('.badge.bg-primary');
                if (badge) {
                    badge.remove();
                }
            });

            // Add active state to current item
            item.classList.add('active');
            const currentCard = item.querySelector('.card');
            if (currentCard) {
                currentCard.classList.add('border-primary', 'bg-light');
            }

            // Add "Now Playing" badge
            const thumbnailContainer = item.querySelector('.position-relative');
            if (thumbnailContainer && !thumbnailContainer.querySelector('.badge.bg-primary')) {
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary position-absolute top-0 start-0 m-1';
                badge.textContent = 'Now Playing';
                thumbnailContainer.appendChild(badge);
            }

            // Update browser URL without reloading (optional - improves SEO and allows bookmarking)
            if (videoUrl && window.history && window.history.pushState) {
                try {
                    window.history.pushState(
                        { videoId: item.dataset.videoId }, 
                        title, 
                        videoUrl
                    );
                    // Update document title
                    if (document.title) {
                        const siteName = document.title.split(' - ')[1] || '';
                        document.title = siteName ? `${title} - ${siteName}` : title;
                    }
                } catch (e) {
                    console.warn('Could not update browser URL:', e);
                }
            }

            // Scroll to top of video player (helpful on mobile)
            const mainVideoContainer = document.querySelector('.main-video-container');
            if (mainVideoContainer && window.innerWidth < 992) {
                mainVideoContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Add click event listeners to all playlist items
        playlistItems.forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                switchVideo(item);
            });

            // Make keyboard accessible
            item.setAttribute('tabindex', '0');
            item.setAttribute('role', 'button');
            item.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    switchVideo(item);
                }
            });
        });

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.videoId) {
                const item = document.querySelector(`.playlist-video-item[data-video-id="${e.state.videoId}"]`);
                if (item) {
                    switchVideo(item);
                }
            }
        });
    });
})();

