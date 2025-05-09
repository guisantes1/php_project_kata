const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const form = document.getElementById('dataForm');

// Click en la zona de drop activa el input de archivo
dropzone.addEventListener('click', () => fileInput.click());

// Cambio de estilo al arrastrar archivos
dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.style.backgroundColor = 'rgba(0,255,255,0.2)';
});

dropzone.addEventListener('dragleave', () => {
    dropzone.style.backgroundColor = 'transparent';
});

// Manejo de archivos soltados
dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.style.backgroundColor = 'transparent';

    const dt = new DataTransfer();
    for (const file of e.dataTransfer.files) {
        if (file.name.endsWith('.txt')) {
            dt.items.add(file);
            dropzone.innerText = '📄 File ready: ' + file.name;
        } else {
            dropzone.innerText = '❌ Only .txt files are accepted';
            return;
        }
    }
    fileInput.files = dt.files;
    setTimeout(() => form.submit(), 500);
});

// Si se elige un archivo manualmente
fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) {
        dropzone.innerText = '📄 File ready: ' + fileInput.files[0].name;
        setTimeout(() => form.submit(), 500);
    }
});

// Limpia resultados y reinicia el formulario
function resetPage() {
    const resultBlock = document.getElementById('result-block');
    if (resultBlock) resultBlock.remove();

    window.scrollTo({ top: 0, behavior: 'smooth' });
    document.getElementById('manual_data').value = '';
    dropzone.innerText = 'Drag and drop your .txt file here or click to upload';
    fileInput.value = '';
}

// Envía el formulario oculto de descarga
function openDownload() {
    const form = document.getElementById('downloadForm');
    form.submit();
}

// Desplaza la vista hacia los resultados tras procesar
setTimeout(() => {
    const resultBlock = document.getElementById('result-block');
    if (resultBlock) {
        resultBlock.scrollIntoView({ behavior: 'smooth' });
    }
}, 300);
