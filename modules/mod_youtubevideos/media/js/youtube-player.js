(function() {
    // Global players storage to avoid conflicts if script is loaded multiple times
    window.com_youtubevideos_players = window.com_youtubevideos_players || {};
    var players = window.com_youtubevideos_players;
    var apiReady = false;

    // Load YouTube IFrame API
    if (!window.YT) {
        var tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    }

    // Initialize players when API is ready
    window.onYouTubeIframeAPIReady = (function(oldCallback) {
        return function() {
            if (oldCallback) oldCallback();
            apiReady = true;
            initAllPlayers();
        };
    })(window.onYouTubeIframeAPIReady);

    function initAllPlayers() {
        if (!apiReady || !window.YT || !window.YT.Player) return;
        
        document.querySelectorAll('[id^="youtube-player"]').forEach(function(playerElement) {
            var playerId = playerElement.id;
            var moduleId = playerId.replace('youtube-player', '');
            
            if (!players[moduleId] || typeof players[moduleId].loadVideoById !== 'function') {
                try {
                    players[moduleId] = new YT.Player(playerId, {
                        height: '100%',
                        width: '100%',
                        playerVars: {
                            'autoplay': 1,
                            'rel': 0,
                            'modestbranding': 1,
                            'origin': window.location.origin
                        }
                    });
                } catch (e) {
                    console.error('Failed to initialize YouTube player for module ' + moduleId, e);
                }
            }
        });
    }

    // If API is already loaded
    if (window.YT && window.YT.Player) {
        apiReady = true;
        initAllPlayers();
    }

    // Use Bootstrap modal events for cleaner logic
    document.addEventListener('show.bs.modal', function(event) {
        var modal = event.target;
        if (!modal || !modal.id || !modal.id.startsWith('videoModal')) return;

        var moduleId = modal.id.replace('videoModal', '');
        var trigger = event.relatedTarget;
        
        // If triggered via JS without relatedTarget, we might need to find the data elsewhere
        // But in our component/module, it's always from a click on .video-item
        if (!trigger) return;

        var videoId = trigger.dataset.videoId;
        var videoTitle = trigger.dataset.videoTitle || 'Video Player';
        var videoDescription = trigger.dataset.videoDescription || '';

        if (!videoId) return;

        // Update modal title
        var modalTitle = document.getElementById('videoModalLabel' + moduleId);
        if (modalTitle) {
            modalTitle.textContent = videoTitle;
        }

        // Update video description
        var descriptionContainer = document.getElementById('video-description-container' + moduleId);
        var descriptionContent = document.getElementById('video-description-content' + moduleId);
        
        if (descriptionContainer && descriptionContent) {
            if (videoDescription && videoDescription.trim() !== '') {
                descriptionContent.innerHTML = videoDescription
                    .replace(/\n/g, '<br>')
                    .replace(/\r/g, '');
                descriptionContainer.style.display = 'block';
            } else {
                descriptionContainer.style.display = 'none';
            }
        }

        // Play video
        if (apiReady && players[moduleId] && typeof players[moduleId].loadVideoById === 'function') {
            players[moduleId].loadVideoById(videoId);
        } else {
            // Re-initialize if missing and try again
            initAllPlayers();
            setTimeout(function() {
                if (players[moduleId] && typeof players[moduleId].loadVideoById === 'function') {
                    players[moduleId].loadVideoById(videoId);
                }
            }, 500);
        }
    });

    // Stop video when modals are closed
    document.addEventListener('hidden.bs.modal', function(event) {
        var modal = event.target;
        if (!modal || !modal.id || !modal.id.startsWith('videoModal')) return;

        var moduleId = modal.id.replace('videoModal', '');
        if (players[moduleId] && typeof players[moduleId].stopVideo === 'function') {
            players[moduleId].stopVideo();
        }
    });

    // Handle Clear button functionality
    document.addEventListener('click', function(e) {
        var clearBtn = e.target.closest('.filter-search-actions .btn, .filter-search-actions button, .btn-clear');
        if (clearBtn) {
            e.preventDefault();
            var form = clearBtn.closest('form');
            if (form) {
                var searchInput = form.querySelector('.filter-search, input[name="filter[search]"]');
                if (searchInput) {
                    searchInput.value = '';
                }
                form.submit();
            } else {
                window.location.href = window.location.pathname;
            }
        }
    });
})();
