// Global variables
let selectedImageFile = null;
let imagePreviewUrl = null;

// Initialize birthdate selectors
function initializeBirthdateSelectors() {
    const daySelect = document.getElementById('birth_day');
    const yearSelect = document.getElementById('birth_year');

    // Clear previous options (if any)
    daySelect.innerHTML = '<option value="">Day</option>';
    yearSelect.innerHTML = '<option value="">Year</option>';

    // Populate days (1–31)
    for (let i = 1; i <= 31; i++) {
        const option = document.createElement('option');
        option.value = i.toString().padStart(2, '0');
        option.textContent = i;
        daySelect.appendChild(option);
    }

    // Populate years from 1945 up to the current year (inclusive)
    const currentYear = new Date().getFullYear();
    for (let i = currentYear; i >= 1945; i--) {
        const option = document.createElement('option');
        option.value = i;
        option.textContent = i;
        yearSelect.appendChild(option);
    }
}

// Auto-generate username
function generateUsername() {
    const firstName = document.getElementById('admin_fn').value.trim();
    if (firstName) {
        // Clean the first name: get first word, convert to lowercase, remove special characters
        const cleanFirstName = firstName.split(' ')[0].toLowerCase().replace(/[^a-z0-9]/g, '');
        const username = `admin_${cleanFirstName}`;
        document.getElementById('username').value = username;
    } else {
        document.getElementById('username').value = '';
    }
}

// Photo upload functions
function uploadPhoto() {
    document.getElementById('profileImageInput').click();
}

function removePhoto() {
    selectedImageFile = null;
    imagePreviewUrl = null;
    document.getElementById('profilePreview').classList.add('hidden');
    document.getElementById('uploadPlaceholder').classList.remove('hidden');
    document.getElementById('removePhotoBtn').classList.add('hidden');
    document.getElementById('profileImageInput').value = '';
    clearPhotoError();
}

function clearPhotoError() {
    const errorDiv = document.getElementById('photoError');
    errorDiv.classList.add('hidden');
    errorDiv.textContent = '';
}

function showPhotoError(message) {
    const errorDiv = document.getElementById('photoError');
    errorDiv.textContent = message;
    errorDiv.classList.remove('hidden');
}

// Modal functions
function openAddAdminModal() {
    document.getElementById('addAdminModal').classList.remove('hidden');
    document.getElementById('admin_fn').focus();
}

function closeAddAdminModal() {
    document.getElementById('addAdminModal').classList.add('hidden');
    resetForm();
}

function resetForm() {
    document.getElementById('addAdminForm').reset();
    document.getElementById('customRoleDiv').classList.add('hidden');
    document.getElementById('username').value = '';
    removePhoto();
    clearAllErrors();
}

function confirmClose() {
    const form = document.getElementById('addAdminForm');
    const hasData = Array.from(form.elements).some(element =>
        element.type !== 'submit' && element.type !== 'button' && element.value.trim() !== ''
    ) || selectedImageFile;

    if (hasData) {
        showConfirmation(
            'warning',
            'Discard Changes?',
            'All unsaved changes will be lost.',
            () => closeAddAdminModal(),
            'Discard',
            'bg-red-500 hover:bg-red-600 text-white'
        );
    } else {
        closeAddAdminModal();
    }
}

function showConfirmation(type, title, message, onConfirm, confirmText = 'Confirm', confirmClass = 'bg-blue-500 hover:bg-blue-600 text-white') {
    const modal = document.getElementById('confirmationModal');
    const icon = document.getElementById('confirmIcon');
    const iconClass = document.getElementById('confirmIconClass');
    const titleEl = document.getElementById('confirmTitle');
    const messageEl = document.getElementById('confirmMessage');
    const confirmBtn = document.getElementById('confirmAction');
    const cancelBtn = document.getElementById('confirmCancel');

    if (type === 'warning') {
        icon.className = 'w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center bg-yellow-100';
        iconClass.className = 'fas fa-exclamation-triangle text-xl text-yellow-500';
    } else if (type === 'success') {
        icon.className = 'w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center bg-green-100';
        iconClass.className = 'fas fa-check-circle text-xl text-green-500';
    } else if (type === 'question') {
        icon.className = 'w-12 h-12 mx-auto mb-4 rounded-full flex items-center justify-center bg-blue-100';
        iconClass.className = 'fas fa-question-circle text-xl text-blue-500';
    }

    titleEl.textContent = title;
    messageEl.textContent = message;
    confirmBtn.textContent = confirmText;
    confirmBtn.className = `px-4 py-2 text-sm rounded-md transition-colors ${confirmClass}`;

    confirmBtn.onclick = () => {
        modal.classList.add('hidden');
        onConfirm();
    };

    cancelBtn.onclick = () => {
        modal.classList.add('hidden');
    };

    modal.classList.remove('hidden');
}

// Password toggle
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('passwordToggle');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'fas fa-eye';
    }
}

// Form validation functions
function validateField(fieldId, validationFn, errorMessage) {
    const field = document.getElementById(fieldId);
    const errorDiv = field.parentElement.querySelector('.error-message');

    if (!validationFn(field.value)) {
        field.classList.add('border-red-500', 'animate-shake');
        if (errorDiv) {
            errorDiv.textContent = errorMessage;
            errorDiv.classList.remove('hidden');
        }
        setTimeout(() => field.classList.remove('animate-shake'), 300);
        return false;
    } else {
        field.classList.remove('border-red-500');
        if (errorDiv) {
            errorDiv.classList.add('hidden');
        }
        return true;
    }
}

function validateBirthdate() {
    const month = document.getElementById('birth_month').value;
    const day = document.getElementById('birth_day').value;
    const year = document.getElementById('birth_year').value;
    const errorDiv = document.getElementById('birthdateError');

    if (!month || !day || !year) {
        errorDiv.textContent = 'Please select a complete birthdate';
        errorDiv.classList.remove('hidden');
       
        // Add red border to empty fields
        if (!month) document.getElementById('birth_month').classList.add('border-red-500');
        if (!day) document.getElementById('birth_day').classList.add('border-red-500');
        if (!year) document.getElementById('birth_year').classList.add('border-red-500');
       
        return false;
    } else {
        errorDiv.classList.add('hidden');
        document.getElementById('birth_month').classList.remove('border-red-500');
        document.getElementById('birth_day').classList.remove('border-red-500');
        document.getElementById('birth_year').classList.remove('border-red-500');
        return true;
    }
}

// Handle role selection
document.getElementById('role').addEventListener('change', function () {
    const customRoleDiv = document.getElementById('customRoleDiv');
    const customRoleInput = document.getElementById('customRole');

    if (this.value === 'other') {
        customRoleDiv.classList.remove('hidden');
        customRoleInput.required = true;
    } else {
        customRoleDiv.classList.add('hidden');
        customRoleInput.required = false;
        customRoleInput.value = '';
    }
});

// Form submit handler
document.getElementById('addAdminForm').addEventListener('submit', function (e) {
    e.preventDefault();
    console.log('Form submitted!');

    if (!validateForm()) {
        console.log('Form validation failed');
        return;
    }

    const formData = new FormData(this);
   
    const data = {
        admin_fn: formData.get('admin_fn'),
        admin_mn: formData.get('admin_mn') || '',
        admin_ln: formData.get('admin_ln'),
        birth_month: formData.get('birth_month'),
        birth_day: formData.get('birth_day'),
        birth_year: formData.get('birth_year'),
        role: formData.get('role') === 'other' ? formData.get('customRole') : formData.get('role'),
        username: formData.get('username'),
        password: formData.get('password'),
        photo: imagePreviewUrl // This should now contain the base64 data or null
    };

    console.log('Calling showAdminDetailsConfirmation with:', data);
    console.log('Image preview URL:', imagePreviewUrl);
    showAdminDetailsConfirmation(data);
});

function clearAllErrors() {
    document.querySelectorAll('.error-message').forEach(error => {
        error.classList.add('hidden');
    });
    document.querySelectorAll('input, select').forEach(field => {
        field.classList.remove('border-red-500');
    });
    clearPhotoError();
}

function validateForm() {
    let isValid = true;

    // Validate first name
    if (!validateField('admin_fn', val => val.trim().length >= 2, 'First name required (2+ chars)')) {
        isValid = false;
    }

    // Validate last name
    if (!validateField('admin_ln', val => val.trim().length >= 2, 'Last name required (2+ chars)')) {
        isValid = false;
    }

    // Validate birthdate
    if (!validateBirthdate()) {
        isValid = false;
    }

    // Validate role
    const roleSelect = document.getElementById('role');
    if (!roleSelect.value) {
        const errorDiv = roleSelect.parentElement.querySelector('.error-message');
        roleSelect.classList.add('border-red-500');
        if (errorDiv) {
            errorDiv.textContent = 'Please select a position';
            errorDiv.classList.remove('hidden');
        }
        isValid = false;
    } else {
        roleSelect.classList.remove('border-red-500');
        const errorDiv = roleSelect.parentElement.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.classList.add('hidden');
        }
    }

    // Validate custom role if selected
    if (roleSelect.value === 'other') {
        if (!validateField('customRole', val => val.trim().length >= 2, 'Please specify position')) {
            isValid = false;
        }
    }

    // Validate password
    if (!validateField('password',
        val => val.length >= 8 && /[a-zA-Z]/.test(val) && /[0-9]/.test(val),
        'Min 8 chars with letters & numbers')) {
        isValid = false;
    }

    return isValid;
}

// Function to show admin details confirmation modal
function showAdminDetailsConfirmation(data) {
    const modal = document.getElementById('adminDetailsModal');
    const detailsContent = document.getElementById('adminDetailsContent');
   
    // Format the details
    const middleName = data.admin_mn ? ` ${data.admin_mn}` : '';
    const photoStatus = data.photo ? 'Uploaded' : 'No photo';
    const birthdate = formatBirthdate(data.birth_month, data.birth_day, data.birth_year);
   
    detailsContent.innerHTML = `
        <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center">
                <span class="flex items-center space-x-2">
                    <i class="fas fa-user text-gray-400 w-4"></i>
                    <span class="font-medium">Full Name:</span>
                </span>
                <span class="text-gray-800">${data.admin_fn}${middleName} ${data.admin_ln}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="flex items-center space-x-2">
                    <i class="fas fa-calendar text-gray-400 w-4"></i>
                    <span class="font-medium">Birthdate:</span>
                </span>
                <span class="text-gray-800">${birthdate}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="flex items-center space-x-2">
                    <i class="fas fa-briefcase text-gray-400 w-4"></i>
                    <span class="font-medium">Position:</span>
                </span>
                <span class="text-gray-800">${data.role}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="flex items-center space-x-2">
                    <i class="fas fa-at text-gray-400 w-4"></i>
                    <span class="font-medium">Username:</span>
                </span>
                <span class="text-gray-800">${data.username}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="flex items-center space-x-2">
                    <i class="fas fa-camera text-gray-400 w-4"></i>
                    <span class="font-medium">Profile Photo:</span>
                </span>
                <span class="text-gray-800">${photoStatus}</span>
            </div>
        </div>
    `;

    // Show the modal
    modal.classList.remove('hidden');

    // Handle Cancel button
    document.getElementById('cancelDetailsBtn').onclick = () => {
        modal.classList.add('hidden');
        // User returns to the form
    };

    // Handle Confirm button
    document.getElementById('confirmDetailsBtn').onclick = async () => {
        modal.classList.add('hidden');
        // Show loading indicator
        showLoadingIndicator();
        // Proceed to create admin and show success
        try {
            await createAdminAccount(data);
        } catch (error) {
            hideLoadingIndicator();
            showErrorModal('Error creating admin account: ' + error.message);
        }
    };
}

// Function to show loading indicator
function showLoadingIndicator() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.classList.remove('hidden');
}

// Function to hide loading indicator
function hideLoadingIndicator() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.classList.add('hidden');
}

// Function to create admin account and show success
async function createAdminAccount(data) {
    try {
        console.log('Sending data to server:', data);
        
        // Check if we have image data
        if (data.photo) {
            console.log('Image data length:', data.photo.length);
            console.log('Image data type:', data.photo.substring(0, 50));
        }
        
        // Fixed the fetch URL to be relative to the current page
        const response = await fetch('../javascript/USERS/add_admin.php', {            
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });

        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Server error response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}. Response: ${errorText}`);
        }

        const result = await response.json();
        console.log('Server response:', result);

        if (result.success) {
            // Hide loading indicator
            hideLoadingIndicator();
            
            // Show success modal
            showSuccessModal(result.data);
        } else {
            throw new Error(result.message || 'Unknown error occurred');
        }
        
    } catch (error) {
        console.error('Error creating admin account:', error);
        hideLoadingIndicator();
        throw error;
    }
}

// Function to show success modal
function showSuccessModal(data) {
    const modal = document.getElementById('successModal');
    const message = document.getElementById('successMessage');
    message.textContent = `Admin account for ${data.first_name} ${data.last_name} has been created successfully with ID: ${data.admin_id}`;
    modal.classList.remove('hidden');

    // Handle OK button
    document.getElementById('successOkBtn').onclick = () => {
        modal.classList.add('hidden');
        closeAddAdminModal(); // Close the entire form
        
        // Force reload the page
        window.location.reload(true);
    };
}


// Function to show error modal
function showErrorModal(errorMessage) {
    showConfirmation(
        'warning',
        'Error',
        errorMessage,
        () => {}, // No action on confirm
        'OK',
        'bg-red-500 hover:bg-red-600 text-white'
    );
}

// Helper function to format birthdate
function formatBirthdate(month, day, year) {
    const monthNames = {
        '01': 'January', '02': 'February', '03': 'March',
        '04': 'April', '05': 'May', '06': 'June',
        '07': 'July', '08': 'August', '09': 'September',
        '10': 'October', '11': 'November', '12': 'December'
    };
    
    const monthName = monthNames[month] || 'Unknown';
    return `${monthName} ${parseInt(day)}, ${year}`;
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    initializeBirthdateSelectors();
});

// Add event listener for first name input to generate username
document.getElementById('admin_fn').addEventListener('input', generateUsername);
document.getElementById('admin_fn').addEventListener('blur', generateUsername);

// Handle file input change for photo upload - FIXED VERSION
document.getElementById('profileImageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    console.log('File selected:', file.name, 'Size:', file.size, 'Type:', file.type);

    // Validate file type
    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    if (!allowedTypes.includes(file.type)) {
        showPhotoError('Only PNG, JPG, and JPEG files are allowed.');
        this.value = '';
        return;
    }

    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        showPhotoError('File size must be less than 5MB.');
        this.value = '';
        return;
    }

    clearPhotoError();
    selectedImageFile = file;

    // Create preview and convert to base64
    const reader = new FileReader();
    reader.onload = function(e) {
        const result = e.target.result;
        console.log('FileReader result type:', typeof result);
        console.log('FileReader result length:', result.length);
        console.log('FileReader result preview:', result.substring(0, 100));
        
        // Store the base64 data URL
        imagePreviewUrl = result;
        
        const preview = document.getElementById('profilePreview');
        const placeholder = document.getElementById('uploadPlaceholder');
        const removeBtn = document.getElementById('removePhotoBtn');
       
        preview.src = imagePreviewUrl;
        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
        removeBtn.classList.remove('hidden');
        
        console.log('Image preview updated successfully');
    };
    
    reader.onerror = function(error) {
        console.error('FileReader error:', error);
        showPhotoError('Error reading the image file.');
    };
    
    // Read as data URL (base64)
    reader.readAsDataURL(file);
});

// Handle profile image container click
document.querySelector('.profile-image-container').addEventListener('click', function() {
    uploadPhoto();
});

// Close modals on outside click
window.addEventListener('click', function (e) {
    const addAdminModal = document.getElementById('addAdminModal');
    const confirmModal = document.getElementById('confirmationModal');
    const detailsModal = document.getElementById('adminDetailsModal');
    const successModal = document.getElementById('successModal');

    if (e.target === addAdminModal) confirmClose();
    if (e.target === confirmModal) confirmModal.classList.add('hidden');
    if (e.target === detailsModal) detailsModal.classList.add('hidden');
    if (e.target === successModal) {
        successModal.classList.add('hidden');
        closeAddAdminModal();
    }
});

// Make functions globally available
window.openAddAdminModal = openAddAdminModal;
window.confirmClose = confirmClose;
window.togglePassword = togglePassword;
window.uploadPhoto = uploadPhoto;
window.removePhoto = removePhoto;