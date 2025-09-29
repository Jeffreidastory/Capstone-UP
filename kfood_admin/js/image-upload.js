// Image upload functionality
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('p_image');
    const fileInputWrapper = document.querySelector('.file-input-wrapper');
    const previewContainer = document.getElementById('image-preview-container');

    if (fileInput && fileInputWrapper) {
        // Handle drag and drop
        ['dragenter', 'dragover'].forEach(eventName => {
            fileInputWrapper.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileInputWrapper.addEventListener(eventName, unhighlight, false);
        });

        // Handle file drop
        fileInputWrapper.addEventListener('drop', handleDrop, false);

        // Handle file selection
        fileInput.addEventListener('change', function(e) {
            handleFiles(this.files);
        });
    }

    function highlight(e) {
        e.preventDefault();
        e.stopPropagation();
        fileInputWrapper.classList.add('dragover');
    }

    function unhighlight(e) {
        e.preventDefault();
        e.stopPropagation();
        fileInputWrapper.classList.remove('dragover');
    }

    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const dt = e.dataTransfer;
        const files = dt.files;

        handleFiles(files);
    }

    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            
            // Check if file is an image
            if (!file.type.match('image.*')) {
                showNotification('Error', 'Please upload an image file (JPG, JPEG, or PNG)', 'error');
                return;
            }

            // Check file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                showNotification('Error', 'Image size should be less than 5MB', 'error');
                return;
            }

            // Update the upload placeholder text
            const uploadText = document.querySelector('.upload-text .main-text');
            if (uploadText) {
                uploadText.textContent = file.name;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.innerHTML = `
                    <div class="image-preview">
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="remove-preview" onclick="removePreview()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        }
    }
});

// Function to remove preview
function removePreview() {
    const fileInput = document.getElementById('p_image');
    const previewContainer = document.getElementById('image-preview-container');
    const uploadText = document.querySelector('.upload-text .main-text');
    
    if (fileInput) fileInput.value = '';
    if (previewContainer) previewContainer.innerHTML = '';
    if (uploadText) uploadText.textContent = 'Insert Image Here';
}