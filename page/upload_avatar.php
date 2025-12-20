<?php
require '../_base.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$info = '';
$error = '';

// 1. Upload after cropping
if (is_post() && isset($_POST['cropped_image'])) {
    $imageData = $_POST['cropped_image'];
    
    // Remove the prefix "data:image"
    $imageData = str_replace('data:image/png;base64,', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $imageData = base64_decode($imageData);
    
    if ($imageData) {
        $new_name = $user_id . '_' . time() . '.jpg';
        $path = "../images/avatars/" . $new_name;
        
        if (file_put_contents($path, $imageData)) {
            // Delete old avatar (keeping default)
            $current = $_SESSION['Profile_Photo'] ?? '';
            if ($current && !preg_match('/^default\d\.jpg$/', $current)) {
                @unlink("../images/avatars/" . $current);
            }
            
            $stm = $_db->prepare("UPDATE user SET Profile_Photo = ? WHERE userID = ?");
            $stm->execute([$new_name, $user_id]);
            
            $_SESSION['Profile_Photo'] = $new_name;
            temp('info', 'Avatar updated successfully!');
            redirect('profile.php');
            exit();
        }
    }
}

// 2. Select Default Avatar
if (is_post() && isset($_POST['default_avatar'])) {
    $chosen = $_POST['default_avatar'];
    
    $current = $_SESSION['Profile_Photo'] ?? '';
    if ($current && !preg_match('/^default\d\.jpg$/', $current)) {
        @unlink("../images/avatars/" . $current);
    }
    
    $stm = $_db->prepare("UPDATE user SET Profile_Photo = ? WHERE userID = ?");
    $stm->execute([$chosen, $user_id]);
    $_SESSION['Profile_Photo'] = $chosen;
    
    temp('info', 'Avatar changed successfully!');
    redirect('profile.php');
    exit();
}

$current_avatar = $_SESSION['Profile_Photo'] ?? 'default1.jpg';

$_title = 'Change Avatar - Nº9 Perfume';
include '../_head.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<style>
    .avatar-upload-container {
        max-width: 900px;
        margin: 2rem auto;
        padding: 2rem;
    }

    .avatar-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .avatar-header h2 {
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #1a1a1a;
    }

    .avatar-header p {
        color: #666;
        font-size: 1rem;
    }

    .current-avatar-section {
        text-align: center;
        margin-bottom: 3rem;
        padding: 2rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .current-avatar-section img {
        width: 180px;
        height: 180px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #fff;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        margin-bottom: 1rem;
    }

    .current-avatar-section p {
        color: #666;
        font-size: 0.95rem;
        margin: 0;
    }

    .upload-section {
        background: white;
        border-radius: 16px;
        padding: 2.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    }

    .upload-section h3 {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        color: #333;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .file-upload-area {
        border: 3px dashed #ddd;
        border-radius: 12px;
        padding: 3rem 2rem;
        text-align: center;
        transition: all 0.3s;
        background: #fafafa;
        cursor: pointer;
    }

    .file-upload-area:hover {
        border-color: #000;
        background: #f5f5f5;
    }

    .file-upload-area.dragover {
        border-color: #000;
        background: #f0f0f0;
    }

    .file-upload-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #999;
    }

    .file-upload-area p {
        color: #666;
        margin: 0.5rem 0;
    }

    .file-upload-area input[type="file"] {
        display: none;
    }

    .upload-btn {
        display: inline-block;
        padding: 0.75rem 2rem;
        background: #000;
        color: white;
        border-radius: 8px;
        font-weight: 500;
        margin-top: 1rem;
        cursor: pointer;
        transition: all 0.3s;
    }

    .upload-btn:hover {
        background: #333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    /* Image Editor */
    .crop-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 9999;
        animation: fadeIn 0.3s;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .crop-modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .crop-container {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        max-width: 800px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .crop-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .crop-header h3 {
        margin: 0;
        font-size: 1.5rem;
        color: #333;
    }

    .close-crop {
        background: #f5f5f5;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.5rem;
        transition: all 0.3s;
    }

    .close-crop:hover {
        background: #e0e0e0;
        transform: rotate(90deg);
    }

    .crop-image-container {
        max-height: 400px;
        margin-bottom: 1.5rem;
        background: #000;
        border-radius: 8px;
        overflow: hidden;
    }

    .crop-controls {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .crop-btn {
        padding: 0.75rem 1rem;
        background: #f5f5f5;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .crop-btn:hover {
        background: #e0e0e0;
        transform: translateY(-2px);
    }

    .crop-actions {
        display: flex;
        gap: 1rem;
    }

    .crop-actions button {
        flex: 1;
        padding: 1rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .cancel-btn {
        background: #f5f5f5;
        color: #666;
    }

    .cancel-btn:hover {
        background: #e0e0e0;
    }

    .save-btn {
        background: #000;
        color: white;
    }

    .save-btn:hover {
        background: #333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    /* Default Avatar Selection */
    .default-avatars {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    .avatar-option {
        position: relative;
        cursor: pointer;
        transition: all 0.3s;
    }

    .avatar-option input[type="radio"] {
        display: none;
    }

    .avatar-option img {
        width: 100%;
        aspect-ratio: 1;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid transparent;
        transition: all 0.3s;
    }

    .avatar-option:hover img {
        transform: scale(1.05);
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }

    .avatar-option input[type="radio"]:checked + img {
        border-color: #000;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    }

    .avatar-option input[type="radio"]:checked + img::after {
        content: "✓";
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #000;
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .submit-default-btn {
        margin-top: 2rem;
        width: 100%;
        padding: 1rem;
        background: #000;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .submit-default-btn:hover {
        background: #333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    .back-link {
        display: inline-block;
        margin-top: 2rem;
        color: #666;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
    }

    .back-link:hover {
        color: #000;
        transform: translateX(-5px);
    }

    .divider {
        height: 2px;
        background: linear-gradient(to right, transparent, #e0e0e0, transparent);
        margin: 3rem 0;
    }

    /* Webcam Modal */
    .webcam-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.95);
        z-index: 9999;
        animation: fadeIn 0.3s;
    }

    .webcam-modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .webcam-container {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        max-width: 700px;
        width: 90%;
    }

    .webcam-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .webcam-header h3 {
        margin: 0;
        font-size: 1.5rem;
        color: #333;
    }

    .webcam-video-container {
        position: relative;
        background: #000;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    #webcamVideo {
        width: 100%;
        display: block;
        border-radius: 12px;
    }

    #webcamCanvas {
        display: none;
    }

    .webcam-preview {
        display: none;
        width: 100%;
        border-radius: 12px;
    }

    .webcam-controls {
        display: flex;
        gap: 1rem;
        justify-content: center;
    }

    .webcam-controls button {
        padding: 1rem 2rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .capture-btn {
        background: #4CAF50;
        color: white;
        font-size: 1.1rem;
    }

    .capture-btn:hover {
        background: #45a049;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
    }

    .retake-btn {
        background: #ff9800;
        color: white;
        display: none;
    }

    .retake-btn:hover {
        background: #f57c00;
        transform: translateY(-2px);
    }

    .use-photo-btn {
        background: #000;
        color: white;
        display: none;
    }

    .use-photo-btn:hover {
        background: #333;
        transform: translateY(-2px);
    }

    .webcam-error {
        text-align: center;
        padding: 2rem;
        color: #dc3545;
        display: none;
    }

    .loading-spinner {
        text-align: center;
        padding: 2rem;
        color: #666;
    }

    .loading-spinner::after {
        content: "";
        display: inline-block;
        width: 30px;
        height: 30px;
        margin-left: 10px;
        border: 3px solid #f3f3f3;
        border-radius: 50%;
        border-top-color: #000;
        animation: spinner 0.8s linear infinite;
    }

    @keyframes spinner {
        to { transform: rotate(360deg); }
    }
</style>

<div class="avatar-upload-container">
    <div class="avatar-header">
        <h2>✨ Change Your Avatar</h2>
        <p>Upload a custom photo or choose from our defaults</p>
    </div>

    <div class="current-avatar-section">
        <img src="../images/avatars/<?= htmlspecialchars($current_avatar) ?>" alt="Current Avatar">
        <p><strong>Your Current Avatar</strong></p>
    </div>

    <div class="upload-section">
        <h3>📸 Upload Custom Avatar</h3>
        <div class="file-upload-area" id="uploadArea">
            <div class="file-upload-icon">📤</div>
            <p><strong>Click to upload</strong> or drag and drop</p>
            <p style="font-size: 0.85rem; color: #999;">JPG, PNG • Max 5MB</p>
            <input type="file" id="fileInput" accept="image/jpeg,image/png">
            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1rem;">
                <label for="fileInput" class="upload-btn">Choose Photo</label>
                <button class="upload-btn" onclick="openWebcam()" type="button" style="background: #4CAF50;">📷 Take Photo</button>
            </div>
        </div>
    </div>

    <div class="divider"></div>

    <div class="upload-section">
        <h3>🎭 Choose Default Avatar</h3>
        <form method="post" id="defaultForm">
            <div class="default-avatars">
                <?php for ($i=1; $i<=6; $i++): 
                    $def = "default$i.jpg";
                ?>
                    <label class="avatar-option">
                        <input type="radio" name="default_avatar" value="<?= $def ?>" 
                               <?= ($current_avatar === $def) ? 'checked' : '' ?>>
                        <img src="../images/avatars/<?= $def ?>" alt="Default <?= $i ?>">
                    </label>
                <?php endfor; ?>
            </div>
            <button type="submit" class="submit-default-btn">Select This Avatar</button>
        </form>
    </div>

    <div style="text-align: center;">
        <a href="profile.php" class="back-link">← Back to Profile</a>
    </div>
</div>

<!-- Image cropping Modal -->
<div class="crop-modal" id="cropModal">
    <div class="crop-container">
        <div class="crop-header">
            <h3>Edit Your Photo</h3>
            <button class="close-crop" onclick="closeCropModal()">×</button>
        </div>
        
        <div class="crop-image-container">
            <img id="cropImage" src="" alt="Crop">
        </div>

        <div class="crop-controls">
            <button class="crop-btn" onclick="cropper.rotate(-45)">↺ Rotate Left</button>
            <button class="crop-btn" onclick="cropper.rotate(45)">↻ Rotate Right</button>
            <button class="crop-btn" onclick="cropper.scaleX(-cropper.getData().scaleX || -1)">⇄ Flip H</button>
            <button class="crop-btn" onclick="cropper.scaleY(-cropper.getData().scaleY || -1)">⇅ Flip V</button>
            <button class="crop-btn" onclick="cropper.reset()">↶ Reset</button>
        </div>

        <div class="crop-actions">
            <button class="cancel-btn" onclick="closeCropModal()">Cancel</button>
            <button class="save-btn" onclick="saveCroppedImage()">Save Avatar</button>
        </div>
    </div>
</div>

<!-- Webcam Modal -->
<div class="webcam-modal" id="webcamModal">
    <div class="webcam-container">
        <div class="webcam-header">
            <h3>📷 Take a Photo</h3>
            <button class="close-crop" onclick="closeWebcam()">×</button>
        </div>

        <div class="loading-spinner" id="webcamLoading">
            Starting camera...
        </div>

        <div class="webcam-error" id="webcamError">
            <p><strong>❌ Camera Access Denied</strong></p>
            <p>Please allow camera access in your browser settings</p>
        </div>
        
        <div class="webcam-video-container" id="webcamVideoContainer" style="display: none;">
            <video id="webcamVideo" autoplay playsinline></video>
            <canvas id="webcamCanvas"></canvas>
            <img id="webcamPreview" class="webcam-preview" alt="Preview">
        </div>

        <div class="webcam-controls">
            <button class="capture-btn" id="captureBtn" onclick="capturePhoto()" style="display: none;">
                📸 Capture
            </button>
            <button class="retake-btn" id="retakeBtn" onclick="retakePhoto()">
                🔄 Retake
            </button>
            <button class="use-photo-btn" id="usePhotoBtn" onclick="useWebcamPhoto()">
                ✓ Use This Photo
            </button>
            <button class="cancel-btn" onclick="closeWebcam()">Cancel</button>
        </div>
    </div>
</div>

<form method="post" id="uploadForm" style="display:none;">
    <input type="hidden" name="cropped_image" id="croppedImageData">
</form>

<script>
let cropper = null;
let webcamStream = null;

// File upload processing
const fileInput = document.getElementById('fileInput');
const uploadArea = document.getElementById('uploadArea');
const cropModal = document.getElementById('cropModal');
const cropImage = document.getElementById('cropImage');

fileInput.addEventListener('change', handleFileSelect);

// drag and drop function
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFile(files[0]);
    }
});

function handleFileSelect(e) {
    const file = e.target.files[0];
    if (file) {
        handleFile(file);
    }
}

function handleFile(file) {
    if (!file.type.match('image/jpeg') && !file.type.match('image/png')) {
        alert('Please upload JPG or PNG only');
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = (e) => {
        cropImage.src = e.target.result;
        openCropModal();
    };
    reader.readAsDataURL(file);
}

function openCropModal() {
    cropModal.classList.add('active');
    
    if (cropper) {
        cropper.destroy();
    }
    
    cropper = new Cropper(cropImage, {
        aspectRatio: 1,
        viewMode: 2,
        dragMode: 'move',
        autoCropArea: 1,
        restore: false,
        guides: true,
        center: true,
        highlight: false,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: false,
    });
}

function closeCropModal() {
    cropModal.classList.remove('active');
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    fileInput.value = '';
}

function saveCroppedImage() {
    const canvas = cropper.getCroppedCanvas({
        width: 400,
        height: 400,
        imageSmoothingQuality: 'high'
    });
    
    const croppedImageData = canvas.toDataURL('image/png');
    document.getElementById('croppedImageData').value = croppedImageData;
    document.getElementById('uploadForm').submit();
}

// WEBCAM

function openWebcam() {
    const webcamModal = document.getElementById('webcamModal');
    const webcamLoading = document.getElementById('webcamLoading');
    const webcamError = document.getElementById('webcamError');
    const webcamVideoContainer = document.getElementById('webcamVideoContainer');
    const captureBtn = document.getElementById('captureBtn');
    const video = document.getElementById('webcamVideo');
    
    webcamModal.classList.add('active');
    webcamLoading.style.display = 'block';
    webcamError.style.display = 'none';
    webcamVideoContainer.style.display = 'none';
    captureBtn.style.display = 'none';
    
    // Request camera permissions
    navigator.mediaDevices.getUserMedia({ 
        video: { 
            width: { ideal: 1280 },
            height: { ideal: 720 },
            facingMode: 'user'
        } 
    })
    .then(stream => {
        webcamStream = stream;
        video.srcObject = stream;
        
        webcamLoading.style.display = 'none';
        webcamVideoContainer.style.display = 'block';
        captureBtn.style.display = 'block';
    })
    .catch(err => {
        console.error('Webcam error:', err);
        webcamLoading.style.display = 'none';
        webcamError.style.display = 'block';
    });
}

function capturePhoto() {
    const video = document.getElementById('webcamVideo');
    const canvas = document.getElementById('webcamCanvas');
    const preview = document.getElementById('webcamPreview');
    const captureBtn = document.getElementById('captureBtn');
    const retakeBtn = document.getElementById('retakeBtn');
    const usePhotoBtn = document.getElementById('usePhotoBtn');
    
    // Set canvas size
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Drawing the current frame
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    // Show Preview
    preview.src = canvas.toDataURL('image/png');
    preview.style.display = 'block';
    video.style.display = 'none';
    
    // Update button
    captureBtn.style.display = 'none';
    retakeBtn.style.display = 'block';
    usePhotoBtn.style.display = 'block';
}

function retakePhoto() {
    const video = document.getElementById('webcamVideo');
    const preview = document.getElementById('webcamPreview');
    const captureBtn = document.getElementById('captureBtn');
    const retakeBtn = document.getElementById('retakeBtn');
    const usePhotoBtn = document.getElementById('usePhotoBtn');
    
    preview.style.display = 'none';
    video.style.display = 'block';
    
    captureBtn.style.display = 'block';
    retakeBtn.style.display = 'none';
    usePhotoBtn.style.display = 'none';
}

function useWebcamPhoto() {
    const canvas = document.getElementById('webcamCanvas');
    const imageData = canvas.toDataURL('image/png');
    
    // close webcam
    closeWebcam();
    
    // open cropper
    cropImage.src = imageData;
    openCropModal();
}

function closeWebcam() {
    const webcamModal = document.getElementById('webcamModal');
    const video = document.getElementById('webcamVideo');
    const preview = document.getElementById('webcamPreview');
    const captureBtn = document.getElementById('captureBtn');
    const retakeBtn = document.getElementById('retakeBtn');
    const usePhotoBtn = document.getElementById('usePhotoBtn');
    
    // Stop the camera
    if (webcamStream) {
        webcamStream.getTracks().forEach(track => track.stop());
        webcamStream = null;
    }
    
    // reset UI
    webcamModal.classList.remove('active');
    preview.style.display = 'none';
    video.style.display = 'block';
    captureBtn.style.display = 'none';
    retakeBtn.style.display = 'none';
    usePhotoBtn.style.display = 'none';
}
</script>

<?php include '../_foot.php'; ?>