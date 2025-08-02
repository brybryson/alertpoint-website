// Global variables to store admin data
let currentEditingAdmin = null;
let currentArchivingAdmin = null;

// Edit admin function
function editAdmin(adminId) {
    console.log(`Opening edit modal for admin ID: ${adminId}`);
    
    try {
        // Find the admin card to get data
        const adminCard = document.querySelector(`[onclick*="editAdmin('${adminId}')"]`).closest('.admin-card');
        if (!adminCard) {
            showErrorMessage('Admin card not found');
            return;
        }
        
        // Extract admin data from the card
        const adminData = extractAdminDataFromCard(adminCard, adminId);
        if (!adminData) {
            showErrorMessage('Failed to extract admin data');
            return;
        }
        
        // Store current editing admin
        currentEditingAdmin = adminData;
        
        // Show the edit modal first
        const editModal = document.getElementById('editAdminModal');
        if (editModal) {
            editModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        } else {
            showErrorMessage('Edit modal not found');
            return;
        }
        
        // Populate the edit form with basic data
        populateEditFormBasic(adminData);
        
        // Fetch complete admin data including birthdate and proper image path
        fetchCompleteAdminData(adminData.adminId);
        
    } catch (error) {
        console.error('Error in editAdmin:', error);
        showErrorMessage('An error occurred while opening the edit form');
    }
}

// Extract admin data from card
function extractAdminDataFromCard(adminCard, adminId) {
    try {
        // Extract full name
        const fullNameElement = adminCard.querySelector('h3.font-semibold');
        const fullName = fullNameElement ? fullNameElement.textContent.trim() : '';
        
        // Extract position
        const positionElement = adminCard.querySelector('p.text-sm.text-gray-600');
        const position = positionElement ? positionElement.textContent.trim() : '';
        
        // Extract username (from the element with fas fa-user-shield icon)
        const usernameElement = adminCard.querySelector('.fas.fa-user-shield');
        let username = '';
        if (usernameElement && usernameElement.parentElement) {
            username = usernameElement.parentElement.textContent.trim();
        }
        
        // Extract profile image
        const profileImg = adminCard.querySelector('img');
        const profilePicture = profileImg ? profileImg.src : null;
        
        // Parse full name into parts (assuming format: "FirstName MiddleName LastName" or "FirstName LastName")
        const nameParts = fullName.split(' ').filter(part => part.length > 0);
        let firstName = '', middleName = '', lastName = '';
        
        if (nameParts.length >= 3) {
            firstName = nameParts[0];
            middleName = nameParts.slice(1, -1).join(' ');
            lastName = nameParts[nameParts.length - 1];
        } else if (nameParts.length === 2) {
            firstName = nameParts[0];
            lastName = nameParts[1];
        } else if (nameParts.length === 1) {
            firstName = nameParts[0];
        }
        
        return {
            adminId: adminId,
            firstName: firstName,
            middleName: middleName,
            lastName: lastName,
            position: position,
            username: username,
            profilePicture: profilePicture
        };
        
    } catch (error) {
        console.error('Error extracting admin data:', error);
        return null;
    }
}

// Populate edit form with basic admin data (non-editable fields)
function populateEditFormBasic(adminData) {
    try {
        // Clear any existing error messages
        clearErrorMessages();
        
        // Populate basic fields (make them read-only)
        const adminIdField = document.getElementById('edit_admin_id');
        const adminIdDisplayField = document.getElementById('edit_admin_id_display');
        const firstNameField = document.getElementById('edit_admin_fn');
        const middleNameField = document.getElementById('edit_admin_mn');
        const lastNameField = document.getElementById('edit_admin_ln');
        const usernameField = document.getElementById('edit_username');
        
        if (adminIdField) adminIdField.value = adminData.adminId;
        if (adminIdDisplayField) adminIdDisplayField.value = adminData.adminId;
        
        // Make name fields read-only and populate them
        if (firstNameField) {
            firstNameField.value = adminData.firstName;
            firstNameField.readOnly = true;
            firstNameField.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        }
        
        if (middleNameField) {
            middleNameField.value = adminData.middleName;
            middleNameField.readOnly = true;
            middleNameField.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        }
        
        if (lastNameField) {
            lastNameField.value = adminData.lastName;
            lastNameField.readOnly = true;
            lastNameField.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        }
        
        if (usernameField) usernameField.value = adminData.username;
        
        // Initialize birthdate dropdowns (will be populated later)
        initializeBirthdateDropdowns();
        
    } catch (error) {
        console.error('Error populating basic edit form:', error);
        showErrorMessage('Failed to populate form data');
    }
}

// Initialize birthdate dropdowns
function initializeBirthdateDropdowns() {
    const dayField = document.getElementById('edit_birth_day');
    const yearField = document.getElementById('edit_birth_year');
    
    // Populate day dropdown (1-31)
    if (dayField) {
        dayField.innerHTML = '<option value="">Day</option>';
        for (let i = 1; i <= 31; i++) {
            const day = i.toString().padStart(2, '0');
            dayField.innerHTML += `<option value="${day}">${i}</option>`;
        }
    }
    
    // Populate year dropdown (1900 to current year)
    if (yearField) {
        const currentYear = new Date().getFullYear();
        yearField.innerHTML = '<option value="">Year</option>';
        for (let i = currentYear; i >= 1900; i--) {
            yearField.innerHTML += `<option value="${i}">${i}</option>`;
        }
    }
}

// Fetch complete admin data from database
function fetchCompleteAdminData(adminId) {
    // Show loading state on form
    const form = document.getElementById('editAdminForm');
    if (form) {
        const loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'formLoadingOverlay';
        loadingOverlay.className = 'absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10';
        loadingOverlay.innerHTML = `
            <div class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                <span class="text-gray-600">Loading admin data...</span>
            </div>
        `;
        form.style.position = 'relative';
        form.appendChild(loadingOverlay);
    }
    
    fetch('/ALERTPOINT/javascript/USERS/get_admin_data.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ admin_id: adminId })
    })
    .then(response => {
        console.log('Response status:', response.status); // Debug log
        console.log('Response headers:', response.headers.get('content-type')); // Debug log
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text(); // Get as text first to see what we're receiving
    })
    .then(text => {
        console.log('Raw response:', text); // Debug log
        
        try {
            const data = JSON.parse(text);
            return data;
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
    })
    .then(data => {
        console.log('Parsed data:', data); // Debug log
        
        if (data.success && data.admin) {
            // Update current editing admin with complete data
            currentEditingAdmin = { ...currentEditingAdmin, ...data.admin };
            
            // Populate role field (editable)
            populateRoleField(data.admin.barangay_position);
            
            // Populate birthdate fields (read-only)
            populateBirthdateFields(data.admin.birthdate);
            
            // Handle profile picture display
            handleProfilePictureDisplay(data.admin.picture);
            
        } else {
            throw new Error(data.message || 'Failed to fetch admin data');
        }
    })
    .catch(error => {
        console.error('Error fetching admin data:', error);
        showErrorMessage('Failed to load complete admin information: ' + error.message);
    })
    .finally(() => {
        // Remove loading state
        const loadingOverlay = document.getElementById('formLoadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.remove();
        }
    });
}

// Populate role field
function populateRoleField(barangayPosition) {
    console.log('Populating role field with:', barangayPosition); // Debug log
    
    const roleField = document.getElementById('edit_role');
    const customRoleDiv = document.getElementById('editCustomRoleDiv');
    const customRoleField = document.getElementById('editCustomRole');
    
    if (roleField && barangayPosition) {
        // Clear any previous selections
        roleField.value = '';
        if (customRoleDiv) customRoleDiv.classList.add('hidden');
        if (customRoleField) customRoleField.value = '';
        
        // Check if the position is in the predefined options
        const options = roleField.querySelectorAll('option');
        let found = false;
        
        for (let option of options) {
            if (option.value === barangayPosition) {
                roleField.value = barangayPosition;
                found = true;
                console.log('Found matching option:', barangayPosition);
                break;
            }
        }
        
        // If not found in predefined options, set as custom
        if (!found) {
            console.log('Setting as custom role:', barangayPosition);
            roleField.value = 'other';
            if (customRoleField) {
                customRoleField.value = barangayPosition;
            }
            if (customRoleDiv) {
                customRoleDiv.classList.remove('hidden');
            }
        }
    }
}

// Populate birthdate fields (read-only)
// Populate birthdate fields (read-only)
function populateBirthdateFields(birthdateString) {
    console.log('Processing birthdate:', birthdateString); // Debug log
    
    try {
        if (!birthdateString || birthdateString === '0000-00-00' || birthdateString === '' || birthdateString === null) {
            console.log('No valid birthdate provided');
            return;
        }
        
        let month = '', day = '', year = '';
        
        // Handle format like "November 1, 2007"
        if (birthdateString.includes(',')) {
            const parts = birthdateString.split(',');
            if (parts.length === 2) {
                const monthDay = parts[0].trim().split(' ');
                if (monthDay.length === 2) {
                    const monthName = monthDay[0];
                    day = monthDay[1].padStart(2, '0');
                    year = parts[1].trim();
                    
                    // Convert month name to number
                    const monthMap = {
                        'January': '01', 'February': '02', 'March': '03', 'April': '04',
                        'May': '05', 'June': '06', 'July': '07', 'August': '08',
                        'September': '09', 'October': '10', 'November': '11', 'December': '12'
                    };
                    month = monthMap[monthName] || '';
                }
            }
        }
        // Handle format like "2024-01-02" or "2024-1-2"
        else if (birthdateString.includes('-')) {
            const parts = birthdateString.split('-');
            if (parts.length === 3) {
                year = parts[0];
                month = parts[1].padStart(2, '0');
                day = parts[2].padStart(2, '0');
            }
        }
        
        console.log('Parsed birthdate - Month:', month, 'Day:', day, 'Year:', year); // Debug log
        
        // Set the values and make them read-only
        const monthField = document.getElementById('edit_birth_month');
        const dayField = document.getElementById('edit_birth_day');
        const yearField = document.getElementById('edit_birth_year');
        
        if (monthField && month) {
            monthField.value = month;
            monthField.disabled = true;
            monthField.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        }
        
        if (dayField && day) {
            dayField.value = day;
            dayField.disabled = true;
            dayField.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        }
        
        if (yearField && year) {
            yearField.value = year;
            yearField.disabled = true;
            yearField.classList.add('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
        }
        
    } catch (error) {
        console.error('Error parsing birthdate:', error);
    }
}

// Handle profile picture display with proper path normalization
// Handle profile picture display with proper path normalization
function handleProfilePictureDisplay(profilePicture) {
    const preview = document.getElementById('editProfilePreview');
    const placeholder = document.getElementById('editUploadPlaceholder');
    const removeBtn = document.getElementById('editRemovePhotoBtn');
    
    console.log('Original picture path:', profilePicture); // Debug log
    
    if (profilePicture && profilePicture !== 'null' && profilePicture !== '' && profilePicture !== 'NULL') {
        let normalizedPath = profilePicture;
        
        // Normalize the path according to your requirements
        if (profilePicture.startsWith('../../')) {
            normalizedPath = profilePicture.replace('../../', '/ALERTPOINT/');
        } else if (!profilePicture.startsWith('/ALERTPOINT/') && !profilePicture.startsWith('http')) {
            normalizedPath = '/ALERTPOINT/' + profilePicture.replace(/^\/+/, '');
        }
        
        console.log('Normalized picture path:', normalizedPath); // Debug log
        
        if (preview) {
            preview.src = normalizedPath;
            preview.classList.remove('hidden');
            
            // Add error handler for image loading
            preview.onerror = function() {
                console.warn('Failed to load image:', normalizedPath);
                this.classList.add('hidden');
                if (placeholder) placeholder.classList.remove('hidden');
                if (removeBtn) removeBtn.classList.add('hidden');
            };
            
            // Add success handler for image loading
            preview.onload = function() {
                console.log('Image loaded successfully:', normalizedPath);
                if (placeholder) placeholder.classList.add('hidden');
                if (removeBtn) removeBtn.classList.remove('hidden');
            };
        }
        
        if (placeholder) {
            placeholder.classList.add('hidden');
        }
        
        if (removeBtn) {
            removeBtn.classList.remove('hidden');
        }
    } else {
        console.log('No profile picture or invalid path'); // Debug log
        if (preview) {
            preview.classList.add('hidden');
            preview.src = '';
        }
        if (placeholder) {
            placeholder.classList.remove('hidden');
        }
        if (removeBtn) {
            removeBtn.classList.add('hidden');
        }
    }
}

// Handle custom role selection
function handleCustomRoleSelection() {
    const roleField = document.getElementById('edit_role');
    const customRoleDiv = document.getElementById('editCustomRoleDiv');
    
    if (roleField && customRoleDiv) {
        if (roleField.value === 'other') {
            customRoleDiv.classList.remove('hidden');
        } else {
            customRoleDiv.classList.add('hidden');
        }
    }
}

// Photo upload functions
function editUploadPhoto() {
    const input = document.getElementById('editProfileImageInput');
    if (input) {
        input.click();
    }
}

function editRemovePhoto() {
    const preview = document.getElementById('editProfilePreview');
    const placeholder = document.getElementById('editUploadPlaceholder');
    const removeBtn = document.getElementById('editRemovePhotoBtn');
    const input = document.getElementById('editProfileImageInput');
    
    if (preview) preview.classList.add('hidden');
    if (placeholder) placeholder.classList.remove('hidden');
    if (removeBtn) removeBtn.classList.add('hidden');
    if (input) input.value = '';
}

// Close edit modal
function closeEditAdminModal() {
    const editModal = document.getElementById('editAdminModal');
    if (editModal) {
        editModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // Clear current editing admin
    currentEditingAdmin = null;
    
    // Clear form
    const form = document.getElementById('editAdminForm');
    if (form) {
        form.reset();
        
        // Remove read-only classes from name fields
        const nameFields = ['edit_admin_fn', 'edit_admin_mn', 'edit_admin_ln'];
        nameFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.readOnly = false;
                field.classList.remove('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
            }
        });
        
        // Remove disabled state from birthdate fields
        const birthdateFields = ['edit_birth_month', 'edit_birth_day', 'edit_birth_year'];
        birthdateFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.disabled = false;
                field.classList.remove('bg-gray-50', 'text-gray-600', 'cursor-not-allowed');
            }
        });
    }
    
    // Clear error messages
    clearErrorMessages();
}

// Archive admin function
function archiveAdmin(adminId) {
    console.log(`Opening archive confirmation for admin ID: ${adminId}`);
    
    try {
        // Find the admin card to get data
        const adminCard = document.querySelector(`[onclick*="archiveAdmin('${adminId}')"]`).closest('.admin-card');
        if (!adminCard) {
            showErrorMessage('Admin card not found');
            return;
        }
        
        // Extract admin data
        const adminData = extractAdminDataFromCard(adminCard, adminId);
        if (!adminData) {
            showErrorMessage('Failed to extract admin data');
            return;
        }
        
        // Store current archiving admin
        currentArchivingAdmin = adminData;
        
        // Populate archive confirmation details
        populateArchiveConfirmation(adminData);
        
        // Show archive confirmation modal
        const archiveModal = document.getElementById('archiveConfirmationModal');
        if (archiveModal) {
            archiveModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            showErrorMessage('Archive confirmation modal not found');
        }
        
    } catch (error) {
        console.error('Error in archiveAdmin:', error);
        showErrorMessage('An error occurred while opening archive confirmation');
    }
}

// Populate archive confirmation details
function populateArchiveConfirmation(adminData) {
    const detailsDiv = document.getElementById('archiveAdminDetails');
    if (detailsDiv) {
        detailsDiv.innerHTML = `
            <div class="text-sm">
                <p class="font-medium text-gray-900">${adminData.firstName} ${adminData.middleName} ${adminData.lastName}</p>
                <p class="text-gray-600">${adminData.position}</p>
                <p class="text-gray-500">ID: ${adminData.adminId}</p>
            </div>
        `;
    }
}

// Utility functions
function showErrorMessage(message) {
    // Create or update error notification
    let errorNotification = document.getElementById('errorNotification');
    
    if (!errorNotification) {
        errorNotification = document.createElement('div');
        errorNotification.id = 'errorNotification';
        errorNotification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 max-w-md';
        document.body.appendChild(errorNotification);
    }
    
    errorNotification.innerHTML = `
        <div class="flex items-center space-x-2">
            <i class="fas fa-exclamation-triangle"></i>
            <span>${message}</span>
            <button onclick="closeErrorNotification()" class="ml-2 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    errorNotification.classList.remove('hidden');
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (errorNotification) {
            errorNotification.classList.add('hidden');
        }
    }, 5000);
    
    console.error('Error:', message);
}

function closeErrorNotification() {
    const errorNotification = document.getElementById('errorNotification');
    if (errorNotification) {
        errorNotification.classList.add('hidden');
    }
}

function clearErrorMessages() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.classList.add('hidden');
        element.textContent = '';
    });
    
    // Clear field error states
    const fields = document.querySelectorAll('.border-red-500');
    fields.forEach(field => {
        field.classList.remove('border-red-500');
    });
}

// Event listeners for modals
document.addEventListener('DOMContentLoaded', function() {
    // Edit form submission
    const editForm = document.getElementById('editAdminForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditFormSubmission);
    }
    
    // Role selection change
    const roleField = document.getElementById('edit_role');
    if (roleField) {
        roleField.addEventListener('change', handleCustomRoleSelection);
    }
    
    // Profile image input change
    const profileInput = document.getElementById('editProfileImageInput');
    if (profileInput) {
        profileInput.addEventListener('change', handleProfileImageChange);
    }
    
    // Archive confirmation buttons
    const cancelArchiveBtn = document.getElementById('cancelArchiveBtn');
    const confirmArchiveBtn = document.getElementById('confirmArchiveBtn');
    
    if (cancelArchiveBtn) {
        cancelArchiveBtn.addEventListener('click', closeArchiveModal);
    }
    
    if (confirmArchiveBtn) {
        confirmArchiveBtn.addEventListener('click', confirmArchiveAdmin);
    }
    
    // Edit confirmation buttons
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const confirmEditBtn = document.getElementById('confirmEditBtn');
    
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', closeEditConfirmationModal);
    }
    
    if (confirmEditBtn) {
        confirmEditBtn.addEventListener('click', confirmEditAdmin);
    }
    
    // Success modal buttons
    const editSuccessBtn = document.getElementById('editSuccessOkBtn');
    const archiveSuccessBtn = document.getElementById('archiveSuccessOkBtn');
    
    if (editSuccessBtn) {
        editSuccessBtn.addEventListener('click', closeEditSuccessModal);
    }
    
    if (archiveSuccessBtn) {
        archiveSuccessBtn.addEventListener('click', closeArchiveSuccessModal);
    }
});

// Handle profile image change
function handleProfileImageChange(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('editProfilePreview');
    const placeholder = document.getElementById('editUploadPlaceholder');
    const removeBtn = document.getElementById('editRemovePhotoBtn');
    const errorDiv = document.getElementById('editPhotoError');
    
    // Clear previous errors
    if (errorDiv) {
        errorDiv.classList.add('hidden');
        errorDiv.textContent = '';
    }
    
    if (file) {
        // Validate file type
        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            if (errorDiv) {
                errorDiv.textContent = 'Only PNG, JPG, and JPEG files are allowed';
                errorDiv.classList.remove('hidden');
            }
            event.target.value = '';
            return;
        }
        
        // Validate file size (5MB max)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (file.size > maxSize) {
            if (errorDiv) {
                errorDiv.textContent = 'File size must be less than 5MB';
                errorDiv.classList.remove('hidden');
            }
            event.target.value = '';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            if (preview) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            }
            if (placeholder) {
                placeholder.classList.add('hidden');
            }
            if (removeBtn) {
                removeBtn.classList.remove('hidden');
            }
        };
        reader.readAsDataURL(file);
    }
}

// Handle edit form submission
function handleEditFormSubmission(event) {
    event.preventDefault();
    
    try {
        // Validate form
        if (!validateEditForm()) {
            return;
        }
        
        // Show confirmation modal
        showEditConfirmation();
        
    } catch (error) {
        console.error('Error handling form submission:', error);
        showErrorMessage('An error occurred while processing the form');
    }
}

// Validate edit form
function validateEditForm() {
    let isValid = true;
    
    // Clear previous errors
    clearErrorMessages();
    
    // Validate required fields (only role is editable and required)
    const roleField = document.getElementById('edit_role');
    if (roleField && !roleField.value) {
        showFieldError('edit_role', 'Position/Role is required');
        isValid = false;
    }
    
    // Validate custom role if selected
    if (roleField && roleField.value === 'other') {
        const customRoleField = document.getElementById('editCustomRole');
        if (customRoleField && !customRoleField.value.trim()) {
            showFieldError('editCustomRole', 'Please specify the position');
            isValid = false;
        }
    }
    
    return isValid;
}

// Show field error
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (field) {
        const errorDiv = field.parentElement.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('hidden');
        }
        field.classList.add('border-red-500');
    }
}

// Show edit confirmation
function showEditConfirmation() {
    const confirmationModal = document.getElementById('editConfirmationModal');
    const contentDiv = document.getElementById('editConfirmationContent');
    
    if (confirmationModal && contentDiv && currentEditingAdmin) {
        // Populate confirmation content
        const roleField = document.getElementById('edit_role');
        const customRoleField = document.getElementById('editCustomRole');
        const imageInput = document.getElementById('editProfileImageInput');
        
        let roleText = roleField ? roleField.value : '';
        if (roleText === 'other' && customRoleField) {
            roleText = customRoleField.value;
        }
        
        const hasNewImage = imageInput && imageInput.files.length > 0;
        
        contentDiv.innerHTML = `
            <div class="space-y-2 text-sm">
                <p><span class="font-medium">Admin:</span> ${currentEditingAdmin.firstName} ${currentEditingAdmin.middleName} ${currentEditingAdmin.lastName}</p>
                <p><span class="font-medium">ID:</span> ${currentEditingAdmin.adminId}</p>
                <p><span class="font-medium">New Position:</span> ${roleText}</p>
                <p><span class="font-medium">Profile Picture:</span> ${hasNewImage ? 'Will be updated' : 'No changes'}</p>
            </div>
        `;
        
        confirmationModal.classList.remove('hidden');
    }
}

// Close edit confirmation modal
function closeEditConfirmationModal() {
    const confirmationModal = document.getElementById('editConfirmationModal');
    if (confirmationModal) {
        confirmationModal.classList.add('hidden');
    }
}

// Confirm edit admin
function confirmEditAdmin() {
    // Close confirmation modal
    closeEditConfirmationModal();
    
    // Prepare form data
    const formData = new FormData();
    formData.append('admin_id', currentEditingAdmin.adminId);
    
    const roleField = document.getElementById('edit_role');
    const customRoleField = document.getElementById('editCustomRole');
    const imageInput = document.getElementById('editProfileImageInput');
    
    // Add role data
    if (roleField) {
        if (roleField.value === 'other' && customRoleField) {
            formData.append('barangay_position', customRoleField.value);
        } else {
            formData.append('barangay_position', roleField.value);
        }
    }
    
    // Add image data if new image selected
    if (imageInput && imageInput.files.length > 0) {
        formData.append('profile_image', imageInput.files[0]);
    }
    
    // Show loading state
    const editModal = document.getElementById('editAdminModal');
    if (editModal) {
        const loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'updateLoadingOverlay';
        loadingOverlay.className = 'absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-20';
        loadingOverlay.innerHTML = `
            <div class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                <span class="text-gray-600">Updating admin...</span>
            </div>
        `;
        editModal.style.position = 'relative';
        editModal.appendChild(loadingOverlay);
    }
    
    fetch('/ALERTPOINT/javascript/USERS/update_admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Update response status:', response.status); // Debug log
        console.log('Update response headers:', response.headers.get('content-type')); // Debug log
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text(); // Get as text first
    })
    .then(text => {
        console.log('Update raw response:', text); // Debug log
        
        try {
            const data = JSON.parse(text);
            return data;
        } catch (e) {
            console.error('Update JSON parse error:', e);
            console.error('Update response text:', text);
            throw new Error('Invalid JSON response from server');
        }
    })
    .then(data => {
        console.log('Update parsed data:', data); // Debug log
        
        if (data.success) {
            // Close edit modal
            closeEditAdminModal();
            
            // Show success modal
            showEditSuccessModal(data.message || 'Admin updated successfully');
            
            // Refresh the page or update the admin card after a delay
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            throw new Error(data.message || 'Failed to update admin');
        }
    })
    .catch(error => {
        console.error('Error updating admin:', error);
        showErrorMessage('Failed to update admin: ' + error.message);
    })
    .finally(() => {
        // Remove loading state
        const loadingOverlay = document.getElementById('updateLoadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.remove();
        }
    });
}

// Show edit success modal
function showEditSuccessModal(message) {
    const successModal = document.getElementById('editSuccessModal');
    const messageElement = document.getElementById('editSuccessMessage');
    
    if (successModal) {
        if (messageElement) {
            messageElement.textContent = message;
        }
        successModal.classList.remove('hidden');
    }
}

// Close edit success modal
function closeEditSuccessModal() {
    const successModal = document.getElementById('editSuccessModal');
    if (successModal) {
        successModal.classList.add('hidden');
    }
}

// Close archive modal
function closeArchiveModal() {
    const archiveModal = document.getElementById('archiveConfirmationModal');
    if (archiveModal) {
        archiveModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    currentArchivingAdmin = null;
}

// Confirm archive admin
function confirmArchiveAdmin() {
    if (!currentArchivingAdmin) {
        showErrorMessage('No admin selected for archiving');
        return;
    }
    
    // Close confirmation modal
    closeArchiveModal();
    
    // Show loading state on the admin card
    const adminCard = document.querySelector(`[onclick*="archiveAdmin('${currentArchivingAdmin.adminId}')"]`).closest('.admin-card');
    if (adminCard) {
        adminCard.style.opacity = '0.5';
        adminCard.style.pointerEvents = 'none';
    }
    
    // Send archive request
    fetch('/ALERTPOINT/javascript/USERS/archive_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ admin_id: currentArchivingAdmin.adminId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Remove the admin card with animation
            if (adminCard) {
                adminCard.style.transform = 'scale(0)';
                adminCard.style.opacity = '0';
                setTimeout(() => {
                    adminCard.remove();
                }, 300);
            }
            
            // Show success modal
            showArchiveSuccessModal(data.message || 'Admin archived successfully');
            
        } else {
            throw new Error(data.message || 'Failed to archive admin');
        }
    })
    .catch(error => {
        console.error('Error archiving admin:', error);
        showErrorMessage('Failed to archive admin: ' + error.message);
        
        // Restore admin card state
        if (adminCard) {
            adminCard.style.opacity = '1';
            adminCard.style.pointerEvents = 'auto';
        }
    });
}

// Show archive success modal
function showArchiveSuccessModal(message) {
    const successModal = document.getElementById('archiveSuccessModal');
    const messageElement = document.getElementById('archiveSuccessMessage');
    
    if (successModal) {
        if (messageElement) {
            messageElement.textContent = message;
        }
        successModal.classList.remove('hidden');
    }
}

// Close archive success modal
function closeArchiveSuccessModal() {
    const successModal = document.getElementById('archiveSuccessModal');
    if (successModal) {
        successModal.classList.add('hidden');
    }
}