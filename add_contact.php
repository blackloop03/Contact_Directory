<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form_type'] === 'single') {
    $user_id = $_SESSION['user_id'];
    $first_name = trim($_POST['first_name']);
    $phone = trim($_POST['phone']);

    if (empty($first_name) || empty($phone)) {
        $errors[] = 'First name and phone are required.';
    }

    if (!preg_match('/^\d{10}$/', $phone)) {
        $errors[] = 'Phone number must be exactly 10 digits.';
    }

    if (empty($errors)) {
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $company = trim($_POST['company']);
        $notes = trim($_POST['notes']);

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM contacts WHERE user_id = ? AND LOWER(first_name) = LOWER(?) AND phone = ?"
        );
        $stmt->execute([$user_id, $first_name, $phone]);
        $exists = $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM contacts WHERE user_id = ? AND phone = ?"
        );
        $stmt->execute([$user_id, $phone]);
        $phone_exists = $stmt->fetchColumn();

        if ($exists) {
            $errors[] = 'This contact with same name and phone already exists.';
        } elseif ($phone_exists) {
            $errors[] = 'This phone number is already used for another contact.';
        }
    }

    if (empty($errors)) {
        $avatar_path = null;

        if (!empty($_FILES['avatar']['name'])) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = uniqid() . '_' . basename($_FILES['avatar']['name']);
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                $avatar_path = $target_file;
            } else {
                $errors[] = 'Failed to upload avatar.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO contacts 
                (user_id, first_name, last_name, email, phone, address, company, notes, avatar_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id, $first_name, $last_name, $email, $phone, $address, $company, $notes, $avatar_path
            ]);

            $success = "Contact added successfully!";
            $_POST = []; 
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form_type'] === 'bulk') {
    $user_id = $_SESSION['user_id'];
    
    if (isset($_FILES['bulk_file']) && $_FILES['bulk_file']['error'] === UPLOAD_ERR_OK) {
        require 'vendor/autoload.php';
        
        $file = $_FILES['bulk_file']['tmp_name'];
        $file_type = $_FILES['bulk_file']['type'];
        $file_ext = strtolower(pathinfo($_FILES['bulk_file']['name'], PATHINFO_EXTENSION));
        
        $allowed_extensions = ['csv', 'xlsx', 'xls'];
        if (!in_array($file_ext, $allowed_extensions)) {
            $errors[] = 'Invalid file type. Please upload a CSV or Excel file.';
        }
        
        if ($_FILES['bulk_file']['size'] > 5242880) {
            $errors[] = 'File size exceeds 5MB limit.';
        }
        
        if (empty($errors)) {
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
                
                $header = array_shift($rows);
                
                $expected_headers = ['First Name*', 'Last Name', 'Phone* (10 digits)', 'Email', 'Company', 'Address', 'Notes'];
                if ($header !== $expected_headers) {
                    $errors[] = 'Invalid file format. Please use our template.';
                }
                
                if (empty($errors)) {
                    $valid_contacts = [];
                    $invalid_rows = [];
                    $success_count = 0;
                    
                    foreach ($rows as $i => $row) {
                        $row_num = $i + 2;
                        $row_errors = [];
                        
                        if (empty($row[0])) {
                            $row_errors[] = "Row $row_num: First name is required";
                        } elseif (preg_match('/^[0-9]+$/', $row[0])) {
                            $row_errors[] = "Row $row_num: First name cannot be all numbers";
                        }
                        
                        if (empty($row[2])) {
                            $row_errors[] = "Row $row_num: Phone is required";
                        } elseif (!preg_match('/^\d{10}$/', $row[2])) {
                            $row_errors[] = "Row $row_num: Phone must be exactly 10 digits";
                        }
                        
                        if (!empty($row[3]) && !filter_var($row[3], FILTER_VALIDATE_EMAIL)) {
                            $row_errors[] = "Row $row_num: Invalid email format";
                        }
                        
                        if (empty($row_errors)) {
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contacts WHERE user_id = ? AND phone = ?");
                            $stmt->execute([$user_id, $row[2]]);
                            $exists = $stmt->fetchColumn();
                            
                            if ($exists) {
                                $row_errors[] = "Row $row_num: Phone number already exists in your contacts";
                            } else {
                                $valid_contacts[] = [
                                    'first_name' => $row[0],
                                    'last_name'  => $row[1] ?? '',
                                    'phone'      => $row[2],
                                    'email'      => $row[3] ?? '',
                                    'company'    => $row[4] ?? '',
                                    'address'    => $row[5] ?? '',
                                    'notes'      => $row[6] ?? ''
                                ];
                            }
                        }
                        
                        if (!empty($row_errors)) {
                            $invalid_rows = array_merge($invalid_rows, $row_errors);
                        }
                    }
                    
                    if (!empty($valid_contacts)) {
                        $stmt = $pdo->prepare("INSERT INTO contacts 
                            (user_id, first_name, last_name, email, phone, address, company, notes) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        
                        foreach ($valid_contacts as $contact) {
                            $stmt->execute([
                                $user_id,
                                $contact['first_name'],
                                $contact['last_name'],
                                $contact['email'],
                                $contact['phone'],
                                $contact['address'],
                                $contact['company'],
                                $contact['notes']
                            ]);
                            $success_count++;
                        }
                    }
                    
                    $message = "Processed " . count($rows) . " rows. ";
                    $message .= "Successfully imported $success_count contacts. ";
                    
                    if (!empty($invalid_rows)) {
                        $message .= count($invalid_rows) . " errors found.";
                        $_SESSION['bulk_errors'] = $invalid_rows;
                    }
                    
                    $_SESSION['bulk_message'] = $message;
                    header("Location: add_contact.php?bulk_result=1");
                    exit;
                }
            } catch (Exception $e) {
                $errors[] = 'Error processing file: ' . $e->getMessage();
            }
        }
    } else {
        $errors[] = 'Please select a file to upload.';
    }
}

if (isset($_GET['bulk_result']) && isset($_SESSION['bulk_message'])) {
    $success = $_SESSION['bulk_message'];
    unset($_SESSION['bulk_message']);
    
    if (isset($_SESSION['bulk_errors'])) {
        $errors = $_SESSION['bulk_errors'];
        unset($_SESSION['bulk_errors']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Contact - Contact Directory</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'cream': '#FEF7ED',
                    'darkgreen': '#166534',
                    'tealgreen': '#0D9488',
                    'lightgreen': '#DCFCE7',
                    'mediumgreen': '#22C55E'
                }
            }
        }
    }
</script>
<style>
    .form-input {
        transition: all 0.3s ease;
    }
    .form-input:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.15);
    }
    .tab-button.active {
        background: linear-gradient(135deg, #0D9488 0%, #14B8A6 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
    }
    .upload-area {
        background: linear-gradient(135deg, #F0FDF4 0%, #ECFDF5 100%);
        border: 2px dashed #22C55E;
        transition: all 0.3s ease;
    }
    .upload-area:hover {
        border-color: #0D9488;
        background: linear-gradient(135deg, #F0FDFA 0%, #CCFBF1 100%);
    }
    .btn-primary {
        background: linear-gradient(135deg, #0D9488 0%, #14B8A6 100%);
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(13, 148, 136, 0.3);
    }
    .btn-secondary {
        background: linear-gradient(135deg, #166534 0%, #15803D 100%);
        transition: all 0.3s ease;
    }
    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(22, 101, 52, 0.3);
    }
    .card-shadow {
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }
    #qr-video {
        max-width: 100%;
        border-radius: 8px;
    }
    #qr-canvas {
        display: none;
    }
</style>
</head>
<body class="bg-gradient-to-br from-cream via-orange-50 to-emerald-50 min-h-screen">

<?php include 'includes/header.php'; ?>

<main class="max-w-5xl mx-auto px-4 py-8">
    <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-darkgreen mb-2">Add New Contact</h1>
        <p class="text-gray-600 text-lg">Build your network, one contact at a time</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl card-shadow overflow-hidden">
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 pt-6">
            <div class="flex space-x-1 bg-white p-1 rounded-xl shadow-inner">
                <button class="tab-button flex-1 py-3 px-6 rounded-lg font-semibold transition-all duration-300 active" 
                        onclick="showTab('single')">
                    <span class="text-sm">📱</span> Single Upload
                </button>
                <button class="tab-button flex-1 py-3 px-6 rounded-lg font-semibold transition-all duration-300 text-gray-600 hover:text-tealgreen" 
                        onclick="showTab('bulk')">
                    <span class="text-sm">📊</span> Bulk Upload
                </button>
            </div>
        </div>

        <div class="p-8">
            <div id="single" class="tab-content">
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-r-lg">
                        <div class="flex items-center">
                            <span class="text-red-400 mr-2">⚠️</span>
                            <h3 class="font-semibold text-red-700">Please fix the following issues:</h3>
                        </div>
                        <ul class="mt-2 list-disc list-inside text-red-600">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php elseif (!empty($success)): ?>
                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r-lg">
                        <div class="flex items-center">
                            <span class="text-green-400 mr-2">✅</span>
                            <p class="font-semibold text-green-700"><?= htmlspecialchars($success) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" id="contactForm" onsubmit="return validatePhone()" class="space-y-6">
                    <input type="hidden" name="form_type" value="single">
                    
                    <div class="bg-gray-50 p-6 rounded-xl">
                        <h3 class="text-lg font-semibold text-darkgreen mb-4 flex items-center">
                            <span class="mr-2">👤</span> Basic Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                                <input type="text" name="first_name" required
                                       value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                                       class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                                <input type="text" name="last_name"
                                       value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                                       class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 p-6 rounded-xl">
                        <h3 class="text-lg font-semibold text-darkgreen mb-4 flex items-center">
                            <span class="mr-2">📞</span> Contact Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                                <input type="text" id="phone" name="phone" required placeholder="1234567890"
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                       class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Must be exactly 10 digits</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" name="email"
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                       class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="bg-purple-50 p-6 rounded-xl">
                        <h3 class="text-lg font-semibold text-darkgreen mb-4 flex items-center">
                            <span class="mr-2">🏢</span> Additional Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Company</label>
                                <input type="text" name="company"
                                       value="<?= htmlspecialchars($_POST['company'] ?? '') ?>"
                                       class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <input type="text" name="address"
                                       value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
                                       class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                            <textarea name="notes" rows="3" placeholder="Any additional notes about this contact..."
                                      class="form-input w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-tealgreen focus:outline-none resize-none"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="bg-green-50 p-6 rounded-xl">
                        <h3 class="text-lg font-semibold text-darkgreen mb-4 flex items-center">
                            <span class="mr-2">📸</span> Profile Picture
                        </h3>
                        <div class="upload-area p-6 rounded-xl text-center">
                            <input type="file" name="avatar" accept="image/*" id="avatar-input" class="hidden">
                            <label for="avatar-input" class="cursor-pointer">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-mediumgreen rounded-full flex items-center justify-center text-white text-2xl mb-2">📷</div>
                                    <p class="text-mediumgreen font-semibold">Click to upload an image</p>
                                    <p class="text-sm text-gray-500 mt-1">PNG, JPG up to 10MB</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="bg-yellow-50 p-6 rounded-xl">
                        <h3 class="text-lg font-semibold text-darkgreen mb-4 flex items-center">
                            <span class="mr-2">🔍</span> Scan QR Code
                        </h3>
                        <div class="upload-area p-6 rounded-xl text-center">
                            <button type="button" onclick="startQRScanner()" class="text-mediumgreen hover:text-tealgreen">
                                <span class="text-4xl">📷</span>
                            </button>
                            <div id="qr-scanner" class="hidden mt-4">
                                <video id="qr-video" class="w-full max-w-md mx-auto"></video>
                                <canvas id="qr-canvas"></canvas>
                                <div class="flex justify-center gap-4 mt-4">
                                    <button type="button" onclick="captureQR()" class="btn-primary text-white px-6 py-3 rounded-xl font-semibold">
                                        <span class="mr-2">📸</span> Capture QR
                                    </button>
                                    <button type="button" onclick="stopQRScanner()" class="btn-secondary text-white px-6 py-3 rounded-xl font-semibold">
                                        <span class="mr-2">🛑</span> Stop Scanner
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4 pt-4">
                        <button type="submit" class="btn-primary text-white px-8 py-4 rounded-xl font-semibold text-lg flex-1 sm:flex-initial">
                            <span class="mr-2">💾</span> Add Contact
                        </button>
                        <a href="dashboard.php" class="btn-secondary text-white px-8 py-4 rounded-xl font-semibold text-lg text-center flex-1 sm:flex-initial">
                            <span class="mr-2">🔙</span> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>

            <div id="bulk" class="tab-content hidden">
                <div class="space-y-6">
                    <div class="bg-blue-50 p-6 rounded-xl">
                        <h3 class="text-lg font-semibold text-darkgreen mb-4 flex items-center">
                            <span class="mr-2">📝</span> Instructions
                        </h3>
                        <ol class="list-decimal list-inside space-y-2 text-gray-700">
                            <li>Download our template file to ensure proper formatting</li>
                            <li>Fill in your contact information</li>
                            <li>Upload the completed file below</li>
                            <li>We'll validate and import your contacts</li>
                        </ol>
                        <div class="mt-4">
                            <a href="download_template.php" class="inline-flex items-center btn-secondary text-white px-6 py-3 rounded-xl font-semibold">
                                <span class="mr-2">📥</span> Download Template
                            </a>
                        </div>
                    </div>

                    <form method="post" enctype="multipart/form-data" id="bulkUploadForm">
                        <input type="hidden" name="form_type" value="bulk">
                        <div class="bg-green-50 p-6 rounded-xl">
                            <h3 class="text-lg font-semibold text-darkgreen mb-4 flex items-center">
                                <span class="mr-2">📤</span> Upload Your File
                            </h3>
                            <div class="upload-area p-8 rounded-xl text-center border-2 border-dashed border-tealgreen">
                                <input type="file" name="bulk_file" accept=".csv, .xlsx, .xls" id="bulk-file-input" class="hidden" required>
                                <label for="bulk-file-input" class="cursor-pointer">
                                    <div class="flex flex-col items-center">
                                        <div class="w-20 h-20 bg-mediumgreen rounded-full flex items-center justify-center text-white text-3xl mb-4">📁</div>
                                        <p class="text-mediumgreen font-semibold text-lg">Click to upload your Excel/CSV file</p>
                                        <p class="text-sm text-gray-500 mt-2">Supports .xlsx, .xls, or .csv files (Max 5MB)</p>
                                    </div>
                                </label>
                            </div>
                            <div id="file-info" class="mt-4 hidden">
                                <p class="text-gray-700"><span class="font-semibold">Selected file:</span> <span id="file-name"></span></p>
                                <p class="text-sm text-gray-500 mt-1">Click the upload area to change file</p>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-4 pt-4">
                            <button type="submit" class="btn-primary text-white px-8 py-4 rounded-xl font-semibold text-lg flex-1 sm:flex-initial">
                                <span class="mr-2">🚀</span> Upload & Process
                            </button>
                            <a href="dashboard.php" class="btn-secondary text-white px-8 py-4 rounded-xl font-semibold text-lg text-center flex-1 sm:flex-initial">
                                <span class="mr-2">🔙</span> Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="qr-confirm-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white p-6 rounded-xl max-w-md w-full">
            <h3 class="text-lg font-semibold text-darkgreen mb-4">Confirm Contact Details</h3>
            <div id="qr-contact-details" class="space-y-2 text-gray-700"></div>
            <div class="flex justify-end gap-4 mt-6">
                <button onclick="cancelQRConfirm()" class="btn-secondary text-white px-4 py-2 rounded-xl">Cancel</button>
                <button onclick="confirmQRContact()" class="btn-primary text-white px-4 py-2 rounded-xl">Confirm</button>
            </div>
        </div>
    </div>
</main>

<script>
let stream = null;

function showTab(tab) {
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
        btn.classList.add('text-gray-600', 'hover:text-tealgreen');
    });
    
    event.target.classList.add('active');
    event.target.classList.remove('text-gray-600', 'hover:text-tealgreen');
    
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById(tab).classList.remove('hidden');

    if (tab === 'single') {
        stopQRScanner();
    }
}

function validatePhone() {
    const phone = document.getElementById('phone').value.trim();
    if (!/^\d{10}$/.test(phone)) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        alertDiv.innerHTML = '⚠️ Phone number must be exactly 10 digits.';
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
        
        return false;
    }
    return true;
}

function startQRScanner() {
    const video = document.getElementById('qr-video');
    const canvasElement = document.getElementById('qr-canvas');
    const qrScanner = document.getElementById('qr-scanner');
    
    qrScanner.classList.remove('hidden');
    
    navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: 'user',
            width: { ideal: 1280 },
            height: { ideal: 720 }
        } 
    })
        .then(function(mediaStream) {
            stream = mediaStream;
            video.srcObject = stream;
            video.play();
        })
        .catch(function(err) {
            showError('Camera access denied: ' + err.message);
            qrScanner.classList.add('hidden');
        });
}

function stopQRScanner() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    document.getElementById('qr-scanner').classList.add('hidden');
}

function captureQR() {
    const video = document.getElementById('qr-video');
    const canvasElement = document.getElementById('qr-canvas');
    const canvas = canvasElement.getContext('2d');
    
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvasElement.height = video.videoHeight;
        canvasElement.width = video.videoWidth;
        canvas.drawImage(video, 0, 0, canvasElement.width, canvasElement.height);
        const imageData = canvas.getImageData(0, 0, canvasElement.width, canvasElement.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'dontInvert',
        });
        
        if (code) {
            processQRCode(code.data);
            stopQRScanner();
        } else {
            showError('No QR code detected. Please try again.');
        }
    } else {
        showError('Camera not ready. Please wait a moment and try again.');
    }
}

function processQRCode(data) {
    try {
        const contact = parseContactData(data);
        if (contact) {
            showConfirmModal(contact);
        } else {
            showError('Invalid contact data in QR code');
        }
    } catch (e) {
        showError('Error processing QR code: ' + e.message);
    }
}

function parseContactData(data) {
    let contact = {};
    
    if (data.startsWith('BEGIN:VCARD')) {
        const lines = data.split('\n');
        lines.forEach(line => {
            if (line.startsWith('FN:')) {
                const name = line.replace('FN:', '').split(' ');
                contact.first_name = name[0] || '';
                contact.last_name = name.slice(1).join(' ') || '';
            } else if (line.startsWith('TEL')) {
                contact.phone = line.replace(/[^0-9]/g, '').slice(-10);
            } else if (line.startsWith('EMAIL')) {
                contact.email = line.replace('EMAIL:', '');
            } else if (line.startsWith('ORG:')) {
                contact.company = line.replace('ORG:', '');
            } else if (line.startsWith('ADR:')) {
                contact.address = line.replace('ADR:', '').replace(';;', '');
            } else if (line.startsWith('NOTE:')) {
                contact.notes = line.replace('NOTE:', '');
            }
        });
    } else if (data.startsWith('{')) {
        contact = JSON.parse(data);
    }
    
    if (contact.first_name && contact.phone && /^\d{10}$/.test(contact.phone)) {
        return contact;
    }
    return null;
}

function showConfirmModal(contact) {
    const modal = document.getElementById('qr-confirm-modal');
    const details = document.getElementById('qr-contact-details');
    
    details.innerHTML = `
        <p><strong>First Name:</strong> ${contact.first_name || '-'}</p>
        <p><strong>Last Name:</strong> ${contact.last_name || '-'}</p>
        <p><strong>Phone:</strong> ${contact.phone || '-'}</p>
        <p><strong>Email:</strong> ${contact.email || '-'}</p>
        <p><strong>Company:</strong> ${contact.company || '-'}</p>
        <p><strong>Address:</strong> ${contact.address || '-'}</p>
        <p><strong>Notes:</strong> ${contact.notes || '-'}</p>
    `;
    
    modal.classList.remove('hidden');
    modal.dataset.contact = JSON.stringify(contact);
}

function cancelQRConfirm() {
    document.getElementById('qr-confirm-modal').classList.add('hidden');
}

function confirmQRContact() {
    const modal = document.getElementById('qr-confirm-modal');
    const contact = JSON.parse(modal.dataset.contact);
    const form = document.getElementById('contactForm');
    
    form.querySelector('input[name="first_name"]').value = contact.first_name || '';
    form.querySelector('input[name="last_name"]').value = contact.last_name || '';
    form.querySelector('input[name="phone"]').value = contact.phone || '';
    form.querySelector('input[name="email"]').value = contact.email || '';
    form.querySelector('input[name="company"]').value = contact.company || '';
    form.querySelector('input[name="address"]').value = contact.address || '';
    form.querySelector('textarea[name="notes"]').value = contact.notes || '';
    
    modal.classList.add('hidden');
    form.submit();
}

function showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    alertDiv.innerHTML = `⚠️ ${message}`;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('avatar-input').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const label = document.querySelector('label[for="avatar-input"]');
            label.innerHTML = `
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-mediumgreen rounded-full flex items-center justify-center text-white text-2xl mb-2">✅</div>
                    <p class="text-mediumgreen font-semibold">${e.target.files[0].name}</p>
                    <p class="text-sm text-gray-500 mt-1">Click to change image</p>
                </div>
            `;
        }
    });

    document.getElementById('bulk-file-input').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const fileInfo = document.getElementById('file-info');
            const fileName = document.getElementById('file-name');
            
            fileName.textContent = e.target.files[0].name;
            fileInfo.classList.remove('hidden');
        }
    });

    document.getElementById('bulkUploadForm')?.addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<span class="mr-2">⏳</span> Processing...';
        submitBtn.disabled = true;
    });

    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('transform', 'scale-105');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('transform', 'scale-105');
        });
    });

    window.showTab = showTab;
    window.validatePhone = validatePhone;

    if (window.location.search.includes('bulk_result') && document.getElementById('bulk')) {
        showTab('bulk');
    }
});
</script>

</body>
</html>