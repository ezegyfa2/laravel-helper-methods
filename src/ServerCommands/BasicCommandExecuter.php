<?php

$commandName = 'git_pull';
$message = file_get_contents('http://127.0.0.1:8222/dynamic_web/command.php?command=' . $commandName);
echo $message;

?>

<html>
 
<head>

</head>
 
<body>

</body>
 
</html>
