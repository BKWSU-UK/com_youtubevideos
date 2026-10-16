/**
 * YouTube modal player for com_youtubevideos grids and mod_youtubevideos.
 *
 * Cards are real links (<a class="video-item" href="...">). When a card has
 * data-yv-modal="#videoModal{suffix}", a plain left click opens that modal and
 * plays the video; any other click (new tab, middle click) follows the link.
 *
 * Modal element ids: videoModal{suffix}, videoModalLabel{suffix},
 * youtube-player{suffix}, video-description-container{suffix},
 * video-description-content{suffix}.
 *
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 * @since       1.0.0
 */
(function () {
    'use strict';

    // Guard against the script being included twice (component + module).
    if (window.comYoutubevideosPlayer) {
        return;
    }

    var MODAL_PREFIX = 'videoModal';
    var players = {};
    var triggers = {};
    var apiCallbacks = [];
    var apiRequested = false;

    window.comYoutubevideosPlayer = { players: players };

    function text(key, fallback) {
        if (window.Joomla && Joomla.Text && typeof Joomla.Text._ === 'function') {
            var value = Joomla.Text._(key, fallback);
            if (value && value !== key) {
                return value;
            }
        }
        return fallback;
    }

    function apiReady() {
        return !!(window.YT && window.YT.Player);
    }

    /**
     * Run a callback once the YouTube IFrame API is available, loading it on first use.
     */
    function withApi(callback) {
        if (apiReady()) {
            callback();
            return;
        }

        apiCallbacks.push(callback);

        if (apiRequested) {
            return;
        }

        apiRequested = true;

        var previous = window.onYouTubeIframeAPIReady;
        window.onYouTubeIframeAPIReady = function () {
            if (typeof previous === 'function') {
                previous();
            }
            var queue = apiCallbacks.splice(0);
            queue.forEach(function (fn) { fn(); });
        };

        if (!document.querySelector('script[src="https://www.youtube.com/iframe_api"]')) {
            var tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            tag.async = true;
            document.head.appendChild(tag);
        }
    }

    function play(suffix, videoId) {
        var player = players[suffix];

        if (player) {
            if (typeof player.loadVideoById === 'function') {
                player.loadVideoById(videoId);
            } else {
                // Still initialising: play this one when ready.
                player.pendingVideoId = videoId;
                player.cancelled = false;
            }
            return;
        }

        var element = document.getElementById('youtube-player' + suffix);

        if (!element) {
            return;
        }

        players[suffix] = new window.YT.Player(element, {
            height: '100%',
            width: '100%',
            videoId: videoId,
            playerVars: {
                autoplay: 1,
                rel: 0,
                playsinline: 1,
                origin: window.location.origin
            },
            events: {
                onReady: function (event) {
                    var target = event.target;
                    var pending = target.pendingVideoId;

                    delete target.pendingVideoId;

                    if (target.cancelled) {
                        // The modal was closed before the player was ready.
                        target.stopVideo();
                    } else if (pending && pending !== videoId) {
                        target.loadVideoById(pending);
                    } else {
                        target.playVideo();
                    }
                }
            }
        });
    }

    function suffixOf(modal) {
        if (!modal || !modal.id || modal.id.indexOf(MODAL_PREFIX) !== 0) {
            return null;
        }
        return modal.id.substring(MODAL_PREFIX.length);
    }

    function setDescription(suffix, description) {
        var container = document.getElementById('video-description-container' + suffix);
        var content = document.getElementById('video-description-content' + suffix);

        if (!container || !content) {
            return;
        }

        var hasText = !!(description && description.trim() !== '');

        // textContent only: descriptions are untrusted text. Line breaks are kept by CSS (white-space: pre-line).
        content.textContent = hasText ? description : '';
        container.hidden = !hasText;
        container.style.display = hasText ? '' : 'none';
        container.classList.toggle('d-none', !hasText);
    }

    // Open the modal from a card link.
    document.addEventListener('click', function (event) {
        var card = event.target.closest ? event.target.closest('a.video-item[data-yv-modal]') : null;

        if (!card || event.defaultPrevented || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        var modalElement = document.querySelector(card.getAttribute('data-yv-modal'));

        if (!modalElement || !window.bootstrap || !window.bootstrap.Modal) {
            return; // Fall back to following the link.
        }

        event.preventDefault();
        window.bootstrap.Modal.getOrCreateInstance(modalElement).show(card);
    });

    document.addEventListener('show.bs.modal', function (event) {
        var suffix = suffixOf(event.target);
        var trigger = event.relatedTarget;

        if (suffix === null || !trigger || !trigger.dataset || !trigger.dataset.videoId) {
            return;
        }

        triggers[suffix] = trigger;

        var title = document.getElementById('videoModalLabel' + suffix);
        if (title) {
            title.textContent = trigger.dataset.videoTitle || text('COM_YOUTUBEVIDEOS_JS_VIDEO_PLAYER', 'Video Player');
        }

        setDescription(suffix, trigger.dataset.videoDescription || '');

        var videoId = trigger.dataset.videoId;
        withApi(function () { play(suffix, videoId); });
    });

    document.addEventListener('hidden.bs.modal', function (event) {
        var suffix = suffixOf(event.target);

        if (suffix === null) {
            return;
        }

        var player = players[suffix];
        if (player && typeof player.stopVideo === 'function') {
            player.stopVideo();
        } else if (player) {
            player.cancelled = true;
        }

        // Return focus to the card that opened the modal.
        var trigger = triggers[suffix];
        if (trigger && typeof trigger.focus === 'function' && document.body.contains(trigger)) {
            trigger.focus();
        }
        delete triggers[suffix];
    });

    // Optional explicit clear buttons for search forms.
    document.addEventListener('click', function (event) {
        var clear = event.target.closest ? event.target.closest('.btn-clear, [data-yv-clear]') : null;
        var form = clear ? clear.closest('form') : null;

        if (!form) {
            return;
        }

        event.preventDefault();
        form.querySelectorAll('input[name="filter[search]"]').forEach(function (input) {
            input.value = '';
        });
        form.submit();
    });
})();
