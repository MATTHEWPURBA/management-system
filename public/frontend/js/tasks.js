/**
 * Tasks Module
 * 
 * Handles task listing, creation, editing, and deletion.
 * Implements role-based access control for task management.
 */

// DOM elements
const tasksContainer = document.getElementById('tasks-container');
const tasksLink = document.getElementById('tasks-link');
const tasksBody = document.getElementById('tasks-body');
const newTaskBtn = document.getElementById('new-task-btn');

// Task modal elements
const taskModal = new bootstrap.Modal(document.getElementById('task-modal'));
const taskForm = document.getElementById('task-form');
const taskModalLabel = document.getElementById('task-modal-label');
const taskIdInput = document.getElementById('task-id');
const taskTitleInput = document.getElementById('task-title');
const taskDescriptionInput = document.getElementById('task-description');
const taskStatusInput = document.getElementById('task-status');
const taskDueDateInput = document.getElementById('task-due-date');
const taskAssignedToInput = document.getElementById('task-assigned-to');
const saveTaskBtn = document.getElementById('save-task-btn');

// Store available users for assignment
let availableUsers = [];

/**
 * Initialize the tasks module
 */
function initTasks() {
    // Add event listeners
    tasksLink.addEventListener('click', showTasks);
    newTaskBtn.addEventListener('click', showNewTaskModal);
    saveTaskBtn.addEventListener('click', saveTask);
}

/**
 * Show the tasks container and hide other containers
 */
function showTasks() {
    // Hide all containers
    hideAllContainers();
    
    // Show tasks container
    tasksContainer.style.display = 'block';
    
    // Load tasks data
    loadTasks();
}

/**
 * Load tasks data from the API
 */
async function loadTasks() {
    try {
        // Get all tasks
        const response = await TaskAPI.getTasks();
        
        if (response.status === 200 && response.data.success) {
            const tasks = response.data.data;
            
            // Display tasks
            displayTasks(tasks);
        } else {
            console.error('Failed to load tasks:', response.data.message);
        }
    } catch (error) {
        console.error('Tasks loading error:', error);
    }
}

/**
 * Display tasks in the table
 * @param {Array} tasks - List of tasks
 */
function displayTasks(tasks) {
    if (!tasks || !Array.isArray(tasks)) {
        console.error('Invalid tasks data');
        return;
    }
    
    // Clear previous content
    tasksBody.innerHTML = '';
    
    if (tasks.length === 0) {
        // No tasks found
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `<td colspan="6" class="text-center">No tasks found.</td>`;
        tasksBody.appendChild(emptyRow);
        return;
    }
    
    // Get current user for permission checks
    const currentUser = getUser();
    
    // Add each task to the table
    tasks.forEach(task => {
        const row = document.createElement('tr');
        
        // Determine status badge class
        let statusBadgeClass = '';
        switch (task.status) {
            case 'pending':
                statusBadgeClass = 'bg-warning text-dark';
                break;
            case 'in_progress':
                statusBadgeClass = 'bg-primary';
                break;
            case 'done':
                statusBadgeClass = 'bg-success';
                break;
        }
        
        // Format the date
        const dueDate = new Date(task.due_date);
        const formattedDate = dueDate.toLocaleDateString();
        
        // Check if task is overdue
        const isOverdue = task.status !== 'done' && dueDate < new Date();
        const dueDateClass = isOverdue ? 'due-date-danger' : '';
        
        // Generate action buttons based on permissions
        let actionButtons = '';
        
        // Edit button - available based on role and task ownership
        const canEdit = currentUser.role === 'admin' || 
                        task.created_by === currentUser.id || 
                        task.assigned_to === currentUser.id;
        
        if (canEdit) {
            actionButtons += `
                <button class="btn btn-sm btn-primary action-btn" onclick="showEditTaskModal('${task.id}')">
                    <i class="bi bi-pencil"></i> Edit
                </button>
            `;
        }
        
        // Delete button - available for admin or task creator
        const canDelete = currentUser.role === 'admin' || task.created_by === currentUser.id;
        
        if (canDelete) {
            actionButtons += `
                <button class="btn btn-sm btn-danger action-btn" onclick="confirmDeleteTask('${task.id}')">
                    <i class="bi bi-trash"></i> Delete
                </button>
            `;
        }
        
        // Create row content
        row.innerHTML = `
            <td>${task.title}</td>
            <td class="task-description">${task.description}</td>
            <td><span class="badge ${statusBadgeClass}">${task.status.replace('_', ' ')}</span></td>
            <td class="${dueDateClass}">${formattedDate}</td>
            <td>${task.assignee ? task.assignee.name : 'Unassigned'}</td>
            <td>${actionButtons}</td>
        `;
        
        tasksBody.appendChild(row);
    });
}

/**
 * Show the modal for creating a new task
 */
async function showNewTaskModal() {
    // Set modal title
    taskModalLabel.textContent = 'New Task';
    
    // Clear the form
    taskForm.reset();
    taskIdInput.value = '';
    
    // Set default values
    taskStatusInput.value = 'pending';
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    taskDueDateInput.min = today;
    taskDueDateInput.value = today;
    
    // Load available users for assignment
    await loadAvailableUsers();
    
    // Show the modal
    taskModal.show();
}

/**
 * Show the modal for editing an existing task
 * @param {string} taskId - ID of the task to edit
 */
async function showEditTaskModal(taskId) {
    try {
        // Set modal title
        taskModalLabel.textContent = 'Edit Task';
        
        // Load task data
        const response = await TaskAPI.getTask(taskId);
        
        if (response.status === 200 && response.data.success) {
            const task = response.data.data;
            
            // Set form values
            taskIdInput.value = task.id;
            taskTitleInput.value = task.title;
            taskDescriptionInput.value = task.description;
            taskStatusInput.value = task.status;
            
            // Format and set due date
            const dueDate = new Date(task.due_date);
            taskDueDateInput.value = dueDate.toISOString().split('T')[0];
            
            // Load available users for assignment
            await loadAvailableUsers();
            
            // Set assigned user
            taskAssignedToInput.value = task.assigned_to;
            
            // Show the modal
            taskModal.show();
        } else {
            console.error('Failed to load task data:', response.data.message);
        }
    } catch (error) {
        console.error('Error loading task for edit:', error);
    }
}

/**
 * Load available users for task assignment
 * This is affected by role-based permissions:
 * - Admin can assign to anyone
 * - Manager can assign to staff only
 * - Staff can assign to themselves only
 */
async function loadAvailableUsers() {
    try {
        // Clear previous options
        taskAssignedToInput.innerHTML = '';
        
        // Get current user for permission checks
        const currentUser = getUser();
        
        if (hasRole('admin') || hasRole('manager')) {
            // Admin or manager: load users from API
            const response = await UserAPI.getUsers();
            
            if (response.status === 200 && response.data.success) {
                availableUsers = response.data.data;
                
                // Filter users based on role
                let filteredUsers = availableUsers;
                
                if (hasRole('manager')) {
                    // Managers can only assign to staff
                    filteredUsers = availableUsers.filter(user => user.role === 'staff');
                }
                
                // Add options for each user
                filteredUsers.forEach(user => {
                    if (user.status) { // Only active users can be assigned
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = `${user.name} (${user.role})`;
                        taskAssignedToInput.appendChild(option);
                    }
                });
            } else {
                console.error('Failed to load users:', response.data.message);
            }
        } else {
            // Staff can only assign to themselves
            const option = document.createElement('option');
            option.value = currentUser.id;
            option.textContent = `${currentUser.name} (You)`;
            taskAssignedToInput.appendChild(option);
        }
    } catch (error) {
        console.error('Error loading available users:', error);
    }
}

/**
 * Save a task (create new or update existing)
 */
async function saveTask() {
    // Basic validation
    if (!taskForm.checkValidity()) {
        taskForm.reportValidity();
        return;
    }
    
    // Prepare task data
    const taskData = {
        title: taskTitleInput.value.trim(),
        description: taskDescriptionInput.value.trim(),
        status: taskStatusInput.value,
        due_date: taskDueDateInput.value,
        assigned_to: taskAssignedToInput.value
    };
    
    try {
        let response;
        
        if (taskIdInput.value) {
            // Update existing task
            response = await TaskAPI.updateTask(taskIdInput.value, taskData);
        } else {
            // Create new task
            response = await TaskAPI.createTask(taskData);
        }
        
        if (response.status === 200 || response.status === 201) {
            // Success
            taskModal.hide();
            
            // Reload tasks
            loadTasks();
        } else {
            // Handle validation or other errors
            alert(`Error: ${response.data.message}`);
            
            if (response.data.errors) {
                console.error('Validation errors:', response.data.errors);
            }
        }
    } catch (error) {
        console.error('Error saving task:', error);
        alert('An unexpected error occurred. Please try again.');
    }
}

/**
 * Confirm and delete a task
 * @param {string} taskId - ID of the task to delete
 */
function confirmDeleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
        deleteTask(taskId);
    }
}

/**
 * Delete a task
 * @param {string} taskId - ID of the task to delete
 */
async function deleteTask(taskId) {
    try {
        const response = await TaskAPI.deleteTask(taskId);
        
        if (response.status === 200 && response.data.success) {
            // Success, reload tasks
            loadTasks();
        } else {
            alert(`Error: ${response.data.message}`);
        }
    } catch (error) {
        console.error('Error deleting task:', error);
        alert('An unexpected error occurred while deleting the task.');
    }
}


//public/frontend/js/tasks.js