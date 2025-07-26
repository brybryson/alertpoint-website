// Sample data structure based on your database schema
let users = [
    {
        id: 1,
        type: 'resident',
        firstName: 'Juan',
        middleName: 'Santos',
        lastName: 'Dela Cruz',
        phoneNo: '09123456789',
        address: { houseNo: 123, street: 'Rizal Street' },
        password: 'hashed_password',
        role: 'RESIDENT',
        status: 'online',
        lastSeen: new Date(),
        archived: false,
        userImg: null
    },
    {
        id: 2,
        type: 'resident',
        firstName: 'Maria',
        middleName: 'Garcia',
        lastName: 'Santos',
        phoneNo: '09987654321',
        address: { houseNo: 456, street: 'Bonifacio Avenue' },
        password: 'hashed_password',
        role: 'RESIDENT',
        status: 'offline',
        lastSeen: new Date(Date.now() - 3600000),
        archived: false,
        userImg: null
    },
    {
        id: 3,
        type: 'admin',
        firstName: 'Roberto',
        middleName: 'Cruz',
        lastName: 'Martinez',
        username: 'admin_roberto',
        password: 'hashed_password',
        role: 'Barangay Captain',
        status: 'online',
        lastSeen: new Date(),
        archived: false,
        userImg: null
    },
    {
        id: 4,
        type: 'admin',
        firstName: 'Anna',
        middleName: 'Luz',
        lastName: 'Reyes',
        username: 'admin_anna',
        password: 'hashed_password',
        role: 'Emergency Coordinator',
        status: 'offline',
        lastSeen: new Date(Date.now() - 7200000),
        archived: false,
        userImg: null
    },
    {
        id: 5,
        type: 'resident',
        firstName: 'Pedro',
        middleName: 'Luis',
        lastName: 'Gonzales',
        phoneNo: '09111222333',
        address: { houseNo: 789, street: 'Mabini Street' },
        password: 'hashed_password',
        role: 'RESIDENT',
        status: 'online',
        lastSeen: new Date(),
        archived: true,
        userImg: null
    }
];

let currentFilter = 'all';
let archivedVisible = false;

function updateStats() {
    const activeUsers = users.filter(u => !u.archived);
    const residents = activeUsers.filter(u => u.type === 'resident');
    const admins = activeUsers.filter(u => u.type === 'admin');
    const onlineUsers = activeUsers.filter(u => u.status === 'online');
    const archivedUsers = users.filter(u => u.archived);

    document.getElementById('total-residents').textContent = residents.length;
    document.getElementById('online-users').textContent = onlineUsers.length;
    document.getElementById('total-admins').textContent = admins.length;
    document.getElementById('archived-count').textContent = archivedUsers.length;
}

function createUserCard(user) {
    const fullName = `${user.firstName} ${user.middleName || ''} ${user.lastName}`.replace(/\s+/g, ' ').trim();
    const initials = `${user.firstName.charAt(0)}${user.lastName.charAt(0)}`;
    const statusClass = user.status === 'online' ? 'status-online' : 'status-offline';
    const statusText = user.status === 'online' ? 'Online' : 'Offline';
    
    const addressText = user.type === 'resident' 
        ? `${user.address.houseNo} ${user.address.street}`
        : user.role;

    const contactInfo = user.type === 'resident' 
        ? `<i class="fas fa-phone text-gray-400 mr-2"></i>${user.phoneNo}`
        : `<i class="fas fa-user-shield text-gray-400 mr-2"></i>${user.username}`;

    return `
        <div class="user-card bg-white rounded-xl p-6 ${user.archived ? 'opacity-75' : ''}">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center space-x-4">
                    <div class="user-avatar rounded-full relative">
                        ${initials}
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 ${statusClass} rounded-full border-2 border-white"></div>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 text-lg">${fullName}</h3>
                        <p class="text-sm text-gray-600">${addressText}</p>
                        <p class="text-xs text-gray-500 flex items-center mt-1">
                            ${contactInfo}
                        </p>
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    <span class="px-3 py-1 rounded-full text-xs font-medium ${user.status === 'online' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${statusText}
                    </span>
                    <span class="text-xs text-gray-500 mt-1 capitalize">${user.type}</span>
                </div>
            </div>
            
            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <div class="text-xs text-gray-500">
                    Last seen: ${formatLastSeen(user.lastSeen)}
                </div>
                <div class="flex space-x-2">
                    ${user.archived ? `
                        <button onclick="unarchiveUser(${user.id})" class="action-btn bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">
                            <i class="fas fa-undo mr-1"></i>Unarchive
                        </button>
                        <button onclick="deleteUser(${user.id})" class="action-btn bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs">
                            <i class="fas fa-trash mr-1"></i>Delete
                        </button>
                    ` : `
                        <button onclick="editUser(${user.id})" class="action-btn bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>
                        <button onclick="archiveUser(${user.id})" class="action-btn bg-orange-600 hover:bg-orange-700 text-white px-3 py-1 rounded text-xs">
                            <i class="fas fa-archive mr-1"></i>Archive
                        </button>
                    `}
                </div>
            </div>
        </div>
    `;
}

function formatLastSeen(date) {
    const now = new Date();
    const diffMs = now - new Date(date);
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    return `${diffDays}d ago`;
}

function renderUsers() {
    const activeUsersGrid = document.getElementById('users-grid');
    const archivedGrid = document.getElementById('archived-grid');
    
    let filteredUsers = users.filter(user => {
        if (currentFilter === 'all') return !user.archived;
        if (currentFilter === 'residents') return user.type === 'resident' && !user.archived;
        if (currentFilter === 'admins') return user.type === 'admin' && !user.archived;
        if (currentFilter === 'online') return user.status === 'online' && !user.archived;
        if (currentFilter === 'offline') return user.status === 'offline' && !user.archived;
        return !user.archived;
    });

    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    if (searchTerm) {
        filteredUsers = filteredUsers.filter(user => 
            `${user.firstName} ${user.middleName || ''} ${user.lastName}`.toLowerCase().includes(searchTerm) ||
            (user.phoneNo && user.phoneNo.toString().includes(searchTerm)) ||
            (user.username && user.username.toLowerCase().includes(searchTerm)) ||
            (user.role && user.role.toLowerCase().includes(searchTerm))
        );
    }

    activeUsersGrid.innerHTML = filteredUsers.length > 0 
        ? filteredUsers.map(createUserCard).join('') 
        : '<div class="col-span-full text-center py-12 text-gray-500"><i class="fas fa-users text-4xl mb-4"></i><p>No users found</p></div>';

    const archivedUsers = users.filter(user => user.archived);
    if (archivedGrid) {
        archivedGrid.innerHTML = archivedUsers.length > 0 
            ? archivedUsers.map(createUserCard).join('') 
            : '<div class="col-span-full text-center py-12 text-gray-500"><i class="fas fa-archive text-4xl mb-4"></i><p>No archived users</p></div>';
    }
}

function filterUsers(filter) {
    currentFilter = filter;
    
    // Update active tab
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.classList.remove('active');
        tab.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    });
    
    const activeTab = document.getElementById(`filter-${filter}`);
    if (activeTab) {
        activeTab.classList.add('active');
        activeTab.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
    }
    
    renderUsers();
}

function searchUsers() {
    renderUsers();
}

function toggleArchivedSection() {
    const archivedSection = document.getElementById('archived-section');
    const toggleBtn = document.getElementById('archived-toggle');
    
    if (!archivedSection || !toggleBtn) return;
    
    archivedVisible = !archivedVisible;
    
    if (archivedVisible) {
        archivedSection.classList.remove('hidden');
        toggleBtn.innerHTML = '<i class="fas fa-eye-slash mr-2"></i>Hide Archived';
        toggleBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
        toggleBtn.classList.add('bg-gray-600', 'hover:bg-gray-700');
    } else {
        archivedSection.classList.add('hidden');
        toggleBtn.innerHTML = '<i class="fas fa-archive mr-2"></i>View Archived';
        toggleBtn.classList.remove('bg-gray-600', 'hover:bg-gray-700');
        toggleBtn.classList.add('bg-red-600', 'hover:bg-red-700');
    }
}

function editUser(userId) {
    const user = users.find(u => u.id === userId);
    if (user) {
        alert(`Edit functionality for ${user.firstName} ${user.lastName} would open an edit modal here.`);
        // Implementation for edit modal would go here
    }
}

function archiveUser(userId) {
    const user = users.find(u => u.id === userId);
    if (user && confirm(`Are you sure you want to archive ${user.firstName} ${user.lastName}?`)) {
        user.archived = true;
        user.status = 'offline';
        updateStats();
        renderUsers();
        
        // Show success message
        showNotification(`${user.firstName} ${user.lastName} has been archived.`, 'warning');
    }
}

function unarchiveUser(userId) {
    const user = users.find(u => u.id === userId);
    if (user && confirm(`Are you sure you want to unarchive ${user.firstName} ${user.lastName}?`)) {
        user.archived = false;
        updateStats();
        renderUsers();
        
        // Show success message
        showNotification(`${user.firstName} ${user.lastName} has been unarchived.`, 'success');
    }
}

function deleteUser(userId) {
    const user = users.find(u => u.id === userId);
    if (user && confirm(`Are you sure you want to permanently delete ${user.firstName} ${user.lastName}? This action cannot be undone.`)) {
        users = users.filter(u => u.id !== userId);
        updateStats();
        renderUsers();
        
        // Show success message
        showNotification(`${user.firstName} ${user.lastName} has been permanently deleted.`, 'error');
    }
}

function showAddUserModal() {
    alert('Add new resident functionality would open a modal form here.');
    // Implementation for add user modal would go here
}

function showAddAdminModal() {
    const addAdminModal = document.getElementById('addAdminModal');
    if (addAdminModal) {
        addAdminModal.classList.remove('hidden');
    }
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
    
    let bgColor, icon;
    switch(type) {
        case 'success':
            bgColor = 'bg-green-600 text-white';
            icon = 'fas fa-check-circle';
            break;
        case 'warning':
            bgColor = 'bg-orange-600 text-white';
            icon = 'fas fa-exclamation-triangle';
            break;
        case 'error':
            bgColor = 'bg-red-600 text-white';
            icon = 'fas fa-times-circle';
            break;
        default:
            bgColor = 'bg-blue-600 text-white';
            icon = 'fas fa-info-circle';
    }
    
    notification.className += ` ${bgColor}`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="${icon} mr-3"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 hover:bg-black hover:bg-opacity-20 rounded p-1">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }, 5000);
}

// Initialize the page
function init() {
    // Check if required elements exist before calling functions
    if (typeof updateTime === 'function') {
        updateTime();
        setInterval(updateTime, 1000);
    }
    
    updateStats();
    renderUsers();
    
    // Simulate random status changes for demo
    setInterval(() => {
        const randomUser = users[Math.floor(Math.random() * users.length)];
        if (!randomUser.archived && Math.random() > 0.8) {
            randomUser.status = randomUser.status === 'online' ? 'offline' : 'online';
            randomUser.lastSeen = new Date();
            updateStats();
            renderUsers();
        }
    }, 10000);
}

// Start the application
document.addEventListener('DOMContentLoaded', init);