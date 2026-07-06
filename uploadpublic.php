<?php
// index.php
require_once 'admin/config/config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Ảnh lên Cloudinary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .upload-form {
            margin: 20px 0;
        }
        .file-input {
            display: block;
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 2px dashed #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .file-input:hover {
            border-color: #4CAF50;
        }
        .btn-upload {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-upload:hover {
            background: #45a049;
        }
        .btn-upload:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        .preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }
        .preview-item {
            position: relative;
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            cursor: pointer;
            font-size: 14px;
            line-height: 25px;
            text-align: center;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 4px;
            display: none;
        }
        .result.show {
            display: block;
        }
        .result .image-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        .result .image-grid img {
            max-width: 200px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .loading {
            display: none;
            color: #666;
            margin: 10px 0;
        }
        .loading.show {
            display: block;
        }
        .error-message {
            color: red;
            margin: 10px 0;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
            display: none;
        }
        .error-message.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Upload Ảnh lên Cloudinary</h1>
        
        <form id="uploadForm" class="upload-form" enctype="multipart/form-data">
            <input type="file" 
                   name="images[]" 
                   id="images" 
                   class="file-input" 
                   accept="image/*" 
                   multiple 
                   required>
            
            <div class="preview-container" id="previewContainer"></div>
            
            <button type="submit" class="btn-upload" id="uploadBtn">
                Upload Ảnh
            </button>
            
            <div class="loading" id="loadingSpinner">
                Đang upload... Vui lòng đợi
            </div>
            
            <div class="error-message" id="errorMessage"></div>
        </form>
        
        <div class="result" id="resultContainer">
            <h3>Upload thành công!</h3>
            <div class="image-grid" id="imageGrid"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('images');
            const previewContainer = document.getElementById('previewContainer');
            const uploadForm = document.getElementById('uploadForm');
            const uploadBtn = document.getElementById('uploadBtn');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const resultContainer = document.getElementById('resultContainer');
            const imageGrid = document.getElementById('imageGrid');
            const errorMessage = document.getElementById('errorMessage');

            // Preview ảnh
            fileInput.addEventListener('change', function(e) {
                previewContainer.innerHTML = '';
                const files = Array.from(this.files);
                
                if (files.length === 0) return;
                
                files.forEach((file, index) => {
                    if (!file.type.startsWith('image/')) return;
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = file.name;
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'remove-btn';
                        removeBtn.textContent = '×';
                        removeBtn.onclick = function() {
                            previewItem.remove();
                            // Xóa file khỏi input
                            const dt = new DataTransfer();
                            const remainingFiles = Array.from(fileInput.files)
                                .filter((_, i) => i !== index);
                            remainingFiles.forEach(f => dt.items.add(f));
                            fileInput.files = dt.files;
                        };
                        
                        previewItem.appendChild(img);
                        previewItem.appendChild(removeBtn);
                        previewContainer.appendChild(previewItem);
                    };
                    reader.readAsDataURL(file);
                });
            });

            // Upload form
            uploadForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const files = fileInput.files;
                
                if (files.length === 0) {
                    showError('Vui lòng chọn ít nhất 1 ảnh');
                    return;
                }

                // Hiển thị loading
                uploadBtn.disabled = true;
                loadingSpinner.classList.add('show');
                resultContainer.classList.remove('show');
                errorMessage.classList.remove('show');
                imageGrid.innerHTML = '';

                try {
                    const response = await fetch('/admin/upload.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        // Hiển thị kết quả
                        resultContainer.classList.add('show');
                        result.data.forEach(item => {
                            const img = document.createElement('img');
                            img.src = item.secure_url;
                            img.alt = item.public_id;
                            imageGrid.appendChild(img);
                        });
                        
                        // Reset form
                        fileInput.value = '';
                        previewContainer.innerHTML = '';
                    } else {
                        showError(result.message || 'Upload thất bại');
                    }
                } catch (error) {
                    console.log(error);
                    
                    showError('Có lỗi xảy ra: ' + error.message);
                } finally {
                    uploadBtn.disabled = false;
                    loadingSpinner.classList.remove('show');
                }
            });

            function showError(message) {
                errorMessage.textContent = message;
                errorMessage.classList.add('show');
                setTimeout(() => {
                    errorMessage.classList.remove('show');
                }, 5000);
            }
        });
    </script>
</body>
</html>