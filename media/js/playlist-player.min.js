/**
 * Playlist Player - switches the playlist video without a page reload.
 *
 * Sidebar items are real links, so they work without JavaScript and in new tabs.
 *
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 * @since       1.0.0
 */
(function () {
    'use strict';

    function text(key, fallback) {
        if (window.Joomla && Joomla.Text && typeof Joomla.Text._ === 'function') {
            var value = Joomla.Text._(key, fallback);
            if (value && value !== key) {
                return value;
            }
        }
        return fallback;
    }

    function metaItem(iconClass, label) {
        var wrapper = document.createElement('span');
        var icon = document.createElement('span');

        wrapper.className = 'me-3';
        icon.className = iconClass;
        icon.setAttribute('aria-hidden', 'true');
        wrapper.appendChild(icon);
        wrapper.appendChild(document.createTextNode(' ' + label));

        return wrapper;
    }

    function init() {
        var items = Array.prototype.slice.call(document.querySelectorAll('.playlist-video-item'));
        var iframe = document.getElementById('playlist-iframe');
        var videoTitle = document.getElementById('current-video-title');
        var videoMeta = document.getElementById('current-video-meta');
        var videoDescription = document.getElementById('current-video-description');

        if (!items.length || !iframe) {
            return;
        }

        function buildEmbedUrl(youtubeId) {
            var url = new URL(iframe.src, window.location.href);

            url.pathname = '/embed/' + encodeURIComponent(youtubeId);
            url.searchParams.set('autoplay', '1');
            url.searchParams.set('rel', '0');
            url.searchParams.delete('showinfo');

            return url.toString();
        }

        function findItem(videoId) {
            return items.filter(function (item) {
                return item.dataset.videoId === String(videoId);
            })[0] || null;
        }

        function updateMeta(item) {
            if (!videoMeta) {
                return;
            }

            var views = parseInt(item.dataset.videoViews, 10) || 0;
            var likes = parseInt(item.dataset.videoLikes, 10) || 0;

            videoMeta.textContent = '';

            if (videoMeta.dataset.showDate !== '0' && item.dataset.videoDate) {
                videoMeta.appendChild(metaItem('icon-calendar', item.dataset.videoDate));
            }

            if (videoMeta.dataset.showViews !== '0' && views > 0) {
                videoMeta.appendChild(metaItem('icon-eye', views.toLocaleString() + ' ' + text('COM_YOUTUBEVIDEOS_JS_VIEWS', 'views')));
            }

            if (likes > 0) {
                videoMeta.appendChild(metaItem('icon-heart', likes.toLocaleString()));
            }
        }

        function setActive(item) {
            items.forEach(function (other) {
                var badge = other.querySelector('.playlist-video-item__now');

                other.classList.remove('active', 'border-primary');
                other.removeAttribute('aria-current');

                if (badge) {
                    badge.remove();
                }
            });

            item.classList.add('active', 'border-primary');
            item.setAttribute('aria-current', 'true');

            var thumb = item.querySelector('.playlist-video-item__thumb');

            if (thumb) {
                var badge = document.createElement('span');
                badge.className = 'badge text-bg-primary position-absolute top-0 start-0 m-1 playlist-video-item__now';
                badge.textContent = text('COM_YOUTUBEVIDEOS_JS_NOW_PLAYING', 'Now Playing');
                thumb.appendChild(badge);
            }
        }

        /**
         * @param {HTMLElement} item        The playlist item to show.
         * @param {boolean}     pushHistory Add a history entry (false when handling Back/Forward).
         */
        function switchVideo(item, pushHistory) {
            var youtubeId = item.dataset.youtubeId;
            var title = item.dataset.videoTitle || '';

            if (!youtubeId) {
                return;
            }

            // Changing an existing iframe's src adds a history entry, which breaks Back/Forward,
            // so swap in a fresh iframe instead.
            var fresh = iframe.cloneNode(false);
            fresh.src = buildEmbedUrl(youtubeId);
            fresh.title = title;
            iframe.replaceWith(fresh);
            iframe = fresh;

            if (videoTitle) {
                videoTitle.textContent = title;
            }

            updateMeta(item);

            if (videoDescription) {
                // Plain text only; CSS keeps the line breaks.
                videoDescription.textContent = item.dataset.videoDescription || '';
                videoDescription.classList.add('is-plain-text');
            }

            setActive(item);

            if (item.dataset.pageTitle) {
                document.title = item.dataset.pageTitle;
            }

            if (pushHistory && window.history && window.history.pushState) {
                window.history.pushState({ videoId: item.dataset.videoId }, '', item.href);
            }

            var container = document.querySelector('.main-video-container');
            if (container && window.innerWidth < 992) {
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Record the initial entry so Back returns to the first video.
        var initial = items.filter(function (item) { return item.classList.contains('active'); })[0];
        if (initial && window.history && window.history.replaceState) {
            window.history.replaceState({ videoId: initial.dataset.videoId }, '', window.location.href);
        }

        items.forEach(function (item) {
            item.addEventListener('click', function (event) {
                if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return; // Let the browser open the link normally.
                }

                event.preventDefault();

                if (!item.classList.contains('active')) {
                    switchVideo(item, true);
                }
            });
        });

        window.addEventListener('popstate', function (event) {
            var item = event.state && event.state.videoId ? findItem(event.state.videoId) : null;

            if (item) {
                switchVideo(item, false);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
