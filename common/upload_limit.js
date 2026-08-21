const sizeLimit = 1024 * 1024 * 1;

const fileInput = document.getElementById('upimg');

const handleFileSelect = () => {
  const files = fileInput.files;
  for (let i = 0; i < files.length; i++) {
    if (files[i].size > sizeLimit) {
      alert('Please upload the file under 1MB.'); 
      fileInput.value = '';
      return; // 
    }
  }
}

fileInput.addEventListener('change', handleFileSelect);
