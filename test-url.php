<?php
echo "<h1>URL Test</h1>";
echo "<h3>Server Variables:</h3>";
echo "<pre>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Not Set') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not Set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'Not Set') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'Not Set') . "\n";

// Test form
echo "</pre><h3>Test Form:</h3>";
echo '<form method="POST" action="">';
echo '<input type="hidden" name="test" value="1">';
echo '<button type="submit" class="btn btn-primary">Test Form Submission</button>';
echo '</form>';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Form Submitted Successfully!</h3>";
    echo "<p>If you can see this message, form submission is working.</p>";
}
?>
