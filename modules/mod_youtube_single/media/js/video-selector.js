/**
 * @package     Joomla.Site
 * @subpackage  mod_youtube_single
 *
 * @copyright   Copyright (C) 2025 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all video selectors on the page
    const selectors = document.querySelectorAll('.youtube-video-selector');
    
    selectors.forEach(function(container) {
        const fieldId = container.dataset.fieldId;
        const hiddenInput = container.querySelector('#' + fieldId);
        const searchInput = container.querySelector('#' + fieldId + '_search');
        const resultsContainer = container.querySelector('.search-results');
        const loadingIndicator = container.querySelector('.search-loading');
        const currentSelection = container.querySelector('.current-selection');
        const clearButton = container.querySelector('.clear-selection');
        
        let searchTimeout = null;
        
        // Handle search input
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Hide results if search is empty
            if (searchTerm.length < 2) {
                resultsContainer.style.display = 'none';
                resultsContainer.innerHTML = '';
                return;
            }
            
            // Debounce the search
            searchTimeout = setTimeout(function() {
                performSearch(searchTerm);
            }, 300);
        });
        
        // Handle clear button
        clearButton.addEventListener('click', function() {
            hiddenInput.value = '';
            searchInput.value = '';
            currentSelection.style.display = 'none';
            resultsContainer.style.display = 'none';
            resultsContainer.innerHTML = '';
        });
        
        // Perform the AJAX search
        function performSearch(searchTerm) {
            loadingIndicator.style.display = 'block';
            resultsContainer.style.display = 'none';
            
            // Get the base URL and token
            const token = Joomla.getOptions('csrf.token', '');
            const baseUrl = Joomla.getOptions('system.paths', {}).base || '';
            
            // Build the AJAX URL
            const url = baseUrl + '/index.php?option=com_youtubevideos&task=videos.searchVideos&format=json';
            
            // Prepare form data
            const formData = new FormData();
            formData.append('search', searchTerm);
            formData.append(token, '1');
            
            // Perform the fetch request
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                loadingIndicator.style.display = 'none';
                
                if (data.success && data.data && data.data.length > 0) {
                    displayResults(data.data);
                } else {
                    displayNoResults();
                }
            })
            .catch(error => {
                loadingIndicator.style.display = 'none';
                console.error('Error searching videos:', error);
                displayError();
            });
        }
        
        // Display search results
        function displayResults(videos) {
            resultsContainer.innerHTML = '';
            
            videos.forEach(function(video) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.dataset.videoId = video.id;
                item.innerHTML = `
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${escapeHtml(video.title)}</h6>
                    </div>
                    <small class="text-muted">${escapeHtml(video.youtube_video_id)}</small>
                `;
                
                item.addEventListener('click', function() {
                    selectVideo(video);
                });
                
                resultsContainer.appendChild(item);
            });
            
            resultsContainer.style.display = 'block';
        }
        
        // Display no results message
        function displayNoResults() {
            resultsContainer.innerHTML = '<div class="list-group-item text-muted">No videos found</div>';
            resultsContainer.style.display = 'block';
        }
        
        // Display error message
        function displayError() {
            resultsContainer.innerHTML = '<div class="list-group-item text-danger">Error searching videos</div>';
            resultsContainer.style.display = 'block';
        }
        
        // Select a video
        function selectVideo(video) {
            hiddenInput.value = video.id;
            searchInput.value = '';
            
            currentSelection.innerHTML = `
                <strong>Selected Video:</strong> ${escapeHtml(video.title)}
                <small class="text-muted">(${escapeHtml(video.youtube_video_id)})</small>
            `;
            currentSelection.className = 'alert alert-info current-selection';
            currentSelection.style.display = 'block';
            
            resultsContainer.style.display = 'none';
            resultsContainer.innerHTML = '';
            
            // Trigger change event for form validation
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Close results when clicking outside
        document.addEventListener('click', function(e) {
            if (!container.contains(e.target)) {
                resultsContainer.style.display = 'none';
            }
        });
    });
});

