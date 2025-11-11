/**
 * Dark Mode Toggle System
 * Handles theme switching and preference storage
 */

(function() {
    'use strict';

    // Initialize dark mode
    function initDarkMode() {
        // Check if user has a saved preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

        // Apply saved theme or system preference
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
            enableDarkMode(false);
        } else {
            disableDarkMode(false);
        }

        // Create toggle button
        createToggleButton();

        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (!localStorage.getItem('theme')) {
                    if (e.matches) {
                        enableDarkMode(true);
                    } else {
                        disableDarkMode(true);
                    }
                }
            });
        }
    }

    // Enable dark mode
    function enableDarkMode(save = true) {
        document.body.classList.add('dark-mode');
        updateToggleButton(true);

        if (save) {
            localStorage.setItem('theme', 'dark');
            saveThemePreference('dark');
        }
    }

    // Disable dark mode
    function disableDarkMode(save = true) {
        document.body.classList.remove('dark-mode');
        updateToggleButton(false);

        if (save) {
            localStorage.setItem('theme', 'light');
            saveThemePreference('light');
        }
    }

    // Toggle dark mode
    function toggleDarkMode() {
        if (document.body.classList.contains('dark-mode')) {
            disableDarkMode();
        } else {
            enableDarkMode();
        }
    }

    // Create toggle button
    function createToggleButton() {
        // Check if button already exists
        if (document.getElementById('dark-mode-toggle')) {
            return;
        }

        const button = document.createElement('button');
        button.id = 'dark-mode-toggle';
        button.className = 'dark-mode-toggle';
        button.setAttribute('aria-label', 'Toggle dark mode');
        button.setAttribute('title', 'Toggle dark mode');
        button.innerHTML = '<i class="fa fa-moon-o"></i>';

        button.addEventListener('click', toggleDarkMode);

        document.body.appendChild(button);
    }

    // Update toggle button icon
    function updateToggleButton(isDark) {
        const button = document.getElementById('dark-mode-toggle');
        if (button) {
            if (isDark) {
                button.innerHTML = '<i class="fa fa-sun-o"></i>';
                button.setAttribute('title', 'Switch to light mode');
            } else {
                button.innerHTML = '<i class="fa fa-moon-o"></i>';
                button.setAttribute('title', 'Switch to dark mode');
            }
        }
    }

    // Save theme preference to database
    function saveThemePreference(theme) {
        // Only save if user is logged in
        if (typeof $ !== 'undefined' && document.querySelector('[data-user-id]')) {
            $.ajax({
                url: 'save-preference.php',
                method: 'POST',
                data: {
                    preference_key: 'theme',
                    preference_value: theme
                },
                success: function(response) {
                    console.log('Theme preference saved:', theme);
                },
                error: function() {
                    console.log('Failed to save theme preference');
                }
            });
        }
    }

    // Keyboard shortcut: Ctrl+Shift+D or Cmd+Shift+D
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
            e.preventDefault();
            toggleDarkMode();
        }
    });

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDarkMode);
    } else {
        initDarkMode();
    }

    // Export functions for external use
    window.darkMode = {
        enable: enableDarkMode,
        disable: disableDarkMode,
        toggle: toggleDarkMode,
        isEnabled: function() {
            return document.body.classList.contains('dark-mode');
        }
    };

})();
