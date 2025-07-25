// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyC4pz2_IBYGkAbIqLFqwyNsrbv-MOCxH3s",
    authDomain: "alertpointprojectver1.firebaseapp.com",
    projectId: "alertpointprojectver1",
    storageBucket: "alertpointprojectver1.firebasestorage.app",
    messagingSenderId: "1067658987404",
    appId: "1:1067658987404:web:856330c149f42c245c38a9"
};

// Initialize Firebase (using global firebase object from CDN)
firebase.initializeApp(firebaseConfig);
const db = firebase.firestore();
const storage = firebase.storage();
const auth = firebase.auth();

// Global variables
let selectedImageFile = null;
let imagePreviewUrl = null;

// Function to generate next admin ID
async function generateNextAdminId() {
    try {
        const adminsRef = db.collection('admins');
        const querySnapshot = await adminsRef.orderBy('account_created', 'desc').limit(1).get();
        
        if (querySnapshot.empty) {
            return 'ADM0001';
        }
        
        const lastAdmin = querySnapshot.docs[0];
        const lastId = lastAdmin.id;
        const numericPart = parseInt(lastId.replace('ADM', ''));
        const nextNum = numericPart + 1;
        return `ADM${nextNum.toString().padStart(4, '0')}`;
    } catch (error) {
        console.error('Error generating admin ID:', error);
        throw error;
    }
}

// Function to upload image to Firebase Storage
async function uploadImageToStorage(file, adminId) {
    try {
        // Create a reference to the file location
        const storageRef = storage.ref().child(`admin_profiles/${adminId}`);
        
        // Upload the file using put() method with proper metadata
        const metadata = {
            contentType: file.type,
            customMetadata: {
                'uploaded_by': 'admin_system',
                'upload_time': new Date().toISOString()
            }
        };
        
        const uploadTask = storageRef.put(file, metadata);
        
        // Wait for upload to complete
        const snapshot = await uploadTask;
        
        // Get download URL
        const downloadURL = await snapshot.ref.getDownloadURL();
        
        console.log('Image uploaded successfully:', downloadURL);
        return downloadURL;
        
    } catch (error) {
        console.error('Error uploading image:', error);
        
        // If it's a CORS error, provide alternative handling
        if (error.code === 'storage/unauthorized' || error.message.includes('CORS')) {
            console.warn('CORS issue detected. Using base64 fallback.');
            // Return the base64 data URL as fallback
            return imagePreviewUrl;
        }
        
        throw error;
    }
}

// Function to hash password (simple implementation - use bcrypt in production)
function hashPassword(password) {
    // This is a simple hash - in production use bcrypt or similar
    return btoa(password + 'salt_string');
}

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

function showDetailsConfirmation(data) {
    showAdminDetailsConfirmation(data);
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
   
    // Format birthdate
    const month = formData.get('birth_month');
    const day = formData.get('birth_day');
    const year = formData.get('birth_year');
    const birthdate = `${year}-${month}-${day}`;

    const data = {
        firstName: formData.get('admin_fn'),
        middleName: formData.get('admin_mn') || '',
        lastName: formData.get('admin_ln'),
        birthdate: birthdate,
        role: formData.get('role') === 'other' ? formData.get('customRole') : formData.get('role'),
        username: formData.get('username'),
        password: formData.get('password'),
        photo: selectedImageFile ? imagePreviewUrl : null
    };

    console.log('Calling showAdminDetailsConfirmation with:', data);
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
    const middleName = data.middleName ? ` ${data.middleName}` : '';
    const photoStatus = data.photo ? 'Uploaded' : 'No photo';
    const birthdate = formatBirthdate(data.birthdate);
   
    detailsContent.innerHTML = `
        <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center">
                <span class="flex items-center space-x-2">
                    <i class="fas fa-user text-gray-400 w-4"></i>
                    <span class="font-medium">Full Name:</span>
                </span>
                <span class="text-gray-800">${data.firstName}${middleName} ${data.lastName}</span>
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
    const confirmBtn = document.getElementById('confirmDetailsBtn');
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';
    confirmBtn.disabled = true;
}

// Function to hide loading indicator
function hideLoadingIndicator() {
    const confirmBtn = document.getElementById('confirmDetailsBtn');
    confirmBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Confirm';
    confirmBtn.disabled = false;
}

// Function to create admin account and show success
async function createAdminAccount(data) {
    try {
        // Generate admin ID
        const adminId = await generateNextAdminId();
        
        // Upload image to storage if exists
        let pictureUrl = '';
        if (selectedImageFile) {
            pictureUrl = await uploadImageToStorage(selectedImageFile, adminId);
        }

        // Hash password
        const hashedPassword = hashPassword(data.password);

        // Prepare admin data for Firestore
        const adminData = {
            first_name: data.firstName,
            middle_name: data.middleName,
            last_name: data.lastName,
            barangay_position: data.role,
            birthdate: data.birthdate,
            username: data.username,
            password: hashedPassword,
            picture: pictureUrl,
            account_status: 'active',
            user_status: 'offline',
            account_created: firebase.firestore.Timestamp.now(),
            last_active: firebase.firestore.Timestamp.now()
        };

        // Save to Firestore 'admins' collection
        await db.collection('admins').doc(adminId).set(adminData);

        // Also save to user_management structure if needed
        await db.collection('user_management').doc('admin_group').set({
            created: firebase.firestore.Timestamp.now()
        }, { merge: true });

        await db.collection('user_management').doc('admin_group').collection('admins').doc(adminId).set({
            admin_id: adminId,
            created: firebase.firestore.Timestamp.now()
        });

        console.log('Admin account created successfully with ID:', adminId);
        
        // Hide loading indicator
        hideLoadingIndicator();
        
        // Show success modal
        showSuccessModal(data);
        
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
   
    message.textContent = `Admin account for ${data.firstName} ${data.lastName} has been created successfully and is ready to use.`;
   
    modal.classList.remove('hidden');

    // Handle OK button
    document.getElementById('successOkBtn').onclick = () => {
        modal.classList.add('hidden');
        closeAddAdminModal(); // Close the entire form
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
function formatBirthdate(birthdate) {
    const date = new Date(birthdate);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    initializeBirthdateSelectors();
});

// Add event listener for first name input to generate username
document.getElementById('admin_fn').addEventListener('input', generateUsername);
document.getElementById('admin_fn').addEventListener('blur', generateUsername);

// Handle file input change for photo upload
document.getElementById('profileImageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

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

    // Create preview
    const reader = new FileReader();
    reader.onload = function(e) {
        imagePreviewUrl = e.target.result;
        const preview = document.getElementById('profilePreview');
        const placeholder = document.getElementById('uploadPlaceholder');
        const removeBtn = document.getElementById('removePhotoBtn');
       
        preview.src = imagePreviewUrl;
        preview.classList.remove('hidden');
        placeholder.classList.add('hidden');
        removeBtn.classList.remove('hidden');
    };
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