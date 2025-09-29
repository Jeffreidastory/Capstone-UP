// Update file input label when a file is selected
document.getElementById('p_image')?.addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name || 'No file chosen';
    document.getElementById('file-label').innerHTML = `
        <i class="fas fa-file-image"></i> ${fileName}
    `;
});

// Show image preview before upload
function readURL(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.className = 'image-preview';
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview">
                <button type="button" class="remove-preview">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            const existingPreview = input.parentElement.querySelector('.image-preview');
            if (existingPreview) {
                existingPreview.remove();
            }
            
            input.parentElement.appendChild(preview);
            
            preview.querySelector('.remove-preview').onclick = function() {
                preview.remove();
                input.value = '';
                document.getElementById('file-label').innerHTML = `
                    <i class="fas fa-cloud-upload-alt"></i> Choose an image
                `;
            };
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

document.getElementById('p_image')?.addEventListener('change', function() {
    readURL(this);
});

// Add animation to table rows
document.querySelectorAll('.display-product-table tr').forEach(row => {
    row.style.opacity = '0';
    row.style.transform = 'translateY(10px)';
    
    setTimeout(() => {
        row.style.transition = 'all 0.3s ease';
        row.style.opacity = '1';
        row.style.transform = 'translateY(0)';
    }, 100);
});