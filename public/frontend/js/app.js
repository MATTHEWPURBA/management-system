/**
 * Main Application Script
 * 
 * This is the entry point of the frontend application. It initializes
 * all modules and provides global utility functions.
 */

/**
 * Initialize the application when DOM is fully loaded
 */
document.addEventListener('DOMContentLoaded', initApp);

/**
 * Initialize the application
 * Start all modules and set up global event handlers
 */
function initApp() {
    // Initialize all modules
    initAuth();
    initDashboard();
    initTasks();
    initUsers();
    initLogs();
    
    // Set minimum date for all date inputs to today
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(input => {
        input.min = today;
    });
}

/**
 * Hide all content containers
 * Used when switching between different views
 */
function hideAllContainers() {
    const containers = [
        document.getElementById('login-container'),
        document.getElementById('dashboard-container'),
        document.getElementById('tasks-container'),
        document.getElementById('users-container'),
        document.getElementById('logs-container')
    ];
    
    containers.forEach(container => {
        if (container) {
            container.style.display = 'none';
        }
    });
}

/**
 * Format a date string for display
 * @param {string} dateString - ISO date string
 * @returns {string} Formatted date string
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

/**
 * Format a date and time string for display
 * @param {string} dateTimeString - ISO date-time string
 * @returns {string} Formatted date-time string
 */
function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleString();
}

/**
 * Validate an email address format
 * @param {string} email - Email address to validate
 * @returns {boolean} True if valid, false otherwise
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Get today's date in ISO format (YYYY-MM-DD)
 * @returns {string} Today's date in ISO format
 */
function getTodayISO() {
    return new Date().toISOString().split('T')[0];
}

//public/frontend/js/app.js