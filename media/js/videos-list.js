/**
 * Videos list JavaScript - Dynamically adjusts list limit dropdown
 * to show only multiples of videos_per_row
 * 
 * @package     Joomla.Site
 * @subpackage  com_youtubevideos
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

(function() {
    'use strict';

    /**
     * Initialize the videos list functionality
     */
    function initVideosList() {
        const limitSelect = document.querySelector('select[name="list[limit]"]');
        
        if (!limitSelect) {
            return;
        }

        // Get custom limit options from data attribute
        const limitOptionsAttr = limitSelect.getAttribute('data-limit-options');
        
        if (!limitOptionsAttr) {
            return;
        }

        try {
            const limitOptions = JSON.parse(limitOptionsAttr);
            const currentValue = parseInt(limitSelect.value) || limitOptions[0];
            
            // Clear existing options
            limitSelect.innerHTML = '';
            
            // Add new options based on videos_per_row multiples
            limitOptions.forEach(function(value) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                
                // Select the closest option to current value
                if (value <= currentValue && (!limitSelect.querySelector('option[selected]') || value > parseInt(limitSelect.querySelector('option[selected]').value))) {
                    option.selected = true;
                } else if (limitOptions.indexOf(value) === 0 && !limitSelect.querySelector('option[selected]')) {
                    option.selected = true;
                }
                
                limitSelect.appendChild(option);
            });
            
            // If current value doesn't match any option, select the next highest
            if (!limitSelect.querySelector('option[selected]')) {
                const nextHighest = limitOptions.find(v => v >= currentValue) || limitOptions[limitOptions.length - 1];
                const optionToSelect = limitSelect.querySelector(`option[value="${nextHighest}"]`);
                if (optionToSelect) {
                    optionToSelect.selected = true;
                }
            }
            
        } catch (e) {
            console.error('Error parsing limit options:', e);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVideosList);
    } else {
        initVideosList();
    }
})();

