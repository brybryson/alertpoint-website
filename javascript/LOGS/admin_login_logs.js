 // Time display
        function updateTime() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Manila',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            };
            document.getElementById('current-time').textContent = now.toLocaleString('en-US', options);
        }

        setInterval(updateTime, 1000);
        updateTime();

        // Settings dropdown functionality
        function toggleSettingsDropdown() {
            const dropdown = document.getElementById('settingsDropdown');
            const isVisible = !dropdown.classList.contains('opacity-0');
            
            if (isVisible) {
                dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            } else {
                dropdown.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
            }
        }

        function confirmLogout() {
            const dropdown = document.getElementById('settingsDropdown');
            dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            
            const modal = document.getElementById('logoutConfirmationModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            return false;
        }

        function closeLogoutModal() {
            const modal = document.getElementById('logoutConfirmationModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function performLogout() {
            const confirmBtn = document.getElementById('confirmLogoutBtn');
            const originalContent = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Logging out...';
            confirmBtn.disabled = true;
            
            setTimeout(() => {
                window.location.href = '/ALERTPOINT/javascript/LOGIN/logout.php';
            }, 1000);
        }

        function openProfileModal() {
            const dropdown = document.getElementById('settingsDropdown');
            dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            
            const modal = document.getElementById('profileModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            return false;
        }

        function closeProfileModal() {
            const modal = document.getElementById('profileModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Log category switching
        function showLogCategory(category) {
        // Hide all sections
        document.getElementById('admin-login-section').style.display = 'none';
        document.getElementById('system-actions-section').style.display = 'none';
        document.getElementById('user-activities-section').style.display = 'none';
        
        // Remove active class from all tabs
        document.querySelectorAll('.category-card').forEach(card => {
            card.classList.remove('ring-2', 'ring-blue-500', 'ring-green-500');
        });
        
        // Show selected section and activate tab
        switch(category) {
            case 'admin-login':
                document.getElementById('admin-login-section').style.display = 'block';
                document.getElementById('admin-login-tab').classList.add('ring-2', 'ring-blue-500');
                break;
            case 'system-actions':
                document.getElementById('system-actions-section').style.display = 'block';
                document.getElementById('system-actions-tab').classList.add('ring-2', 'ring-green-500');
                break;
            case 'user-activities':
                document.getElementById('user-activities-section').style.display = 'block';
                break;
        }
    }

        // Export logs functionality
        function openExportLogsModal() {
            const modal = document.getElementById('exportLogsModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeExportLogsModal() {
            const modal = document.getElementById('exportLogsModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function confirmExportLogs() {
        const confirmBtn = event.target;
        const originalContent = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
        confirmBtn.disabled = true;
        
        // Close modal first
        closeExportLogsModal();
        
        // Start download with current filters
        setTimeout(() => {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('export', 'excel');
            
            // Create a temporary link to trigger download
            const link = document.createElement('a');
            link.href = currentUrl.toString();
            
            // Generate filename with filter info
            let filename = 'admin_logs_';
            const timeframe = currentUrl.searchParams.get('timeframe') || 'all';
            
            if (timeframe === 'today') {
                filename += 'today_';
            } else if (timeframe === 'week') {
                filename += 'this_week_';
            } else if (timeframe === 'month') {
                filename += 'this_month_';
            } else if (timeframe === 'year') {
                filename += 'this_year_';
            } else if (timeframe === 'custom') {
                const startDate = currentUrl.searchParams.get('start_date');
                const endDate = currentUrl.searchParams.get('end_date');
                if (startDate && endDate) {
                    filename += startDate + '_to_' + endDate + '_';
                } else if (startDate) {
                    filename += 'from_' + startDate + '_';
                } else if (endDate) {
                    filename += 'until_' + endDate + '_';
                }
            } else {
                filename += 'all_time_';
            }
            
            filename += new Date().toISOString().split('T')[0] + '.xlsx';
            
            link.download = filename;
            link.click();
            
            // Reset button
            confirmBtn.innerHTML = originalContent;
            confirmBtn.disabled = false;
        }, 500);
    }

         function openClearLogsModal() {
            const modal = document.getElementById('clearLogsModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeClearLogsModal() {
            const modal = document.getElementById('clearLogsModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }


         document.addEventListener('DOMContentLoaded', function() {
            // Set default active category
            showLogCategory('admin-login');
        
             // ADD THIS NEW EVENT LISTENER:
            document.getElementById('exportLogsModal').addEventListener('click', function(e) {
                if (e.target === this) closeExportLogsModal();
            });

            // Modal event listeners
            document.getElementById('cancelLogoutBtn').addEventListener('click', closeLogoutModal);
            document.getElementById('confirmLogoutBtn').addEventListener('click', performLogout);
            
            // Close modals when clicking outside
            document.getElementById('logoutConfirmationModal').addEventListener('click', function(e) {
                if (e.target === this) closeLogoutModal();
            });
            
            document.getElementById('profileModal').addEventListener('click', function(e) {
                if (e.target === this) closeProfileModal();
            });
            
            // ADD THIS NEW EVENT LISTENER:
            document.getElementById('clearLogsModal').addEventListener('click', function(e) {
                if (e.target === this) closeClearLogsModal();
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                const dropdown = document.getElementById('settingsDropdown');
                const cogIcon = e.target.closest('.fa-cog');
                
                if (!cogIcon && !dropdown.contains(e.target)) {
                    dropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
                }
            });
            
            // ESC key to close modals - UPDATE THIS:
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeLogoutModal();
                    closeProfileModal();
                    closeClearLogsModal();
                    closeExportLogsModal(); // ADD THIS LINE
                }
            });
        });

        // Auto-refresh logs every 30 seconds
        setInterval(function() {
            if (document.getElementById('admin-login-section').style.display !== 'none') {
                // Only refresh if we're viewing admin login logs
                location.reload();
            }
        }, 30000);

        function performRealTimeSearch() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const tableRows = document.querySelectorAll('tbody tr');
    
    tableRows.forEach(row => {
        const adminCell = row.querySelector('td:first-child');
        if (adminCell) {
            const adminName = adminCell.textContent.toLowerCase();
            if (adminName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
    
        // Update "no results" message
        const visibleRows = document.querySelectorAll('tbody tr[style=""], tbody tr:not([style])');
        const noResultsDiv = document.getElementById('noSearchResults');
        
        if (visibleRows.length === 0 && searchTerm.trim() !== '') {
            if (!noResultsDiv) {
                const tableContainer = document.querySelector('.overflow-x-auto');
                const noResultsHtml = `
                    <div id="noSearchResults" class="text-center py-8">
                        <i class="fas fa-search text-gray-400 text-2xl mb-2"></i>
                        <p class="text-gray-600">No admin records match your search</p>
                    </div>
                `;
                tableContainer.insertAdjacentHTML('afterend', noResultsHtml);
            }
            document.querySelector('table').style.display = 'none';
        } else {
            if (noResultsDiv) {
                noResultsDiv.remove();
            }
            document.querySelector('table').style.display = '';
        }
    }


// ADD these new functions to admin_logs.js:

// Enhanced timeframe handling
function handleTimeframeChange() {
    const timeframe = document.getElementById('timeframeSelect').value;
    const dateContainer = document.getElementById('dateRangeContainer');
    const quickButtons = document.getElementById('quickDateButtons');
    
    if (timeframe === 'custom') {
        dateContainer.style.display = 'flex';
        quickButtons.style.display = 'flex';
        
        // Set default dates if not already set
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        
        if (!startDate.value) {
            const today = new Date();
            const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
            startDate.value = weekAgo.toISOString().split('T')[0];
        }
        
        if (!endDate.value) {
            const today = new Date();
            endDate.value = today.toISOString().split('T')[0];
        }
        
        // Update max dates to current date
        updateMaxDates();
    } else {
        dateContainer.style.display = 'none';
        quickButtons.style.display = 'none';
        document.getElementById('filterForm').submit();
    }
}

// Update max date attributes based on current server date
function updateMaxDates() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('startDate').max = today;
    document.getElementById('endDate').max = today;
}

// Validate date range
function validateDateRange() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (startDate && endDate && startDate > endDate) {
        alert('Start date cannot be later than end date');
        document.getElementById('startDate').value = endDate;
        return false;
    }
    
    // Ensure dates are not in the future
    const today = new Date().toISOString().split('T')[0];
    if (startDate > today) {
        alert('Start date cannot be in the future');
        document.getElementById('startDate').value = today;
        return false;
    }
    
    if (endDate > today) {
        alert('End date cannot be in the future');
        document.getElementById('endDate').value = today;
        return false;
    }
    
    return true;
}

// Quick date range setters
function setQuickDate(range) {
    const today = new Date();
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    
    switch(range) {
        case 'thisMonth':
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            startDate.value = firstDay.toISOString().split('T')[0];
            endDate.value = today.toISOString().split('T')[0];
            break;
            
        case 'lastMonth':
            const lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
            startDate.value = lastMonthStart.toISOString().split('T')[0];
            endDate.value = lastMonthEnd.toISOString().split('T')[0];
            break;
            
        case 'last7days':
            const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
            startDate.value = weekAgo.toISOString().split('T')[0];
            endDate.value = today.toISOString().split('T')[0];
            break;
    }
    
    if (validateDateRange()) {
        document.getElementById('filterForm').submit();
    }
}

// UPDATE the existing DOMContentLoaded event listener to include:
document.addEventListener('DOMContentLoaded', function() {
    // ... existing code ...
    
    // Initialize date inputs
    updateMaxDates();
    
    // Set up date input change listeners
    document.getElementById('startDate').addEventListener('change', function() {
        const endDate = document.getElementById('endDate');
        if (!endDate.value || endDate.value < this.value) {
            endDate.value = this.value;
        }
    });
    
    document.getElementById('endDate').addEventListener('change', function() {
        const startDate = document.getElementById('startDate');
        if (!startDate.value || startDate.value > this.value) {
            startDate.value = this.value;
        }
    });
    
    // ... rest of existing code ...
});

// UPDATE the confirmExportLogs function to preserve current filters:
function confirmExportLogs() {
    const confirmBtn = event.target;
    const originalContent = confirmBtn.innerHTML;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating...';
    confirmBtn.disabled = true;
    
    // Close modal first
    closeExportLogsModal();
    
    // Start download with current filters
    setTimeout(() => {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('export', 'csv');
        
        // Create a temporary link to trigger download
        const link = document.createElement('a');
        link.href = currentUrl.toString();
        
        // Generate filename with filter info
        let filename = 'admin_logs_';
        const timeframe = currentUrl.searchParams.get('timeframe') || 'all';
        
        if (timeframe === 'today') {
            filename += 'today_';
        } else if (timeframe === 'custom') {
            const startDate = currentUrl.searchParams.get('start_date');
            const endDate = currentUrl.searchParams.get('end_date');
            if (startDate && endDate) {
                filename += startDate + '_to_' + endDate + '_';
            }
        } else {
            filename += timeframe + '_';
        }
        
        filename += new Date().toISOString().split('T')[0] + '.csv';
        
        link.download = filename;
        link.click();
        
        // Reset button
        confirmBtn.innerHTML = originalContent;
        confirmBtn.disabled = false;
    }, 500);
}