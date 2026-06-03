<?php
declare(strict_types=1);
ini_set("display_errors", "1");
error_reporting(E_ALL);

$envPath = __DIR__ . "/../.env";
if (file_exists($envPath)){
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach($lines as $line){
        if($line[0] === "#"){
            continue;
        }
        [$key, $value] = explode("=", $line, 2);
        $key = trim($key);
        $value = trim($value);
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }

}