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

        // Filter users function
        function filterUsers(type) {
            console.log(`Filtering by: ${type}`);

            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
        const activeTab = document.getElementById(`filter-${type}`);
            if (activeTab) {
                activeTab.classList.add('active');
            }
            
            console.log(`Filtering by: ${type}`);
        }

        // Search users function
        function searchUsers() {
            console.log(`Searching for: ${searchTerm}`);

            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            console.log(`Searching for: ${searchTerm}`);
        }

        // Modal functions
        function openAddAdminModal() {
            const modal = document.getElementById('addAdminModal');
            if (modal) {
                modal.classList.remove('hidden');
            }
        }

        function closeAddAdminModal() {
            const modal = document.getElementById('addAdminModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Admin management functions
        function editAdmin(adminId) {
            alert(`Edit admin functionality for ID: ${adminId}`);
        }

        function removeAdmin(adminId) {
            if (confirm('Are you sure you want to remove this admin?')) {
                alert(`Remove admin functionality for ID: ${adminId}`);
            }
        }

        // Auto-generate username
        document.addEventListener('DOMContentLoaded', function() {
            const firstNameInput = document.getElementById('admin_fn');
            const usernameInput = document.getElementById('username');
            
            if (firstNameInput && usernameInput) {
                firstNameInput.addEventListener('input', function() {
                    const firstName = this.value.toLowerCase().replace(/\s+/g, '');
                        usernameInput.value = firstName ? `admin_${firstName}` : '';
                });
            }
            
            // Start time updates
            updateTime();
            setInterval(updateTime, 1000);
        });

        // Form submission
        document.getElementById('addAdminForm')?.addEventListener('submit', function(e) {
            // Let the form submit normally to PHP
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.classList.remove('hidden');
            }
        });