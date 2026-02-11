// Script para actualizar el nombre del archivo seleccionado
function updateFileName(input) {
    const label = input.parentElement.querySelector('.file-input-label');
    const fileNameSpan = label.querySelector('.file-name');
    
    if (input.files && input.files.length > 0) {
        const fileName = input.files[0].name;
        const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2);
        fileNameSpan.textContent = `${fileName} (${fileSize} MB)`;
        label.classList.add('has-file');
    } else {
        fileNameSpan.textContent = 'Seleccionar archivo PDF...';
        label.classList.remove('has-file');
    }
}

// Hacer disponible globalmente
if (typeof window !== 'undefined') {
    window.updateFileName = updateFileName;
}
