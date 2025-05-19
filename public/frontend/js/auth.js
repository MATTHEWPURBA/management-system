/**
 * Authentication Module
 * 
 * Handles user authentication, session management, and role-based access control.
 * This module integrates with the API service to perform login/logout operations
 * and maintains the user's session state.
 */

// Store the current user information
let currentUser = null;

// DOM elements
const loginContainer = document.getElementById('login-container');
const loginForm = document.getElementById('login-form');
const loginError = document.getElementById('login-error');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const logoutLink = document.getElementById('logout-link');
const userNameElement = document.getElementById('user-name');

// Admin-only menu items
const adminUsersMenuItem = document.getElementById('admin-users-menu-item');
const adminLogsMenuItem = document.getElementById('admin-logs-menu-item');

/**
 * Initialize the authentication module
 */
function initAuth() {
    // Add event listeners
    loginForm.addEventListener('submit', handleLogin);
    logoutLink.addEventListener('click', handleLogout);
    
    // Check if user is already authenticated
    checkAuthStatus();
}

/**
 * Check if user is already authenticated based on token in localStorage
 */
async function checkAuthStatus() {
    const token = localStorage.getItem('token');
    const user = localStorage.getItem('user');
    
    if (token && user) {
        try {
            // Set the token for API calls
            setAuthToken(token);
            
            // Parse the stored user
            currentUser = JSON.parse(user);
            
            // Update the UI
            updateAuthUI(true);
            
            // Show the dashboard as the default view
            showDashboard();
        } catch (error) {
            console.error('Error parsing stored user data:', error);
            clearAuth();
            showLoginForm();
        }
    } else {
        // No token or user found, show login form
        showLoginForm();
    }
}

/**
 * Handle login form submission
 * @param {Event} event - Form submit event
 */
async function handleLogin(event) {
    event.preventDefault();
    
    // Clear previous errors
    loginError.style.display = 'none';
    
    // Get form values
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    
    // Basic validation
    if (!email || !password) {
        showLoginError('Please enter both email and password.');
        return;
    }
    
    // Disable form during API call
    toggleLoginForm(false);
    
    try {
        // Call the login API
        const response = await AuthAPI.login(email, password);
        
        if (response.status === 200 && response.data.success) {
            // Login successful
            const { token_type, access_token, user } = response.data.data;
            
            // Store the token and user data
            setAuthToken(access_token);
            currentUser = user;
            localStorage.setItem('user', JSON.stringify(user));
            
            // Update UI based on authentication
            updateAuthUI(true);
            
            // Show the dashboard
            showDashboard();
            
            // Reset the form
            loginForm.reset();
        } else {
            // Login failed
            const message = response.data.message || 'Invalid login credentials.';
            showLoginError(message);
        }
    } catch (error) {
        console.error('Login error:', error);
        showLoginError('A network error occurred. Please try again.');
    } finally {
        // Re-enable the form
        toggleLoginForm(true);
    }
}

/**
 * Handle user logout
 * @param {Event} event - Click event
 */
async function handleLogout(event) {
    event.preventDefault();
    
    try {
        // Call the logout API
        await AuthAPI.logout();
    } catch (error) {
        console.error('Logout error:', error);
    } finally {
        // Clear authentication data regardless of API response
        clearAuth();
        
        // Show login form
        showLoginForm();
    }
}

/**
 * Display an error message on the login form
 * @param {string} message - Error message to display
 */
function showLoginError(message) {
    loginError.textContent = message;
    loginError.style.display = 'block';
}

/**
 * Enable or disable the login form
 * @param {boolean} enabled - Whether the form should be enabled
 */
function toggleLoginForm(enabled) {
    const inputs = loginForm.querySelectorAll('input, button');
    inputs.forEach(input => {
        input.disabled = !enabled;
    });
}

/**
 * Update the UI based on authentication status
 * @param {boolean} isAuthenticated - Whether the user is authenticated
 */
function updateAuthUI(isAuthenticated) {
    if (isAuthenticated && currentUser) {
        // Update user name in the sidebar
        userNameElement.textContent = currentUser.name;
        
        // Update menu visibility based on user role
        if (currentUser.role === 'admin') {
            adminUsersMenuItem.style.display = 'block';
            adminLogsMenuItem.style.display = 'block';
        } else if (currentUser.role === 'manager') {
            adminUsersMenuItem.style.display = 'block';
            adminLogsMenuItem.style.display = 'none';
        } else {
            adminUsersMenuItem.style.display = 'none';
            adminLogsMenuItem.style.display = 'none';
        }
    }
}

/**
 * Clear all authentication data
 */
function clearAuth() {
    // Clear token and user data
    clearAuthToken();
    localStorage.removeItem('user');
    currentUser = null;
    
    // Reset UI elements
    userNameElement.textContent = 'User';
    adminUsersMenuItem.style.display = 'none';
    adminLogsMenuItem.style.display = 'none';
}

/**
 * Show the login form and hide other containers
 */
function showLoginForm() {
    // Hide all containers
    hideAllContainers();
    
    // Show login container
    loginContainer.style.display = 'block';
}

/**
 * Check if the current user has a specific role
 * @param {string|Array} roles - Role or array of roles to check
 * @returns {boolean} True if user has the role, false otherwise
 */
function hasRole(roles) {
    if (!currentUser) return false;
    
    if (Array.isArray(roles)) {
        return roles.includes(currentUser.role);
    }
    
    return currentUser.role === roles;
}

/**
 * Get the current authenticated user
 * @returns {Object|null} Current user object or null if not authenticated
 */
function getUser() {
    return currentUser;
}

//public/frontend/js/auth.js
