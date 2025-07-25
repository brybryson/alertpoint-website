//  // Global variables
//         let selectedImageFile = null;
//         let imagePreviewUrl = null;

//         // Initialize birthdate selectors
//         function initializeBirthdateSelectors() {
//             const daySelect = document.getElementById('birth_day');
//             const yearSelect = document.getElementById('birth_year');

//             // Clear previous options (if any)
//             daySelect.innerHTML = '<option value="">Day</option>';
//             yearSelect.innerHTML = '<option value="">Year</option>';

//             // Populate days (1–31)
//             for (let i = 1; i <= 31; i++) {
//                 const option = document.createElement('option');
//                 option.value = i.toString().padStart(2, '0');
//                 option.textContent = i;
//                 daySelect.appendChild(option);
//             }

//             // Populate years from 1945 up to the current year (inclusive)
//             const currentYear = new Date().getFullYear();
//             for (let i = currentYear; i >= 1945; i--) {
//                 const option = document.createElement('option');
//                 option.value = i;
//                 option.textContent = i;
//                 yearSelect.appendChild(option);
//             }
//         }


//         // Auto-generate username
//         function generateUsername() {
//             const firstName = document.getElementById('admin_fn').value.trim();
//             if (firstName) {
//                 // Clean the first name: get first word, convert to lowercase, remove special characters
//                 const cleanFirstName = firstName.split(' ')[0].toLowerCase().replace(/[^a-z0-9]/g, '');
//                 const username = `admin_${cleanFirstName}`;
//                 document.getElementById('username').value = username;
//             } else {
//                 document.getElementById('username').value = '';
//             }
//         }

//         // Photo upload functions
//         function uploadPhoto() {
//             document.getElementById('profileImageInput').click();
//         }

//         function removePhoto() {
//             selectedImageFile = null;
//             imagePreviewUrl = null;
//             document.getElementById('profilePreview').classList.add('hidden');
//             document.getElementById('uploadPlaceholder').classList.remove('hidden');
//             document.getElementById('removePhotoBtn').classList.add('hidden');
//             document.getElementById('profileImageInput').value = '';
//             clearPhotoError();
//         }

//         function clearPhotoError() {
//             const errorDiv = document.getElementById('photoError');
//             errorDiv.classList.add('hidden');
//             errorDiv.textContent = '';
//         }

//         function showPhotoError(message) {
//             const errorDiv = document.getElementById('photoError');
//             errorDiv.textContent = message;
//             errorDiv.classList.remove('hidden');
//         }

//         // Modal functions
//         function openAddAdminModal() {
//             document.getElementById('addAdminModal').classList.remove('hidden');
//             document.getElementById('admin_fn').focus();
//         }

//         function closeAddAdminModal() {
//             document.getElementById('addAdminModal').classList.add('hidden');
//             resetForm();
//         }

//         function resetForm() {
//             document.getElementById('addAdminForm').reset();
//             document.getElementById('customRoleDiv').classList.add('hidden');
//             document.getElementById('username').value = '';
//             removePhoto();
//             clearAllErrors();
//         }

//         function confirmClose() {
//             const form = document.getElementById('addAdminForm');
//             const hasData = Array.from(form.elements).some(element =>
//                 element.type !== 'submit' && element.type !== 'button' && element.value.trim() !== ''
//             ) || selectedImageFile;

//             if (hasData) {
//                 showConfirmation(
//                     'warning',
//                     'Discard Changes?',
//                     'All unsaved changes will be lost.',
//                     () => closeAddAdminModal(),
//                     'Discard',
//                     'bg-red-500 hover:bg-red-600 text-white'
//                 );
//             } else {
//                 closeAddAdminModal();
//             }
//         }

//         function showConfirmation(type, title, message, onConfirm, confirmText = 'Confirm', confirmClass = 'bg-blue-500 hover:bg-blue-600 text-white') {
//             const modal = document.getElementById('confirmationModal');
//             const icon = document.getElementById('confirmIcon');
//             const iconClass = document.getElementById('confirmIconClass');
//             const titleEl = document.getElementById('confirmTitle');
//             const messageEl = document.getElementById('confirmMessage');
//             const confirmBtn = document.getElementById('confirmAction');
//             const cancelBtn = document.getElementById('confirmCancel');

//             if (type === 'warning') {
//                 icon.className = 'w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center bg-yellow-100';
//                 iconClass.className = 'fas fa-exclamation-triangle text-xl text-yellow-500';
//             } else if (type === 'success') {
//                 icon.className = 'w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center bg-green-100';
//                 iconClass.className = 'fas fa-check-circle text-xl text-green-500';
//             } else if (type === 'question') {
//                 icon.className = 'w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center bg-blue-100';
//                 iconClass.className = 'fas fa-question-circle text-xl text-blue-500';
//             }

//             titleEl.textContent = title;
//             messageEl.textContent = message;
//             confirmBtn.textContent = confirmText;
//             confirmBtn.className = `px-4 py-2 text-sm rounded-md transition-colors ${confirmClass}`;

//             confirmBtn.onclick = () => {
//                 modal.classList.add('hidden');
//                 onConfirm();
//             };

//             cancelBtn.onclick = () => {
//                 modal.classList.add('hidden');
//             };

//             modal.classList.remove('hidden');
//         }

    




//         function showDetailsConfirmation(data) {
//             showAdminDetailsConfirmation(data);
//         }

//         // Password toggle
//         function togglePassword() {
//             const passwordInput = document.getElementById('password');
//             const toggleIcon = document.getElementById('passwordToggle');

//             if (passwordInput.type === 'password') {
//                 passwordInput.type = 'text';
//                 toggleIcon.className = 'fas fa-eye-slash';
//             } else {
//                 passwordInput.type = 'password';
//                 toggleIcon.className = 'fas fa-eye';
//             }
//         }

//         // Form validation functions
//        function validateField(fieldId, validationFn, errorMessage) {
//             const field = document.getElementById(fieldId);
//             const errorDiv = field.parentElement.querySelector('.error-message');

//             if (!validationFn(field.value)) {
//                 field.classList.add('border-red-500', 'animate-shake');
//                 if (errorDiv) {
//                     errorDiv.textContent = errorMessage;
//                     errorDiv.classList.remove('hidden');
//                 }
//                 setTimeout(() => field.classList.remove('animate-shake'), 300);
//                 return false;
//             } else {
//                 field.classList.remove('border-red-500');
//                 if (errorDiv) {
//                     errorDiv.classList.add('hidden');
//                 }
//                 return true;
//             }
//         }

//         function validateBirthdate() {
//             const month = document.getElementById('birth_month').value;
//             const day = document.getElementById('birth_day').value;
//             const year = document.getElementById('birth_year').value;
//             const errorDiv = document.getElementById('birthdateError');

//             if (!month || !day || !year) {
//                 errorDiv.textContent = 'Please select a complete birthdate';
//                 errorDiv.classList.remove('hidden');
                
//                 // Add red border to empty fields
//                 if (!month) document.getElementById('birth_month').classList.add('border-red-500');
//                 if (!day) document.getElementById('birth_day').classList.add('border-red-500');
//                 if (!year) document.getElementById('birth_year').classList.add('border-red-500');
                
//                 return false;
//             } else {
//                 errorDiv.classList.add('hidden');
//                 document.getElementById('birth_month').classList.remove('border-red-500');
//                 document.getElementById('birth_day').classList.remove('border-red-500');
//                 document.getElementById('birth_year').classList.remove('border-red-500');
//                 return true;
//             }
//         }

//         // Handle role selection
//         document.getElementById('role').addEventListener('change', function () {
//             const customRoleDiv = document.getElementById('customRoleDiv');
//             const customRoleInput = document.getElementById('customRole');

//             if (this.value === 'other') {
//                 customRoleDiv.classList.remove('hidden');
//                 customRoleInput.required = true;
//             } else {
//                 customRoleDiv.classList.add('hidden');
//                 customRoleInput.required = false;
//                 customRoleInput.value = '';
//             }
//         });

//         // ADD THIS NEW FORM SUBMIT HANDLER HERE:
//         document.getElementById('addAdminForm').addEventListener('submit', function (e) {
//             e.preventDefault();
//             console.log('Form submitted!'); // Debug log

//             if (!validateForm()) {
//                 console.log('Form validation failed');
//                 return;
//             }

//             const formData = new FormData(this);
            
//             // Format birthdate
//             const month = formData.get('birth_month');
//             const day = formData.get('birth_day');
//             const year = formData.get('birth_year');
//             const birthdate = `${year}-${month}-${day}`;

//             const data = {
//                 firstName: formData.get('admin_fn'),
//                 middleName: formData.get('admin_mn') || '',
//                 lastName: formData.get('admin_ln'),
//                 birthdate: birthdate,
//                 role: formData.get('role') === 'other' ? formData.get('customRole') : formData.get('role'),
//                 username: formData.get('username'),
//                 password: formData.get('password'),
//                 photo: selectedImageFile ? imagePreviewUrl : null
//             };

//             console.log('Calling showAdminDetailsConfirmation with:', data); // Debug log
//             showAdminDetailsConfirmation(data);
//         });

//         function clearAllErrors() {
//             document.querySelectorAll('.error-message').forEach(error => {
//                 error.classList.add('hidden');
//             });
//             document.querySelectorAll('input, select').forEach(field => {
//                 field.classList.remove('border-red-500');
//             });
//             clearPhotoError();
//         }

//         function validateForm() {
//             let isValid = true;

//             // Validate first name
//             if (!validateField('admin_fn', val => val.trim().length >= 2, 'First name required (2+ chars)')) {
//                 isValid = false;
//             }

//             // Validate last name
//             if (!validateField('admin_ln', val => val.trim().length >= 2, 'Last name required (2+ chars)')) {
//                 isValid = false;
//             }

//             // Validate birthdate
//             if (!validateBirthdate()) {
//                 isValid = false;
//             }

//             // Validate role
//             const roleSelect = document.getElementById('role');
//             if (!roleSelect.value) {
//                 const errorDiv = roleSelect.parentElement.querySelector('.error-message');
//                 roleSelect.classList.add('border-red-500');
//                 if (errorDiv) {
//                     errorDiv.textContent = 'Please select a position';
//                     errorDiv.classList.remove('hidden');
//                 }
//                 isValid = false;
//             } else {
//                 roleSelect.classList.remove('border-red-500');
//                 const errorDiv = roleSelect.parentElement.querySelector('.error-message');
//                 if (errorDiv) {
//                     errorDiv.classList.add('hidden');
//                 }
//             }

//             // Validate custom role if selected
//             if (roleSelect.value === 'other') {
//                 if (!validateField('customRole', val => val.trim().length >= 2, 'Please specify position')) {
//                     isValid = false;
//                 }
//             }

//             // Validate password
//             if (!validateField('password',
//                 val => val.length >= 8 && /[a-zA-Z]/.test(val) && /[0-9]/.test(val),
//                 'Min 8 chars with letters & numbers')) {
//                 isValid = false;
//             }

//             return isValid;
//         }

//         function submitFormAndShowSuccess(data) {
//             console.log('Form submitted with data:', data);
            
//             // Here you would normally send data to your backend
//             // For now, we'll simulate processing and show success
            
//             // Show success confirmation
//             showConfirmation(
//                 'success',
//                 'Admin Account Created Successfully!',
//                 `Admin account for ${data.firstName} ${data.lastName} has been created and is ready to use.`,
//                 () => closeAddAdminModal(),
//                 'OK',
//                 'bg-green-500 hover:bg-green-600 text-white'
//             );
//         }

//                 // Function to show admin details confirmation modal
// function showAdminDetailsConfirmation(data) {
//     const modal = document.getElementById('adminDetailsModal');
//     const detailsContent = document.getElementById('adminDetailsContent');
    
//     // Format the details
//     const middleName = data.middleName ? ` ${data.middleName}` : '';
//     const photoStatus = data.photo ? 'Uploaded' : 'No photo';
//     const birthdate = formatBirthdate(data.birthdate);
    
//     detailsContent.innerHTML = `
//         <div class="space-y-3 text-sm">
//             <div class="flex justify-between items-center">
//                 <span class="flex items-center space-x-2">
//                     <i class="fas fa-user text-gray-400 w-4"></i>
//                     <span class="font-medium">Full Name:</span>
//                 </span>
//                 <span class="text-gray-800">${data.firstName}${middleName} ${data.lastName}</span>
//             </div>
//             <div class="flex justify-between items-center">
//                 <span class="flex items-center space-x-2">
//                     <i class="fas fa-calendar text-gray-400 w-4"></i>
//                     <span class="font-medium">Birthdate:</span>
//                 </span>
//                 <span class="text-gray-800">${birthdate}</span>
//             </div>
//             <div class="flex justify-between items-center">
//                 <span class="flex items-center space-x-2">
//                     <i class="fas fa-briefcase text-gray-400 w-4"></i>
//                     <span class="font-medium">Position:</span>
//                 </span>
//                 <span class="text-gray-800">${data.role}</span>
//             </div>
//             <div class="flex justify-between items-center">
//                 <span class="flex items-center space-x-2">
//                     <i class="fas fa-at text-gray-400 w-4"></i>
//                     <span class="font-medium">Username:</span>
//                 </span>
//                 <span class="text-gray-800">${data.username}</span>
//             </div>
//             <div class="flex justify-between items-center">
//                 <span class="flex items-center space-x-2">
//                     <i class="fas fa-camera text-gray-400 w-4"></i>
//                     <span class="font-medium">Profile Photo:</span>
//                 </span>
//                 <span class="text-gray-800">${photoStatus}</span>
//             </div>
//         </div>
//     `;

//     // Show the modal
//     modal.classList.remove('hidden');

//     // Handle Cancel button
//     document.getElementById('cancelDetailsBtn').onclick = () => {
//         modal.classList.add('hidden');
//         // User returns to the form
//     };

//     // Handle Confirm button
//     document.getElementById('confirmDetailsBtn').onclick = () => {
//         modal.classList.add('hidden');
//         // Proceed to create admin and show success
//         createAdminAccount(data);
//     };
// }

// // Function to create admin account and show success
// function createAdminAccount(data) {
//     console.log('Creating admin account with data:', data);
    
//     // Here you would send the data to your backend
//     // For now, we'll simulate the process and show success
    
//     // Show success modal
//     showSuccessModal(data);
// }

// // Function to show success modal
// function showSuccessModal(data) {
//     const modal = document.getElementById('successModal');
//     const message = document.getElementById('successMessage');
    
//     message.textContent = `Admin account for ${data.firstName} ${data.lastName} has been created successfully and is ready to use.`;
    
//     modal.classList.remove('hidden');

//     // Handle OK button
//     document.getElementById('successOkBtn').onclick = () => {
//         modal.classList.add('hidden');
//         closeAddAdminModal(); // Close the entire form
//     };
// }

// // Helper function to format birthdate
// function formatBirthdate(birthdate) {
//     const date = new Date(birthdate);
//     const options = { year: 'numeric', month: 'long', day: 'numeric' };
//     return date.toLocaleDateString('en-US', options);
// }

//         // Event Listeners
//         document.addEventListener('DOMContentLoaded', function() {
//             initializeBirthdateSelectors();
//         });

//         // Add event listener for first name input to generate username
//         document.getElementById('admin_fn').addEventListener('input', generateUsername);
//         document.getElementById('admin_fn').addEventListener('blur', generateUsername);

//         // Handle file input change for photo upload
//         document.getElementById('profileImageInput').addEventListener('change', function(e) {
//             const file = e.target.files[0];
//             if (!file) return;

//             // Validate file type
//             const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
//             if (!allowedTypes.includes(file.type)) {
//                 showPhotoError('Only PNG, JPG, and JPEG files are allowed.');
//                 this.value = '';
//                 return;
//             }

//             // Validate file size (5MB max)
//             if (file.size > 5 * 1024 * 1024) {
//                 showPhotoError('File size must be less than 5MB.');
//                 this.value = '';
//                 return;
//             }

//             clearPhotoError();
//             selectedImageFile = file;

//             // Create preview
//             const reader = new FileReader();
//             reader.onload = function(e) {
//                 imagePreviewUrl = e.target.result;
//                 const preview = document.getElementById('profilePreview');
//                 const placeholder = document.getElementById('uploadPlaceholder');
//                 const removeBtn = document.getElementById('removePhotoBtn');
                
//                 preview.src = imagePreviewUrl;
//                 preview.classList.remove('hidden');
//                 placeholder.classList.add('hidden');
//                 removeBtn.classList.remove('hidden');
//             };
//             reader.readAsDataURL(file);
//         });

//         // Handle profile image container click
//         document.querySelector('.profile-image-container').addEventListener('click', function() {
//             uploadPhoto();
//         });

//         // Handle role selection
//         document.getElementById('role').addEventListener('change', function () {
//             const customRoleDiv = document.getElementById('customRoleDiv');
//             const customRoleInput = document.getElementById('customRole');

//             if (this.value === 'other') {
//                 customRoleDiv.classList.remove('hidden');
//                 customRoleInput.required = true;
//             } else {
//                 customRoleDiv.classList.add('hidden');
//                 customRoleInput.required = false;
//                 customRoleInput.value = '';
//             }
//         });

//         // Handle form submit
       

//         // Close modal on outside click
//         window.addEventListener('click', function (e) {
//             const modal = document.getElementById('addAdminModal');
//             const confirmModal = document.getElementById('confirmationModal');

//             if (e.target === modal) confirmClose();
//             if (e.target === confirmModal) confirmModal.classList.add('hidden');
//         });

//         // Make functions globally available
//         window.openAddAdminModal = openAddAdminModal;
//         window.confirmClose = confirmClose;
//         window.togglePassword = togglePassword;
//         window.uploadPhoto = uploadPhoto;
//         window.removePhoto = removePhoto;



// // Close modals on outside click
// window.addEventListener('click', function (e) {
//     const addAdminModal = document.getElementById('addAdminModal');
//     const confirmModal = document.getElementById('confirmationModal');
//     const detailsModal = document.getElementById('adminDetailsModal');
//     const successModal = document.getElementById('successModal');

//     if (e.target === addAdminModal) confirmClose();
//     if (e.target === confirmModal) confirmModal.classList.add('hidden');
//     if (e.target === detailsModal) detailsModal.classList.add('hidden');
//     if (e.target === successModal) {
//         successModal.classList.add('hidden');
//         closeAddAdminModal();
//     }
// });
 
 
 
 
 
 
 
 
 
 
 
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

        //     // Time display
        // function updateTime() {
        //     const now = new Date();
        //     const timeString = now.toLocaleString('en-PH', {
        //         timeZone: 'Asia/Manila',
        //         weekday: 'long',
        //         year: 'numeric',
        //         month: 'long',
        //         day: '2-digit',
        //         hour: '2-digit',
        //         minute: '2-digit',
        //         second: '2-digit'
        //     });
        //     document.getElementById('current-time').textContent = timeString;
        // }

        // setInterval(updateTime, 1000);
        // updateTime();
        
        
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
            archivedGrid.innerHTML = archivedUsers.length > 0 
                ? archivedUsers.map(createUserCard).join('') 
                : '<div class="col-span-full text-center py-12 text-gray-500"><i class="fas fa-archive text-4xl mb-4"></i><p>No archived users</p></div>';
        }

        function filterUsers(filter) {
            currentFilter = filter;
            
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
                tab.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
            });
            
            const activeTab = document.getElementById(`filter-${filter}`);
            activeTab.classList.add('active');
            activeTab.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
            
            renderUsers();
        }

        function searchUsers() {
            renderUsers();
        }

        function toggleArchivedSection() {
            const archivedSection = document.getElementById('archived-section');
            const toggleBtn = document.getElementById('archived-toggle');
            
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
            document.getElementById('addAdminModal').classList.remove('hidden');
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
            updateTime();
            updateStats();
            renderUsers();
            setInterval(updateTime, 1000);
            
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