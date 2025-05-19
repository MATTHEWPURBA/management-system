/**
 * Users Module
 * 
 * Handles user management functionality - listing, creation, editing, and deletion.
 * This module is only accessible to admin and manager roles, with different
 * permissions based on role.
 */

// DOM elements
const usersContainer = document.getElementById('users-container');
const usersLink = document.getElementById('users-link');
const usersBody = document.getElementById('users-body');
const newUserBtn = document.getElementById('new-user-btn');

// User modal elements
const userModal = new bootstrap.Modal(document.getElementById('user-modal'));
const userForm = document.getElementById('user-form');
const userModalLabel = document.getElementById('user-modal-label');
const userIdInput = document.getElementById('user-id');
const userNameInput = document.getElementById('user-name');
const userEmailInput = document.getElementById('user-email');
const userPasswordInput = document.getElementById('user-password');
const userRoleInput = document.getElementById('user-role');
const userStatusInput = document.getElementById('user-status');
const saveUserBtn = document.getElementById('save-user-btn');

/**
 * Initialize the users module
 */
function initUsers() {
    // Add event listeners
    usersLink.addEventListener('click', showUsers);
    newUserBtn.addEventListener('click', showNewUserModal);
    saveUserBtn.addEventListener('click', saveUser);
}

/**
 * Show the users container and hide other containers
 */
function showUsers() {
    // Check if user has permission to view users
    if (!hasRole(['admin', 'manager'])) {
        alert('You do not have permission to access user management.');
        return;
    }
    
    // Hide all containers
    hideAllContainers();
    
    // Show users container
    usersContainer.style.display = 'block';
    
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
 */
function showNewUserModal() {
    // Check if user has permission to create users (admin only)
    if (!hasRole('admin')) {
        alert('You do not have permission to create users.');
        return;
    }
    
    // Set modal title
    userModalLabel.textContent = 'New User';
    
    // Clear the form
    userForm.reset();
    userIdInput.value = '';
    
    // Show password field
    document.querySelector('.password-field').style.display = 'block';
    userPasswordInput.required = true;
    
    // Set default values
    userRoleInput.value = 'staff';
    userStatusInput.value = '1';
    
    // Show the modal
    userModal.show();
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
 */
async function saveUser() {
    // Basic validation
    if (!userForm.checkValidity()) {
        userForm.reportValidity();
        return;
    }
    
    // Prepare user data
    const userData = {
        name: userNameInput.value.trim(),
        email: userEmailInput.value.trim(),
        role: userRoleInput.value,
        status: userStatusInput.value === '1'
    };
    
    // Add password only if provided (required for new users, optional for updates)
    if (userPasswordInput.value) {
        userData.password = userPasswordInput.value;
    }
    
    try {
        let response;
        
        if (userIdInput.value) {
            // Update existing user
            response = await UserAPI.updateUser(userIdInput.value, userData);
        } else {
            // Create new user
            response = await UserAPI.createUser(userData);
        }
        
        if (response.status === 200 || response.status === 201) {
            // Success
            userModal.hide();
            
            // Reload users
            loadUsers();
        } else {
            // Handle validation or other errors
            alert(`Error: ${response.data.message}`);
            
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


//public/frontend/js/user.js