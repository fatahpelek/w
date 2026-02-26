<?php
error_reporting(0);
set_time_limit(0);

$dir = isset($_POST['dir']) ? $_POST['dir'] : __DIR__;
$deleteFile = isset($_GET['del']) ? $_GET['del'] : null;
$viewFile = isset($_GET['view']) ? $_GET['view'] : null;

$patterns = [
    'eval\s*\(',
    'base64_decode\s*\(',
    'gzinflate\s*\(',
    'gzuncompress\s*\(',
    'str_rot13\s*\(',
    'assert\s*\(',
    'preg_replace\s*\(.*\/e',
    'shell_exec\s*\(',
    'system\s*\(',
    'passthru\s*\(',
    'exec\s*\(',
    'urldecode\s*\(',
    'chr\s*\(\d+',
    '\$_(GET|POST|REQUEST|COOKIE)\s*\['
];

$results = [];

if ($deleteFile && file_exists($deleteFile)) {
    @unlink($deleteFile);
}

function scanDirRecursive($dir, $patterns, &$results) {
    $files = @scandir($dir);
    if (!$files) return;

    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;

        if (is_dir($path)) {
            scanDirRecursive($path, $patterns, $results);
        } else {
            if (preg_match('/\.(php|phtml|php5|inc)$/i', $file)) {
                $lines = @file($path);
                if (!$lines) continue;

                foreach ($lines as $num => $line) {
                    foreach ($patterns as $pat) {
                        if (preg_match("/$pat/i", $line)) {
                            $results[] = [
                                'file' => realpath($path),
                                'time' => filemtime($path),
                                'line' => $num + 1,
                                'code' => trim($line)
                            ];
                            break 2;
                        }
                    }
                }
            }
        }
    }
}

scanDirRecursive($dir, $patterns, $results);

/* sort by newest */
usort($results, function($a, $b) {
    return $b['time'] - $a['time'];
});
?>
<!DOCTYPE html>
<html>
<head>
<title>Backdoor Scanner</title>
<style>
body{background:#111;color:#eee;font-family:Arial}
input{padding:5px;width:400px}
button{padding:6px}
table{border-collapse:collapse;width:100%}
th,td{border:1px solid #444;padding:6px;vertical-align:top}
th{background:#222}
.bad{color:#ff5555}
a{color:#ff5555;text-decoration:none}
pre{white-space:pre-wrap;word-wrap:break-word}
.viewer{background:#000;border:1px solid #444;padding:10px;margin:10px 0;max-height:400px;overflow:auto}
</style>
</head>
<body>

<h2>PHP Backdoor Scanner</h2>

<form method="post">
Scan Directory:
<input type="text" name="dir" value="<?php echo htmlspecialchars($dir); ?>">
<button type="submit">SCAN</button>
</form>

<?php if ($viewFile && file_exists($viewFile)): ?>
<h3>Viewing File: <?php echo htmlspecialchars($viewFile); ?></h3>
<div class="viewer">
<pre><?php echo htmlspecialchars(file_get_contents($viewFile)); ?></pre>
</div>
<?php endif; ?>

<p>Total Suspicious Files: <span class="bad"><?php echo count($results); ?></span></p>

<table>
<tr>
<th>No</th>
<th>File Path</th>
<th>Created / Modified</th>
<th>Line</th>
<th>Code (Preview)</th>
<th>View</th>
<th>Action</th>
</tr>

<?php
$no = 1;
foreach ($results as $r) {
    echo "<tr>";
    echo "<td>".$no++."</td>";
    echo "<td>".$r['file']."</td>";
    echo "<td>".date("Y-m-d H:i:s", $r['time'])."</td>";
    echo "<td>".$r['line']."</td>";
    echo "<td><pre>".htmlspecialchars($r['code'])."</pre></td>";
    echo "<td><a href='?view=".urlencode($r['file'])."'>VIEW</a></td>";
    echo "<td><a href='?del=".urlencode($r['file'])."' onclick='return confirm(\"Delete this file?\")'>DELETE</a></td>";
    echo "</tr>";
}
?>

</table>
</body>
</html>