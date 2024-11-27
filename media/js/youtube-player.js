document.addEventListener('DOMContentLoaded', function() {
    // Load YouTube IFrame API
    var tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

    var player;
    var modal = document.getElementById('video-modal');

    // Initialize player when API is ready
    window.onYouTubeIframeAPIReady = function() {
        player = new YT.Player('youtube-player', {
            height: '390',
            width: '640',
            playerVars: {
                'autoplay': 1,
                'rel': 0
            }
        });
    };

    // Add click handlers to video items
    document.querySelectorAll('.video-item').forEach(function(item) {
        item.addEventListener('click', function() {
            var videoId = this.dataset.videoId;
            player.loadVideoById(videoId);
            bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    });

    // Stop video when modal is closed
    modal.addEventListener('hidden.bs.modal', function() {
        player.stopVideo();
    });
}); 