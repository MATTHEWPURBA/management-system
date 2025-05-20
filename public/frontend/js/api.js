/**
 * API Service - Centralized API Calls
 *
 * This module handles all API communication with the backend,
 * encapsulating the fetch API and providing a standardized interface
 * for making requests to the server.
 */

const API_URL = "http://127.0.0.1:8000/api";

// Store the authentication token
let authToken = localStorage.getItem("token");

/**
 * Set the authentication token for subsequent API calls
 * @param {string} token - The authentication token
 */
function setAuthToken(token) {
    authToken = token;
    localStorage.setItem("token", token);
}

/**
 * Clear the authentication token
 */
function clearAuthToken() {
    authToken = null;
    localStorage.removeItem("token");
}

/**
 * Get default headers for API requests
 * @returns {Object} Headers object with content type and authorization
 */
function getHeaders() {
    const headers = {
        "Content-Type": "application/json",
        Accept: "application/json",
    };

    if (authToken) {
        headers["Authorization"] = `Bearer ${authToken}`;
    }

    return headers;
}

/**
 * Generic API request function
 * @param {string} endpoint - API endpoint
 * @param {string} method - HTTP method (GET, POST, PUT, DELETE)
 * @param {Object} data - Request payload (for POST/PUT)
 * @returns {Promise} Promise resolving to the API response
 */
async function apiRequest(endpoint, method = "GET", data = null) {
    const url = `${API_URL}${endpoint}`;
    const options = {
        method,
        headers: getHeaders(),
    };

    if (data && (method === "POST" || method === "PUT")) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        const result = await response.json();

        // Check for authentication errors
        if (response.status === 401) {
            clearAuthToken();
            // Redirect to login if not already there
            if (!window.location.pathname.includes("login")) {
                showLoginForm();
            }
        }

        return { status: response.status, data: result };
    } catch (error) {
        console.error("API Request Error:", error);
        return {
            status: 500,
            data: {
                success: false,
                message: "Network error. Please try again.",
            },
        };
    }
}

/**
 * Authentication API functions
 */
const AuthAPI = {
    /**
     * Login user
     * @param {string} email - User email
     * @param {string} password - User password
     * @returns {Promise} Login response
     */
    login: (email, password) =>
        apiRequest("/login", "POST", { email, password }),

    /**
     * Logout user
     * @returns {Promise} Logout response
     */
    logout: () => apiRequest("/logout", "POST"),
};

/**
 * User API functions
 */
const UserAPI = {
    /**
     * Get all users
     * @returns {Promise} Users list
     */
    getUsers: () => apiRequest("/users"),

    /**
     * Get specific user
     * @param {string} id - User ID
     * @returns {Promise} User data
     */
    getUser: (id) => apiRequest(`/users/${id}`),

    /**
     * Create new user
     * @param {Object} userData - User data
     * @returns {Promise} Created user
     */
    createUser: (userData) => apiRequest("/users", "POST", userData),

    /**
     * Update existing user
     * @param {string} id - User ID
     * @param {Object} userData - Updated user data
     * @returns {Promise} Updated user
     */
    updateUser: (id, userData) => apiRequest(`/users/${id}`, "PUT", userData),

    /**
     * Delete user
     * @param {string} id - User ID
     * @returns {Promise} Deletion response
     */
    deleteUser: (id) => apiRequest(`/users/${id}`, "DELETE"),
};

/**
 * Task API functions
 */
const TaskAPI = {
    /**
     * Get all tasks (filtered by role permissions)
     * @returns {Promise} Tasks list
     */
    getTasks: () => apiRequest("/tasks"),

    /**
     * Get specific task
     * @param {string} id - Task ID
     * @returns {Promise} Task data
     */
    getTask: (id) => apiRequest(`/tasks/${id}`),

    /**
     * Create new task
     * @param {Object} taskData - Task data
     * @returns {Promise} Created task
     */
    createTask: (taskData) => apiRequest("/tasks", "POST", taskData),

    /**
     * Update existing task
     * @param {string} id - Task ID
     * @param {Object} taskData - Updated task data
     * @returns {Promise} Updated task
     */
    updateTask: (id, taskData) => apiRequest(`/tasks/${id}`, "PUT", taskData),

    /**
     * Delete task
     * @param {string} id - Task ID
     * @returns {Promise} Deletion response
     */
    deleteTask: (id) => apiRequest(`/tasks/${id}`, "DELETE"),

    /**
     * Export tasks to CSV (admin only)
     * @returns {Promise} CSV download response
     */
    exportTasks: () => {
        window.location.href = `${API_URL}/tasks/export?token=${authToken}`;
        return Promise.resolve({ status: 200 });
    },
};

/**
 * Activity Log API functions
 */
const LogAPI = {
    /**
     * Get activity logs (admin only)
     * @param {Object} filters - Optional filters (page, date range, etc.)
     * @returns {Promise} Logs list
     */
    getLogs: (filters = {}) => {
        let queryParams = "";
        if (Object.keys(filters).length > 0) {
            queryParams = "?" + new URLSearchParams(filters).toString();
        }

        return apiRequest(`/logs${queryParams}`);
    },

    /**
     * Get specific log entry
     * @param {string} id - Log ID
     * @returns {Promise} Log data
     */
    getLog: (id) => apiRequest(`/logs/${id}`),
};

//public/frontend/js/api.js
