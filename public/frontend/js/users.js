/**
 * Users Module
 * 
 * Handles user management functionality - listing, creation, editing, and deletion.
 * This module is only accessible to admin and manager roles, with different
 * permissions based on role.
 */


/**
 * Users Module
 * 
 * Handles user management functionality - listing, creation, editing, and deletion.
 * This module implements a robust pattern for managing user data with proper
 * DOM lifecycle management, error handling, and initialization sequencing.
 */

// DOM element references - declared but not immediately accessed
let usersContainer, usersLink, usersBody, newUserBtn;
let userForm, userModalLabel, userIdInput, userNameInput, userEmailInput;
let userPasswordInput, userRoleInput, userStatusInput, saveUserBtn;

// Modal reference - will be lazily initialized when needed
let userModalInstance = null;


/**
 * Safely initialize the modal instance only when needed
 * This prevents race conditions with DOM loading and Bootstrap initialization
 * @returns {bootstrap.Modal|null} The modal instance or null if initialization failed
 */
function getUserModal() {
    // If we already have an instance, return it (singleton pattern)
    if (userModalInstance) {
        return userModalInstance;
    }
    
    // Safely attempt to get the modal element
    const modalElement = document.getElementById('user-modal');
    if (!modalElement) {
        console.error('Modal element not found in DOM. Ensure the modal HTML is properly defined.');
        return null;
    }
    
    // Safely attempt to initialize the Bootstrap modal
    try {
        userModalInstance = new bootstrap.Modal(modalElement);
        return userModalInstance;
    } catch (error) {
        console.error('Failed to initialize Bootstrap modal:', error);
        console.warn('Ensure Bootstrap JS is properly loaded before initializing modals.');
        return null;
    }
}

/**
 * Initialize the users module with proper DOM ready checks
 * This function safely acquires DOM references and registers event handlers
 * with comprehensive error handling to prevent cascading failures
 */
function initUsers() {
    // Log initialization start for debugging purposes
    console.log('Initializing Users Module...');
    
    try {
        // Safely acquire DOM element references with error handling
        usersContainer = document.getElementById('users-container');
        usersLink = document.getElementById('users-link');
        usersBody = document.getElementById('users-body');
        newUserBtn = document.getElementById('new-user-btn');
        
        userForm = document.getElementById('user-form');
        userModalLabel = document.getElementById('user-modal-label');
        userIdInput = document.getElementById('user-id');
        userNameInput = document.getElementById('user-name');
        userEmailInput = document.getElementById('user-email');
        userPasswordInput = document.getElementById('user-password');
        userRoleInput = document.getElementById('user-role');
        userStatusInput = document.getElementById('user-status');
        saveUserBtn = document.getElementById('save-user-btn');
        
        // Verify critical elements exist before registering event handlers
        if (!usersLink || !newUserBtn || !saveUserBtn) {
            throw new Error('Critical UI elements missing. Check HTML structure and IDs.');
        }
        
        // Add event listeners with error handling
        usersLink.addEventListener('click', showUsers);
        newUserBtn.addEventListener('click', showNewUserModal);
        saveUserBtn.addEventListener('click', saveUser);
        
        console.log('Users Module initialized successfully!');
    } catch (error) {
        // Comprehensive error reporting to aid debugging
        console.error('Failed to initialize Users Module:', error);
        console.warn('This may indicate missing DOM elements or script loading order issues.');
    }
}



/**
 * Show the users container and hide other containers
 * Implements permission checks and state management
 */
function showUsers() {
    // Check if user has permission with proper role validation
    if (!hasRole(['admin', 'manager'])) {
        alert('You do not have permission to access user management.');
        return;
    }
    
    // Hide all containers (view state management)
    hideAllContainers();
    
    // Show users container
    if (usersContainer) {
        usersContainer.style.display = 'block';
    } else {
        console.error('Users container element not found!');
        return;
    }
    
    // Load users data
    loadUsers();
}




/**
 * Load users data from the API
 */
async function loadUsers() {
    try {
        // Get all users
        const response = await UserAPI.getUsers();
        
        if (response.status === 200 && response.data.success) {
            const users = response.data.data;
            
            // Display users
            displayUsers(users);
        } else {
            console.error('Failed to load users:', response.data.message);
        }
    } catch (error) {
        console.error('Users loading error:', error);
    }
}




/**
 * Display users in the table
 * @param {Array} users - List of users
 */
function displayUsers(users) {
    if (!users || !Array.isArray(users)) {
        console.error('Invalid users data');
        return;
    }
    
    // Clear previous content
    usersBody.innerHTML = '';
    
    if (users.length === 0) {
        // No users found
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `<td colspan="5" class="text-center">No users found.</td>`;
        usersBody.appendChild(emptyRow);
        return;
    }
    
    // Get current user for permission checks
    const currentUser = getUser();
    const isAdmin = currentUser.role === 'admin';
    
    // Add each user to the table
    users.forEach(user => {
        const row = document.createElement('tr');
        
        // Generate action buttons based on permissions
        let actionButtons = '';
        
        // Only admin can edit and delete users
        if (isAdmin) {
            actionButtons += `
                <button class="btn btn-sm btn-primary action-btn" onclick="showEditUserModal('${user.id}')">
                    <i class="bi bi-pencil"></i> Edit
                </button>
                
                <button class="btn btn-sm btn-danger action-btn" onclick="confirmDeleteUser('${user.id}')">
                    <i class="bi bi-trash"></i> Delete
                </button>
            `;
        }
        
        // Create row content
        row.innerHTML = `
            <td>${user.name}</td>
            <td>${user.email}</td>
            <td>${user.role}</td>
            <td>
                <span class="badge ${user.status ? 'bg-success' : 'bg-danger'}">
                    ${user.status ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td>${actionButtons}</td>
        `;
        
        usersBody.appendChild(row);
    });
}

/**
 * Show the modal for creating a new user
 * Implements lazy modal initialization and proper state management
 */
function showNewUserModal() {
    // Permission validation
    if (!hasRole('admin')) {
        alert('You do not have permission to create users.');
        return;
    }
    
    // Get the modal instance (lazy initialization)
    const modal = getUserModal();
    if (!modal) {
        alert('Could not initialize the user form. Please try refreshing the page.');
        return;
    }
    
    // Set modal title with null checks
    if (userModalLabel) {
        userModalLabel.textContent = 'New User';
    }
    
    // Clear the form with null checks
    if (userForm) {
        userForm.reset();
    }
    
    if (userIdInput) {
        userIdInput.value = '';
    }
    
    // Show password field with DOM existence checks
    const passwordField = document.querySelector('.password-field');
    if (passwordField) {
        passwordField.style.display = 'block';
    }
    
    if (userPasswordInput) {
        userPasswordInput.required = true;
    }
    
    // Set default values with null checks
    if (userRoleInput) {
        userRoleInput.value = 'staff';
    }
    
    if (userStatusInput) {
        userStatusInput.value = '1';
    }
    
    // Show the modal
    modal.show();
}


/**
 * Show the modal for editing an existing user
 * @param {string} userId - ID of the user to edit
 */
async function showEditUserModal(userId) {
    // Check if user has permission to edit users (admin only)
    if (!hasRole('admin')) {
        alert('You do not have permission to edit users.');
        return;
    }
    
    try {
        // Set modal title
        userModalLabel.textContent = 'Edit User';
        
        // Load user data
        const response = await UserAPI.getUser(userId);
        
        if (response.status === 200 && response.data.success) {
            const user = response.data.data;
            
            // Set form values
            userIdInput.value = user.id;
            userNameInput.value = user.name;
            userEmailInput.value = user.email;
            userRoleInput.value = user.role;
            userStatusInput.value = user.status ? '1' : '0';
            
            // Hide password field for editing (password only required for new users)
            document.querySelector('.password-field').style.display = 'none';
            userPasswordInput.required = false;
            
            // Show the modal
            userModal.show();
        } else {
            console.error('Failed to load user data:', response.data.message);
        }
    } catch (error) {
        console.error('Error loading user for edit:', error);
    }
}

/**
 * Save a user (create new or update existing)
 * Implements comprehensive validation, error handling, and state management
 */
async function saveUser() {
    // Form validation with null checks
    if (userForm && !userForm.checkValidity()) {
        userForm.reportValidity();
        return;
    }
    
    // Safely get form values with defensive programming
    const safeGetValue = (element) => element && element.value ? element.value.trim() : '';
    
    // Prepare user data with null checks and type safety
    const userData = {
        name: safeGetValue(userNameInput),
        email: safeGetValue(userEmailInput),
        role: userRoleInput ? userRoleInput.value : 'staff',
        status: userStatusInput ? (userStatusInput.value === '1') : true
    };
    
    // Add password only if provided (with null checks)
    if (userPasswordInput && userPasswordInput.value) {
        userData.password = userPasswordInput.value;
    }
    
    try {
        let response;
        const userId = userIdInput ? userIdInput.value : '';
        
        // API call with proper error handling
        if (userId) {
            response = await UserAPI.updateUser(userId, userData);
        } else {
            response = await UserAPI.createUser(userData);
        }
        
        // Process API response
        if (response.status === 200 || response.status === 201) {
            // Success - get the modal instance again through our safe function
            const modal = getUserModal();
            if (modal) {
                modal.hide();
            }
            
            // Reload users data
            loadUsers();
        } else {
            // Handle validation or other errors with detailed feedback
            alert(`Error: ${response.data.message || 'Unknown error occurred'}`);
            
            if (response.data.errors) {
                console.error('Validation errors:', response.data.errors);
            }
        }
    } catch (error) {
        console.error('Error saving user:', error);
        alert('An unexpected error occurred. Please try again.');
    }
}

/**
 * Confirm and delete a user
 * @param {string} userId - ID of the user to delete
 */
function confirmDeleteUser(userId) {
    // Check if user has permission to delete users (admin only)
    if (!hasRole('admin')) {
        alert('You do not have permission to delete users.');
        return;
    }
    
    // Get current user to prevent self-deletion
    const currentUser = getUser();
    if (userId === currentUser.id) {
        alert('You cannot delete your own account.');
        return;
    }
    
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        deleteUser(userId);
    }
}

/**
 * Delete a user
 * @param {string} userId - ID of the user to delete
 */
async function deleteUser(userId) {
    try {
        const response = await UserAPI.deleteUser(userId);
        
        if (response.status === 200 && response.data.success) {
            // Success, reload users
            loadUsers();
        } else {
            alert(`Error: ${response.data.message}`);
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        alert('An unexpected error occurred while deleting the user.');
    }
}

document.addEventListener('DOMContentLoaded', initUsers);

//public/frontend/js/user.js