/**
 * Activity Logs Module
 * 
 * Handles viewing of activity logs (system audit trail).
 * This module is only accessible to admin users.
 */

// DOM elements
const logsContainer = document.getElementById('logs-container');
const logsLink = document.getElementById('logs-link');
const logsBody = document.getElementById('logs-body');
const logsPagination = document.getElementById('logs-pagination');

// Current page for pagination
let currentPage = 1;

/**
 * Initialize the logs module
 */
function initLogs() {
    // Add event listeners
    logsLink.addEventListener('click', showLogs);
}

/**
 * Show the logs container and hide other containers
 */
function showLogs() {
    // Check if user has permission to view logs (admin only)
    if (!hasRole('admin')) {
        alert('You do not have permission to access activity logs.');
        return;
    }
    
    // Hide all containers
    hideAllContainers();
    
    // Show logs container
    logsContainer.style.display = 'block';
    
    // Reset to first page
    currentPage = 1;
    
    // Load logs data
    loadLogs();
}

/**
 * Load activity logs from the API
 */
async function loadLogs() {
    try {
        // Get logs with pagination
        const response = await LogAPI.getLogs({ page: currentPage });
        
        if (response.status === 200 && response.data.success) {
            const logsData = response.data.data;
            
            // Display logs
            displayLogs(logsData.data);
            
            // Setup pagination
            setupPagination(logsData);
        } else {
            console.error('Failed to load logs:', response.data.message);
        }
    } catch (error) {
        console.error('Logs loading error:', error);
    }
}

/**
 * Display logs in the table
 * @param {Array} logs - List of activity logs
 */
function displayLogs(logs) {
    if (!logs || !Array.isArray(logs)) {
        console.error('Invalid logs data');
        return;
    }
    
    // Clear previous content
    logsBody.innerHTML = '';
    
    if (logs.length === 0) {
        // No logs found
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `<td colspan="4" class="text-center">No activity logs found.</td>`;
        logsBody.appendChild(emptyRow);
        return;
    }
    
    // Add each log to the table
    logs.forEach(log => {
        const row = document.createElement('tr');
        
        // Format the date
        const loggedAt = new Date(log.logged_at);
        const formattedDate = loggedAt.toLocaleString();
        
        // Get the user name
        const userName = log.user ? log.user.name : 'System';
        
        // Format the action for display
        const actionDisplay = formatActionType(log.action);
        
        // Create row content
        row.innerHTML = `
            <td>${userName}</td>
            <td>${actionDisplay}</td>
            <td>${log.description}</td>
            <td>${formattedDate}</td>
        `;
        
        logsBody.appendChild(row);
    });
}

/**
 * Format action type for display
 * @param {string} action - Action type from API
 * @returns {string} Formatted action for display
 */
function formatActionType(action) {
    if (!action) return 'Unknown';
    
    // Split by underscore and capitalize each word
    return action.split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

/**
 * Set up pagination controls
 * @param {Object} paginationData - Pagination data from API
 */
function setupPagination(paginationData) {
    // Clear previous pagination
    logsPagination.innerHTML = '';
    
    // If only one page, don't show pagination
    if (paginationData.last_page <= 1) {
        return;
    }
    
    // Create pagination container
    const paginationNav = document.createElement('nav');
    paginationNav.setAttribute('aria-label', 'Activity log pagination');
    
    const paginationList = document.createElement('ul');
    paginationList.className = 'pagination';
    
    // Previous page button
    const prevItem = document.createElement('li');
    prevItem.className = `page-item ${paginationData.current_page === 1 ? 'disabled' : ''}`;
    
    const prevLink = document.createElement('a');
    prevLink.className = 'page-link';
    prevLink.href = '#';
    prevLink.setAttribute('aria-label', 'Previous');
    prevLink.innerHTML = '<span aria-hidden="true">&laquo;</span>';
    prevLink.addEventListener('click', (e) => {
        e.preventDefault();
        if (paginationData.current_page > 1) {
            currentPage = paginationData.current_page - 1;
            loadLogs();
        }
    });
    
    prevItem.appendChild(prevLink);
    paginationList.appendChild(prevItem);
    
    // Page number buttons
    const totalPages = paginationData.last_page;
    const currentPage = paginationData.current_page;
    
    // Determine range of pages to show
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    
    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const pageItem = document.createElement('li');
        pageItem.className = `page-item ${i === currentPage ? 'active' : ''}`;
        
        const pageLink = document.createElement('a');
        pageLink.className = 'page-link';
        pageLink.href = '#';
        pageLink.textContent = i;
        pageLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (i !== currentPage) {
                currentPage = i;
                loadLogs();
            }
        });
        
        pageItem.appendChild(pageLink);
        paginationList.appendChild(pageItem);
    }
    
    // Next page button
    const nextItem = document.createElement('li');
    nextItem.className = `page-item ${paginationData.current_page === paginationData.last_page ? 'disabled' : ''}`;
    
    const nextLink = document.createElement('a');
    nextLink.className = 'page-link';
    nextLink.href = '#';
    nextLink.setAttribute('aria-label', 'Next');
    nextLink.innerHTML = '<span aria-hidden="true">&raquo;</span>';
    nextLink.addEventListener('click', (e) => {
        e.preventDefault();
        if (paginationData.current_page < paginationData.last_page) {
            currentPage = paginationData.current_page + 1;
            loadLogs();
        }
    });
    
    nextItem.appendChild(nextLink);
    paginationList.appendChild(nextItem);
    
    // Add pagination to the DOM
    paginationNav.appendChild(paginationList);
    logsPagination.appendChild(paginationNav);
}


//public/frontend/js/logs.js