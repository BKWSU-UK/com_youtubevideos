document.addEventListener('DOMContentLoaded', function() {
    // Load YouTube IFrame API
    var tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

    var player;
    var modal = document.getElementById('videoModal');

    if (!modal) {
        console.error('Video modal element not found');
        return;
    }

    // Initialize player when API is ready
    window.onYouTubeIframeAPIReady = function() {
        var playerElement = document.getElementById('youtube-player');
        
        if (!playerElement) {
            console.error('YouTube player element not found');
            return;
        }

        player = new YT.Player('youtube-player', {
            height: '390',
            width: '640',
            playerVars: {
                'autoplay': 1,
                'rel': 0,
                'modestbranding': 1
            }
        });
    };

    // Add click handlers to video items
    document.querySelectorAll('.video-item').forEach(function(item) {
        item.addEventListener('click', function() {
            var videoId = this.dataset.videoId;
            var videoTitle = this.dataset.videoTitle || 'Video Player';
            var videoDescription = this.dataset.videoDescription || '';
            
            if (!videoId) {
                console.error('No video ID found for this item');
                return;
            }

            // Update modal title
            var modalTitle = document.getElementById('videoModalLabel');
            if (modalTitle) {
                modalTitle.textContent = videoTitle;
            }

            // Update video description
            var descriptionContainer = document.getElementById('video-description-container');
            var descriptionContent = document.getElementById('video-description-content');
            
            if (descriptionContainer && descriptionContent) {
                if (videoDescription && videoDescription.trim() !== '') {
                    // Convert line breaks to <br> tags and preserve formatting
                    var formattedDescription = videoDescription
                        .replace(/\n/g, '<br>')
                        .replace(/\r/g, '');
                    
                    descriptionContent.innerHTML = formattedDescription;
                    descriptionContainer.style.display = 'block';
                } else {
                    descriptionContainer.style.display = 'none';
                }
            }

            // Initialize player if not already done
            if (typeof YT !== 'undefined' && YT.Player && player) {
                player.loadVideoById(videoId);
            } else {
                console.warn('YouTube player not ready yet');
            }

            // Show modal
            var modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
            modalInstance.show();
        });
    });

    // Stop video when modal is closed
    modal.addEventListener('hidden.bs.modal', function() {
        if (player && typeof player.stopVideo === 'function') {
            player.stopVideo();
        }
    });

    // Handle Clear button functionality
    var clearButton = document.querySelector('.filter-search-actions .btn, .filter-search-actions button, .btn-clear');
    if (clearButton) {
        clearButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Clear the search input
            var searchInput = document.querySelector('.filter-search, input[name="filter[search]"]');
            if (searchInput) {
                searchInput.value = '';
            }
            
            // Submit the form to reload without search
            var form = document.querySelector('.com-youtubevideos-videos__form, form[name="adminForm"]');
            if (form) {
                form.submit();
            } else {
                // Fallback: reload the page
                window.location.href = window.location.pathname;
            }
        });
    }
}); 