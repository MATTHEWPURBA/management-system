/**
 * Dashboard Module
 * 
 * Handles the main dashboard functionality, including summary statistics
 * and recent tasks display. This is the default view after login.
 */

// DOM elements
const dashboardContainer = document.getElementById('dashboard-container');
const totalTasksElement = document.getElementById('total-tasks');
const pendingTasksElement = document.getElementById('pending-tasks');
const completedTasksElement = document.getElementById('completed-tasks');
const recentTasksBody = document.getElementById('recent-tasks-body');
const dashboardLink = document.getElementById('dashboard-link');

/**
 * Initialize the dashboard module
 */
function initDashboard() {
    // Add event listeners
    dashboardLink.addEventListener('click', showDashboard);
}

/**
 * Show the dashboard and hide other containers
 */
function showDashboard() {
    // Hide all containers
    hideAllContainers();
    
    // Show dashboard container
    dashboardContainer.style.display = 'block';
    
    // Load dashboard data
    loadDashboardData();
}

/**
 * Load dashboard data from the API
 */
async function loadDashboardData() {
    try {
        // Get all tasks
        const response = await TaskAPI.getTasks();
        
        if (response.status === 200 && response.data.success) {
            const tasks = response.data.data;
            
            // Update dashboard statistics
            updateDashboardStats(tasks);
            
            // Display recent tasks
            displayRecentTasks(tasks);
        } else {
            console.error('Failed to load tasks:', response.data.message);
        }
    } catch (error) {
        console.error('Dashboard data loading error:', error);
    }
}

/**
 * Update dashboard statistics based on tasks data
 * @param {Array} tasks - List of tasks
 */
function updateDashboardStats(tasks) {
    if (!tasks || !Array.isArray(tasks)) {
        console.error('Invalid tasks data for dashboard stats');
        return;
    }
    
    // Calculate statistics
    const totalTasks = tasks.length;
    const pendingTasks = tasks.filter(task => task.status === 'pending').length;
    const inProgressTasks = tasks.filter(task => task.status === 'in_progress').length;
    const completedTasks = tasks.filter(task => task.status === 'done').length;
    
    // Update the UI
    totalTasksElement.textContent = totalTasks;
    pendingTasksElement.textContent = pendingTasks;
    completedTasksElement.textContent = completedTasks;
}

/**
 * Display recent tasks in the dashboard
 * @param {Array} tasks - List of tasks
 */
function displayRecentTasks(tasks) {
    if (!tasks || !Array.isArray(tasks)) {
        console.error('Invalid tasks data for recent tasks');
        return;
    }
    
    // Clear previous content
    recentTasksBody.innerHTML = '';
    
    // Sort tasks by creation date (newest first)
    const sortedTasks = [...tasks].sort((a, b) => {
        return new Date(b.created_at) - new Date(a.created_at);
    });
    
    // Take only the 5 most recent tasks
    const recentTasks = sortedTasks.slice(0, 5);
    
    if (recentTasks.length === 0) {
        // No tasks found
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `<td colspan="4" class="text-center">No tasks found.</td>`;
        recentTasksBody.appendChild(emptyRow);
        return;
    }
    
    // Add each task to the table
    recentTasks.forEach(task => {
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
        
        // Create row content
        row.innerHTML = `
            <td>${task.title}</td>
            <td><span class="badge ${statusBadgeClass}">${task.status.replace('_', ' ')}</span></td>
            <td>${formattedDate}</td>
            <td>${task.assignee ? task.assignee.name : 'Unassigned'}</td>
        `;
        
        recentTasksBody.appendChild(row);
    });
}


//public/frontend/js/dashboard.js