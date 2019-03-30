<?php
$uri = getCurrentUri();
$namesFile = 'names.txt';
$lines = file($namesFile);
$dataLineNumber = strlen($uri) > 0 ? decode($uri) : 0;
if ($dataLineNumber >= sizeof($lines) || $dataLineNumber < 0)
    $dataLineNumber = 0;

$personData = explode(" ", $lines[$dataLineNumber]);

function encode($number)
{
    return strtr(rtrim(base64_encode(pack('i', $number)), '='), '+/', '-_');
}

function decode($base64)
{
    $number = unpack('i', base64_decode(str_pad(strtr($base64, '-_', '+/'), strlen($base64) % 4, '=')));
    return $number[1];
}

function getCurrentUri()
{
    $basepath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
    $uri = substr($_SERVER['REQUEST_URI'], strlen($basepath));
    if (strstr($uri, '?')) $uri = substr($uri, 0, strpos($uri, '?'));
    $uri = trim($uri, '/');
    return $uri;
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Make A Wish To Me :)</title>
    <link rel="stylesheet" type="text/css" href="stylesheet.css">
</head>
<body>
<div class="wrapper">
    <div class="container">
        <h1>Make a wish For <? echo $personData[0]; ?></h1>

        <form class="form">
            <input type="text" placeholder="Username">
            <input type="password" placeholder="Password">
            <button type="submit" id="login-button">Login</button>
        </form>
    </div>
</div>

</body>
</html>