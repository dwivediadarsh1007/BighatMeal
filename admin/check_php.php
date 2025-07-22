<?php
// Simple PHP test
echo "<h1>PHP is working!</h1>";

echo "<h2>PHP Version: " . phpversion() . "</h2>";

echo "<h3>PHP Info:</h3>
<ul>
    <li>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</li>
    <li>PHP Handler: " . php_sapi_name() . "</li>
</ul>";
?>
