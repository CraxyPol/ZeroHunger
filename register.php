<?php
// register.php - WITH LIVE CAMERA FACE VERIFICATION
require_once "db.php";
session_start();

function clean($s){ return trim(htmlspecialchars($s, ENT_QUOTES)); }

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name'] ?? '');
    $username = clean($_POST['username'] ?? '');
    $email = clean($_POST['email'] ?? '');
    $phone = clean($_POST['phone'] ?? '');
    $password_raw = $_POST['password'] ?? '';
    $user_type = in_array($_POST['user_type'] ?? 'Donor', ['Donor','CharitableInstitution']) ? $_POST['user_type'] : 'Donor';

    if ($name === '' || $username === '' || $email === '' || $password_raw === '') {
        $errors[] = "Please fill required fields.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
        $errors[] = "Invalid email address.";

    // Check unique username/email
    if (empty($errors)) {
        $chk = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $chk->bind_param("ss", $username, $email);
        $chk->execute();
        $res = $chk->get_result();
        if ($res->num_rows > 0) 
            $errors[] = "Username or email already taken.";
    }

    // Create upload directory
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $profile_photo_path = null;
    $uploaded_id_path = null;
    $selfie_path = null;

    // Handle camera capture (base64)
    if (!empty($_POST['face_capture_data'])) {
        $imageData = $_POST['face_capture_data'];
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $type = strtolower($type[1]);
            
            if (in_array($type, ['jpg','jpeg','png'])) {
                $imageData = base64_decode($imageData);
                if ($imageData !== false) {
                    $fn = time() . '_selfie_' . uniqid() . '.' . $type;
                    $target = $uploadDir . $fn;
                    if (file_put_contents($target, $imageData)) {
                        $selfie_path = 'uploads/' . $fn;
                        $profile_photo_path = 'uploads/' . $fn; // use selfie as profile
                    } else {
                        $errors[] = "Failed to save face verification photo.";
                    }
                }
            }
        }
    } else {
        $errors[] = "Face verification photo is required.";
    }

    // Upload ID
    if (isset($_FILES['uploaded_id']) && $_FILES['uploaded_id']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['uploaded_id']['size'] > 8 * 1024 * 1024) 
            $errors[] = "Uploaded ID too large (max 8MB).";

        $allowedID = ['image/jpeg','image/png','application/pdf','image/webp'];
        if (!in_array($_FILES['uploaded_id']['type'], $allowedID)) 
            $errors[] = "ID must be image or PDF.";

        if (empty($errors)) {
            $fn = time() . '_id_' . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", basename($_FILES['uploaded_id']['name']));
            $target = $uploadDir . $fn;
            if (move_uploaded_file($_FILES['uploaded_id']['tmp_name'], $target)) {
                $uploaded_id_path = 'uploads/' . $fn;
            }
        }
    }

    if (empty($errors)) {
        $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);

        // ✔️ FIX: Insert into selfie column instead of non-existing `face_verification`
        $stmt = $conn->prepare("INSERT INTO users 
            (name, username, email, phone_number, password, user_type, profile_photo, uploaded_id, selfie, account_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");

        $stmt->bind_param("sssssssss", 
            $name, 
            $username, 
            $email, 
            $phone, 
            $password_hashed, 
            $user_type, 
            $profile_photo_path, 
            $uploaded_id_path, 
            $selfie_path
        );

        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;

            if ($user_type === 'CharitableInstitution') {
                $institution_name = clean($_POST['institution_name'] ?? $name . " Institution");
                $number_of_children = intval($_POST['number_of_children'] ?? 0);
                $profile_description = clean($_POST['profile_description'] ?? '');

                $ins = $conn->prepare("INSERT INTO charitable_institutions 
                    (user_id, institution_name, number_of_children, profile_description) 
                    VALUES (?, ?, ?, ?)");
                $ins->bind_param("isis", $user_id, $institution_name, $number_of_children, $profile_description);
                $ins->execute();
            }

            $p = $conn->prepare("INSERT INTO pending_accounts (user_id) VALUES (?)");
            $p->bind_param("i", $user_id);
            $p->execute();

            $success = "Registration successful! Your account is pending admin approval.";
            $_POST = [];
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register — ZeroHunger</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
:root{--pastel-purple:#E6D5F5;--pastel-purple-light:#F3EBFC;--purple-primary:#B794F6;--purple-dark:#9F7AEA;--white:#FFFFFF;--text-primary:#4A5568;--text-secondary:#718096;--shadow-md:0 4px 16px rgba(183,148,246,0.12);--shadow-lg:0 10px 40px rgba(183,148,246,0.18);--radius:16px;--transition:all .3s cubic-bezier(.4,0,.2,1)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#F3EBFC 0%,#E6D5F5 50%,#D6BCFA 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;position:relative;overflow-x:hidden}
body::before,body::after{content:'';position:absolute;border-radius:50%;background:rgba(255,255,255,0.1);animation:float 20s infinite ease-in-out;z-index:0}
body::before{width:400px;height:400px;top:-100px;left:-100px}
body::after{width:300px;height:300px;bottom:-80px;right:-80px;animation-delay:5s}
@keyframes float{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(30px,-50px) scale(1.1)}66%{transform:translate(-20px,30px) scale(0.9)}}
.container{max-width:600px;width:100%;position:relative;z-index:1;animation:slideUp .6s ease-out}
@keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
.card{background:rgba(255,255,255,0.95);backdrop-filter:blur(20px);border-radius:var(--radius);padding:48px 40px;box-shadow:var(--shadow-lg);border:1px solid rgba(255,255,255,0.8)}
.logo-container{text-align:center;margin-bottom:32px}
.logo-wrapper{width:80px;height:80px;margin:0 auto 16px;border-radius:50%;background:linear-gradient(135deg,var(--purple-primary),var(--purple-dark));display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-md);transition:var(--transition)}
.logo-wrapper:hover{transform:scale(1.05) rotate(5deg)}
.logo-placeholder{font-size:36px;color:var(--white);font-weight:600}
h1{text-align:center;color:var(--purple-dark);font-size:28px;font-weight:600;margin-bottom:8px}
.subtitle{text-align:center;color:var(--text-secondary);font-size:14px;margin-bottom:32px}
.form-group{margin-bottom:20px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px}
label{display:block;color:var(--text-primary);font-size:14px;font-weight:500;margin-bottom:8px}
.required::after{content:' *';color:#E53E3E}
input[type="text"],input[type="email"],input[type="password"],input[type="number"],select{width:100%;padding:12px 16px;border:2px solid var(--pastel-purple);border-radius:12px;font-size:15px;color:var(--text-primary);background:var(--white);transition:var(--transition);font-family:'Poppins',sans-serif}
input:focus,select:focus{outline:none;border-color:var(--purple-primary);background:var(--pastel-purple-light);box-shadow:0 0 0 4px rgba(183,148,246,0.1)}
.file-upload{margin-bottom:20px}
.file-input-wrapper{display:flex;align-items:center;gap:12px;padding:12px 16px;border:2px dashed var(--pastel-purple);border-radius:12px;background:var(--pastel-purple-light);cursor:pointer;position:relative}
.file-input-wrapper:hover{border-color:var(--purple-primary);background:var(--white)}
.file-input-wrapper input[type="file"]{position:absolute;opacity:0;width:100%;height:100%;cursor:pointer}
.file-icon{font-size:24px}
.file-text{flex:1}
.file-label{font-size:14px;color:var(--text-primary);font-weight:500}
.file-name{font-size:12px;color:var(--text-secondary);margin-top:2px}
.camera-button{width:100%;padding:14px 16px;background:linear-gradient(135deg,var(--purple-primary),var(--purple-dark));color:var(--white);border:none;border-radius:12px;font-size:15px;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:10px;font-family:'Poppins',sans-serif;transition:var(--transition)}
.camera-button:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
.camera-button.captured{background:linear-gradient(135deg,#48BB78,#38A169)}
.modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.8);backdrop-filter:blur(5px)}
.modal-content{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--white);border-radius:var(--radius);padding:32px;max-width:600px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-lg)}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.modal-header h2{color:var(--purple-dark);font-size:24px;font-weight:600}
.close-modal{background:none;border:none;font-size:28px;color:var(--text-secondary);cursor:pointer;padding:0;width:32px;height:32px;border-radius:50%;transition:var(--transition)}
.close-modal:hover{background:var(--pastel-purple-light);color:var(--purple-dark)}
.camera-container{position:relative;width:100%;margin-bottom:20px;border-radius:12px;overflow:hidden;background:#000;aspect-ratio:4/3}
#video{width:100%;height:100%;object-fit:cover;display:block;border-radius:12px}
#canvas{display:none}
.camera-preview{width:100%;border-radius:12px;margin-bottom:16px;box-shadow:var(--shadow-md)}
.camera-controls{display:flex;gap:12px}
.camera-controls button{flex:1;padding:12px 24px;border:none;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;font-family:'Poppins',sans-serif;transition:var(--transition)}
.capture-btn{background:linear-gradient(135deg,var(--purple-primary),var(--purple-dark));color:var(--white)}
.capture-btn:hover{transform:translateY(-2px)}
.retake-btn{background:#FC8181;color:var(--white)}
.use-photo-btn{background:#48BB78;color:var(--white)}
.camera-instructions{background:var(--pastel-purple-light);padding:16px;border-radius:12px;margin-bottom:20px;border-left:4px solid var(--purple-primary)}
.camera-instructions p{font-size:14px;color:var(--text-primary);margin:0;line-height:1.6}
.institution-fields{background:var(--pastel-purple-light);border-radius:12px;padding:20px;margin-bottom:20px;border:2px solid var(--pastel-purple)}
.btn{width:100%;padding:14px 24px;background:linear-gradient(135deg,var(--purple-primary),var(--purple-dark));color:var(--white);border:none;border-radius:12px;font-size:16px;font-weight:600;cursor:pointer;transition:var(--transition);box-shadow:var(--shadow-md);font-family:'Poppins',sans-serif;margin-top:8px;position:relative;overflow:hidden}
.btn::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);transition:left .5s}
.btn:hover::before{left:100%}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(183,148,246,0.4)}
.login-link{text-align:center;margin-top:24px;color:var(--text-secondary);font-size:14px}
.login-link a{color:var(--purple-dark);text-decoration:none;font-weight:600}
.login-link a:hover{text-decoration:underline}
.msg{padding:14px 16px;border-radius:12px;margin-bottom:24px;font-size:14px}
.msg.error{background:linear-gradient(135deg,#FED7D7,#FEB2B2);color:#C53030;border:1px solid #FC8181}
.msg.success{background:linear-gradient(135deg,#C6F6D5,#9AE6B4);color:#22543D;border:1px solid #68D391}
.info-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:rgba(183,148,246,0.1);border-radius:20px;font-size:13px;color:var(--purple-dark);margin-top:16px}
@media(max-width:576px){.card{padding:36px 24px}h1{font-size:24px}.form-row{grid-template-columns:1fr}.logo-wrapper{width:70px;height:70px}.modal-content{padding:24px}}
</style>
</head>
<body>
<div class="container">
  <div class="card">
    <div class="logo-container">
      <div class="logo-wrapper">
        <div class="logo-placeholder">ZH</div>
      </div>
    </div>

    <h1>Join ZeroHunger</h1>
    <p class="subtitle">Create an account to start making a difference</p>

    <?php if(!empty($errors)):?>
      <div class="msg error"><?= htmlspecialchars(implode(' • ',$errors))?></div>
    <?php endif;?>
    <?php if($success):?>
      <div class="msg success"><?= htmlspecialchars($success)?></div>
    <?php endif;?>

    <form method="post" enctype="multipart/form-data" id="regForm">
      <input type="hidden" name="face_capture_data" id="face_capture_data">

      <div class="form-group">
        <label class="required">Full Name</label>
        <input name="name" type="text" required placeholder="Enter your full name" value="<?= htmlspecialchars($_POST['name']??'')?>">
      </div>

      <div class="form-row">
        <div>
          <label class="required">Username</label>
          <input name="username" type="text" required placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username']??'')?>">
        </div>
        <div>
          <label class="required">Email</label>
          <input name="email" type="email" required placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email']??'')?>">
        </div>
      </div>

      <div class="form-row">
        <div>
          <label>Phone Number</label>
          <input name="phone" type="text" placeholder="(optional)" value="<?= htmlspecialchars($_POST['phone']??'')?>">
        </div>
        <div>
          <label class="required">Password</label>
          <input name="password" type="password" required placeholder="Create a password">
        </div>
      </div>

      <div class="form-group">
        <label class="required">Account Type</label>
        <select name="user_type" id="user_type" onchange="toggleInstitution()">
          <option value="Donor" <?=(($_POST['user_type']??'')==='Donor')?'selected':''?>>Donor</option>
          <option value="CharitableInstitution" <?=(($_POST['user_type']??'')==='CharitableInstitution')?'selected':''?>>Charitable Institution</option>
        </select>
      </div>

      <div id="institution_fields" style="display:<?=(($_POST['user_type']??'')==='CharitableInstitution')?'block':'none'?>">
        <div class="institution-fields">
          <div class="form-group">
            <label>Institution Name</label>
            <input name="institution_name" type="text" placeholder="Name of your institution" value="<?= htmlspecialchars($_POST['institution_name']??'')?>">
          </div>
          <div class="form-row">
            <div>
              <label>Number of Children</label>
              <input name="number_of_children" type="number" min="0" placeholder="0" value="<?= htmlspecialchars($_POST['number_of_children']??'')?>">
            </div>
            <div>
              <label>Profile Description</label>
              <input name="profile_description" type="text" placeholder="Brief description" value="<?= htmlspecialchars($_POST['profile_description']??'')?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Face Verification Camera -->
      <div class="form-group">
        <label class="required">Face Verification</label>
        <button type="button" class="camera-button" id="openCameraBtn">
          <span>📸</span>
          <span id="cameraButtonText">Capture Your Face</span>
        </button>
        <p style="font-size:12px;color:var(--text-secondary);margin-top:8px;text-align:center">Required for identity verification with your ID</p>
      </div>

      <div class="file-upload">
        <label>Upload ID / Document</label>
        <div class="file-input-wrapper">
          <span class="file-icon">📄</span>
          <div class="file-text">
            <div class="file-label">Choose ID document</div>
            <div class="file-name" id="idName">Max 8MB - JPG, PNG, PDF, WEBP</div>
          </div>
          <input type="file" name="uploaded_id" accept="image/*,application/pdf" id="uploaded_id">
        </div>
      </div>

      <button class="btn" type="submit">Create Account</button>
    </form>

    <div class="login-link">
      Already have an account? <a href="login.php">Login here</a>
    </div>

    <div style="text-align:center">
      <div class="info-badge">
        <span>🔒</span>
        <span>All accounts are reviewed by admin for security</span>
      </div>
    </div>
  </div>
</div>

<!-- Camera Modal -->
<div id="cameraModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Face Verification</h2>
      <button type="button" class="close-modal" id="closeModal">&times;</button>
    </div>

    <div class="camera-instructions">
      <p><strong>📸 Instructions:</strong> Position your face in the center of the camera frame. Make sure your face is well-lit and clearly visible. This photo will be used to verify your identity against your uploaded ID.</p>
    </div>

    <div class="camera-container">
      <video id="video" autoplay playsinline></video>
      <canvas id="canvas"></canvas>
    </div>

    <div id="previewContainer" style="display:none">
      <img id="preview" class="camera-preview" alt="Captured photo">
    </div>

    <div class="camera-controls">
      <button type="button" class="capture-btn" id="captureBtn">📸 Capture Photo</button>
      <button type="button" class="retake-btn" id="retakeBtn" style="display:none">🔄 Retake</button>
      <button type="button" class="use-photo-btn" id="usePhotoBtn" style="display:none">✓ Use This Photo</button>
    </div>
  </div>
</div>

<script>
let stream = null;
let capturedImage = null;

const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const preview = document.getElementById('preview');
const modal = document.getElementById('cameraModal');
const openCameraBtn = document.getElementById('openCameraBtn');
const closeModalBtn = document.getElementById('closeModal');
const captureBtn = document.getElementById('captureBtn');
const retakeBtn = document.getElementById('retakeBtn');
const usePhotoBtn = document.getElementById('usePhotoBtn');
const previewContainer = document.getElementById('previewContainer');
const cameraButtonText = document.getElementById('cameraButtonText');
const faceCaptureData = document.getElementById('face_capture_data');

// Open camera modal
openCameraBtn.addEventListener('click', async function() {
  modal.style.display = 'block';
  try {
    stream = await navigator.mediaDevices.getUserMedia({ 
      video: { 
        width: { ideal: 1280 },
        height: { ideal: 720 },
        facingMode: 'user'
      } 
    });
    video.srcObject = stream;
    video.style.display = 'block';
    canvas.style.display = 'none';
    previewContainer.style.display = 'none';
    captureBtn.style.display = 'block';
    retakeBtn.style.display = 'none';
    usePhotoBtn.style.display = 'none';
  } catch (err) {
    alert('Unable to access camera. Please check permissions.');
    console.error('Camera error:', err);
    modal.style.display = 'none';
  }
});

// Close modal
function closeModal() {
  modal.style.display = 'none';
  if (stream) {
    stream.getTracks().forEach(track => track.stop());
    stream = null;
  }
}

closeModalBtn.addEventListener('click', closeModal);

modal.addEventListener('click', function(e) {
  if (e.target === modal) closeModal();
});

// Capture photo
captureBtn.addEventListener('click', function() {
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(video, 0, 0);
  
  capturedImage = canvas.toDataURL('image/jpeg', 0.8);
  preview.src = capturedImage;
  
  video.style.display = 'none';
  previewContainer.style.display = 'block';
  captureBtn.style.display = 'none';
  retakeBtn.style.display = 'block';
  usePhotoBtn.style.display = 'block';
});

// Retake photo
retakeBtn.addEventListener('click', function() {
  video.style.display = 'block';
  previewContainer.style.display = 'none';
  captureBtn.style.display = 'block';
  retakeBtn.style.display = 'none';
  usePhotoBtn.style.display = 'none';
  capturedImage = null;
});

// Use captured photo
usePhotoBtn.addEventListener('click', function() {
  if (capturedImage) {
    faceCaptureData.value = capturedImage;
    cameraButtonText.textContent = '✓ Face Captured';
    openCameraBtn.classList.add('captured');
    closeModal();
  }
});

// File input handlers
document.getElementById('uploaded_id').addEventListener('change', function(e) {
  document.getElementById('idName').textContent = e.target.files[0]?.name || 'Max 8MB - JPG, PNG, PDF, WEBP';
});

// Toggle institution fields
function toggleInstitution() {
  const userType = document.getElementById('user_type').value;
  document.getElementById('institution_fields').style.display = 
    userType === 'CharitableInstitution' ? 'block' : 'none';
}

// Form validation
document.getElementById('regForm').addEventListener('submit', function(e) {
  if (!faceCaptureData.value) {
    e.preventDefault();
    alert('Please capture your face for verification before submitting.');
    openCameraBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
    openCameraBtn.style.animation = 'shake 0.5s';
    setTimeout(() => openCameraBtn.style.animation = '', 500);
  }
});
</script>
</body>
</html>