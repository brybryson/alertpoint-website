
// Global variables for pagination and filtering
let currentFilter = 'all';
let currentSearchTerm = '';
let currentActivePage = 1;
let currentArchivedPage = 1;
const itemsPerPage = 9;

// Update time function
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleString('en-US', {
        timeZone: 'Asia/Manila',
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

// Initialize pagination on page load
document.addEventListener('DOMContentLoaded', function() {
    initializePagination();
});

// Initialize pagination
function initializePagination() {
    applyFilters();
    updatePagination('active');
    updatePagination('archived');
}

// Filter users function
function filterUsers(type) {
    console.log(`Filtering by: ${type}`);
    
    currentFilter = type;
    currentActivePage = 1; // Reset to first page when filtering
    currentArchivedPage = 1;
    
    // Update tab appearance
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active', 'bg-blue-600', 'text-white');
        tab.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    });
    
    const activeTab = document.getElementById(`filter-${type}`);
    if (activeTab) {
        activeTab.classList.add('active', 'bg-blue-600', 'text-white');
        activeTab.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    }
    
    // Apply filters
    applyFilters();
}

// Search users function
function searchUsers() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();
    currentSearchTerm = searchTerm;
    currentActivePage = 1; // Reset to first page when searching
    currentArchivedPage = 1;
    console.log(`Searching for: "${searchTerm}"`);
    
    // Apply filters
    applyFilters();
}

// Get filtered cards for a specific section
function getFilteredCards(section) {
    const isArchived = section === 'archived';
    const gridId = isArchived ? 'archived-grid' : 'users-grid';
    const grid = document.getElementById(gridId);
    
    if (!grid) return [];
    
    const allCards = grid.querySelectorAll('.user-card');
    const filteredCards = [];
    
    allCards.forEach(card => {
        let showCard = true;
        
        // Skip no-admins placeholder divs
        if (card.id === 'no-active-admins' || card.id === 'no-archived-admins') {
            return;
        }
        
        // Get card data
        const role = card.getAttribute('data-role');
        const status = card.getAttribute('data-status');
        const name = card.getAttribute('data-name');
        const userRole = card.getAttribute('data-user-role');
        
        // Apply role filter
        if (currentFilter === 'residents' && role !== 'resident') {
            showCard = false;
        } else if (currentFilter === 'admins' && (role !== 'admin' && role !== 'archived-admin')) {
            showCard = false;
        } else if (currentFilter === 'online' && status !== 'online') {
            showCard = false;
        } else if (currentFilter === 'offline' && status !== 'offline') {
            showCard = false;
        }
        
        // Apply search filter
        if (currentSearchTerm && name && !name.includes(currentSearchTerm)) {
            showCard = false;
        }
        
        if (showCard) {
            filteredCards.push(card);
        }
    });
    
    return filteredCards;
}

// Main filter function that handles both filter and search with pagination
function applyFilters() {
    console.log(`Applying filters - Filter: ${currentFilter}, Search: "${currentSearchTerm}"`);
    
    // Handle active section
    const activeFilteredCards = getFilteredCards('active');
    displayPagedCards('active', activeFilteredCards, currentActivePage);
    updatePagination('active', activeFilteredCards.length);
    
    // Handle archived section
    const archivedFilteredCards = getFilteredCards('archived');
    displayPagedCards('archived', archivedFilteredCards, currentArchivedPage);
    updatePagination('archived', archivedFilteredCards.length);
    
    // Show/hide no results message for active section
    const noResultsDiv = document.getElementById('no-results');
    const noActiveAdminsDiv = document.getElementById('no-active-admins');
    
    if (activeFilteredCards.length === 0) {
        if (noActiveAdminsDiv) {
            noActiveAdminsDiv.style.display = 'none';
        }
        if (noResultsDiv) {
            noResultsDiv.classList.remove('hidden');
            noResultsDiv.style.display = 'block';
        }
    } else {
        if (noResultsDiv) {
            noResultsDiv.classList.add('hidden');
            noResultsDiv.style.display = 'none';
        }
        if (noActiveAdminsDiv && activeFilteredCards.length > 0) {
            noActiveAdminsDiv.style.display = 'none';
        }
    }
    
    console.log(`Filter results - Active: ${activeFilteredCards.length}, Archived: ${archivedFilteredCards.length}`);
}

// Display cards for a specific page
function displayPagedCards(section, filteredCards, currentPage) {
    const isArchived = section === 'archived';
    const gridId = isArchived ? 'archived-grid' : 'users-grid';
    const grid = document.getElementById(gridId);
    
    if (!grid) return;
    
    // Hide all user cards first
    const allCards = grid.querySelectorAll('.user-card');
    allCards.forEach(card => {
        // Skip placeholder divs
        if (card.id === 'no-active-admins' || card.id === 'no-archived-admins') {
            return;
        }
        card.style.display = 'none';
    });
    
    // Calculate pagination
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const cardsToShow = filteredCards.slice(startIndex, endIndex);
    
    // Show cards for current page
    cardsToShow.forEach(card => {
        card.style.display = 'block';
        card.style.opacity = '1';
        card.style.transform = 'scale(1)';
    });
}

// Update pagination controls
function updatePagination(section, totalFilteredItems) {
    const isArchived = section === 'archived';
    const prefix = isArchived ? 'archived' : 'active';
    const currentPage = isArchived ? currentArchivedPage : currentActivePage;
    
    // Update showing text
    const startElement = document.getElementById(`${prefix}-showing-start`);
    const endElement = document.getElementById(`${prefix}-showing-end`);
    const totalElement = document.getElementById(`${prefix}-total-count`);
    const paginationButtons = document.getElementById(`${prefix}-pagination-buttons`);
    const paginationContainer = document.getElementById(`${prefix}-pagination`);
    
    if (!startElement || !endElement || !totalElement || !paginationButtons || !paginationContainer) {
        return;
    }
    
    // Update counts
    const startItem = totalFilteredItems > 0 ? (currentPage - 1) * itemsPerPage + 1 : 0;
    const endItem = Math.min(currentPage * itemsPerPage, totalFilteredItems);
    
    startElement.textContent = startItem;
    endElement.textContent = endItem;
    totalElement.textContent = totalFilteredItems;
    
    // Calculate total pages
    const totalPages = Math.ceil(totalFilteredItems / itemsPerPage);
    
    // Hide pagination if not needed
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    } else {
        paginationContainer.style.display = 'flex';
    }
    
    // Generate pagination buttons
    let buttonsHTML = '';
    
    // Previous button
    if (currentPage > 1) {
        buttonsHTML += `
            <button onclick="changePage('${section}', ${currentPage - 1})" 
                    class="px-3 py-2 text-sm text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
    }
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    // Adjust start page if we're near the end
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    // First page and ellipsis
    if (startPage > 1) {
        buttonsHTML += `
            <button onclick="changePage('${section}', 1)" 
                    class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                1
            </button>
        `;
        if (startPage > 2) {
            buttonsHTML += `<span class="px-3 py-2 text-sm text-gray-500">...</span>`;
        }
    }
    
    // Page number buttons
    for (let i = startPage; i <= endPage; i++) {
        const isCurrentPage = i === currentPage;
        buttonsHTML += `
            <button onclick="changePage('${section}', ${i})" 
                    class="px-3 py-2 text-sm ${isCurrentPage 
                        ? 'text-white bg-blue-600 border-blue-600' 
                        : 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50'} border rounded-md">
                ${i}
            </button>
        `;
    }
    
    // Last page and ellipsis
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            buttonsHTML += `<span class="px-3 py-2 text-sm text-gray-500">...</span>`;
        }
        buttonsHTML += `
            <button onclick="changePage('${section}', ${totalPages})" 
                    class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                ${totalPages}
            </button>
        `;
    }
    
    // Next button
    if (currentPage < totalPages) {
        buttonsHTML += `
            <button onclick="changePage('${section}', ${currentPage + 1})" 
                    class="px-3 py-2 text-sm text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
    }
    
    paginationButtons.innerHTML = buttonsHTML;
}

// Change page function
function changePage(section, newPage) {
    if (section === 'active') {
        currentActivePage = newPage;
    } else if (section === 'archived') {
        currentArchivedPage = newPage;
    }
    
    // Re-apply filters to show the new page
    applyFilters();
    
    // Scroll to top of the section
    const sectionElement = document.getElementById(section === 'active' ? 'active-users-section' : 'archived-section');
    if (sectionElement) {
        sectionElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Toggle archived section
function toggleArchivedSection() {
    const archivedSection = document.getElementById('archived-section');
    const toggleBtn = document.getElementById('archived-toggle');
    
    if (archivedSection && toggleBtn) {
        if (archivedSection.classList.contains('hidden')) {
            // Show archived section
            archivedSection.classList.remove('hidden');
            toggleBtn.innerHTML = '<i class="fas fa-users mr-1"></i>Active Users';
            toggleBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
            toggleBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
            
            // Scroll to archived section
            archivedSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            // Hide archived section
            archivedSection.classList.add('hidden');
            toggleBtn.innerHTML = '<i class="fas fa-archive mr-1"></i>Archived';
            toggleBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            toggleBtn.classList.add('bg-red-600', 'hover:bg-red-700');
        }
    }
}


// Modal functions
function openAddAdminModal() {
    const modal = document.getElementById('addAdminModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
}

function closeAddAdminModal() {
    const modal = document.getElementById('addAdminModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Restore scrolling
    }
}



function removeAdmin(adminId) {
    if (confirm('Are you sure you want to remove this admin? This action cannot be undone.')) {
        alert(`Remove admin functionality for ID: ${adminId} - To be implemented`);
        // Here you would typically make an AJAX call to remove the admin
    }
}

// Auto-generate username based on first name
function generateUsername() {
    const firstNameInput = document.getElementById('admin_fn');
    const usernameInput = document.getElementById('username');
    
    if (firstNameInput && usernameInput) {
        const firstName = firstNameInput.value.toLowerCase().replace(/\s+/g, '').replace(/[^a-z0-9]/g, '');
        usernameInput.value = firstName ? `admin_${firstName}` : '';
    }
}

// Real-time search with debouncing
let searchTimeout;
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchUsers();
    }, 300); // Wait 300ms after user stops typing
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Users page initializing...');
    
    // Set up auto-generate username
    const firstNameInput = document.getElementById('admin_fn');
    const usernameInput = document.getElementById('username');
    
    if (firstNameInput && usernameInput) {
        firstNameInput.addEventListener('input', generateUsername);
    }
    
    // Start time updates
    updateTime();
    setInterval(updateTime, 1000);
    
    // Initialize filters
    currentFilter = 'all';
    currentSearchTerm = '';
    
    // Set up search input listener with debouncing
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', debounceSearch);
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                searchUsers();
            }
        });
    }
    
    // Initialize with all users visible
    applyFilters();
    
    console.log('Users page initialized successfully');
});

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const addAdminForm = document.getElementById('addAdminForm');
    if (addAdminForm) {
        addAdminForm.addEventListener('submit', function(e) {
            // Let the form submit normally to PHP
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.classList.remove('hidden');
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
            }
        });
    }
});

// Additional utility functions for future use
function refreshUserList() {
    // Function to refresh the user list (for future AJAX implementation)
    console.log('Refreshing user list...');
    location.reload(); // For now, just reload the page
}

function updateUserStatus(userId, newStatus) {
    // Function to update user status (for future AJAX implementation)
    console.log(`Updating user ${userId} status to ${newStatus}`);
}

// Clear search function
function clearSearch() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.value = '';
        currentSearchTerm = '';
        applyFilters();
    }
}

function countUsers() {
    const userCards = document.querySelectorAll('.user-card');
    let counts = {
        total: 0,
        residents: 0,
        admins: 0,
        online: 0,
        offline: 0
    };
    
    userCards.forEach(card => {
        // Skip placeholder divs
        if (card.id === 'no-active-admins' || card.id === 'no-archived-admins') {
            return;
        }
        
        if (card.style.display !== 'none') {
            counts.total++;
            const role = card.getAttribute('data-role');
            const status = card.getAttribute('data-status');
            
            if (role === 'resident') counts.residents++;
            if (status === 'online') counts.online++;
            if (status === 'offline') counts.offline++;
        }
    });
    
    return counts;
}


// Updated stats function to get database counts and visible counts
function updateStatsCards() {
    const stats = countUsers();
    
    const totalResidentsEl = document.getElementById('total-residents');
    const onlineUsersEl = document.getElementById('online-users');
    const totalAdminsEl = document.getElementById('total-admins');
    const archivedCountEl = document.getElementById('archived-count');
    
    // Update residents and online users with visible counts
    if (totalResidentsEl) totalResidentsEl.textContent = stats.residents;
    if (onlineUsersEl) onlineUsersEl.textContent = stats.online;
    
    // Get database counts from data attributes (set by PHP)
    const adminCard = document.querySelector('[data-total-admins]');
    const archivedCard = document.querySelector('[data-archived-count]');
    
    if (totalAdminsEl && adminCard) {
        const dbAdminCount = adminCard.getAttribute('data-total-admins') || '0';
        totalAdminsEl.textContent = dbAdminCount;
    }
    
    if (archivedCountEl && archivedCard) {
        const dbArchivedCount = archivedCard.getAttribute('data-archived-count') || '0';
        archivedCountEl.textContent = dbArchivedCount;
    }
}

// Enhanced filter function with stats update
function applyFiltersWithStats() {
    applyFilters();
    updateStatsCards();
}

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + F to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    // Escape to clear search
    if (e.key === 'Escape') {
        clearSearch();
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.blur();
        }
    }
});

// Animation helper functions
function fadeIn(element, duration = 300) {
    element.style.opacity = '0';
    element.style.display = 'block';
    
    let start = null;
    function animate(timestamp) {
        if (!start) start = timestamp;
        const progress = timestamp - start;
        
        element.style.opacity = Math.min(progress / duration, 1);
        
        if (progress < duration) {
            requestAnimationFrame(animate);
        }
    }
    
    requestAnimationFrame(animate);
}

function fadeOut(element, duration = 300) {
    let start = null;
    function animate(timestamp) {
        if (!start) start = timestamp;
        const progress = timestamp - start;
        
        element.style.opacity = Math.max(1 - (progress / duration), 0);
        
        if (progress < duration) {
            requestAnimationFrame(animate);
        } else {
            element.style.display = 'none';
        }
    }
    
    requestAnimationFrame(animate);
}

// Initialize stats on page load
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for all elements to be ready
    setTimeout(() => {
        updateStatsCards();
    }, 100);
});