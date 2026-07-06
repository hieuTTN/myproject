// js/cloudinary-upload.js

const CLOUDINARY_URL = 'https://api.cloudinary.com/v1_1/dwfciuqmd/image/upload';
const CLOUDINARY_UPLOAD_PRESET = 'ml_default'; 

/**
 * Hàm upload file ảnh lên Cloudinary
 * @param {File} file - Đối tượng file lấy từ input type="file"
 * @returns {Promise<string>} - Trả về link URL ảnh bảo mật (secure_url) nếu thành công
 */
async function uploadToCloudinaryServer(file) {
  if (!file) return null;

  const formData = new FormData();
  formData.append('file', file);
  formData.append('upload_preset', CLOUDINARY_UPLOAD_PRESET);

  try {
    const response = await fetch(CLOUDINARY_URL, {
      method: 'POST',
      body: formData
    });

    if (!response.ok) {
      throw new Error('Không thể tải ảnh lên Cloudinary');
    }

    const result = await response.json();
    return result.secure_url; // Link ảnh online dạng https
  } catch (error) {
    console.error("Lỗi module Cloudinary:", error);
    throw error;
  }
}