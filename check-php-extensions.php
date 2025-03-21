<?php
/**
 * PHP Extension Checker
 * 
 * This script checks if necessary PHP extensions are installed 
 * and provides guidance on how to enable them.
 */

// Set header to plain text for easy reading
header('Content-Type: text/html; charset=utf-8');

echo "<html><head>";
echo "<title>PHP Extension Checker</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .extension { margin-bottom: 10px; padding: 10px; border-radius: 5px; }
    .installed { background-color: #dff0d8; border-left: 5px solid #3c763d; }
    .missing { background-color: #f2dede; border-left: 5px solid #a94442; }
    .warning { background-color: #fcf8e3; border-left: 5px solid #8a6d3b; }
    h1 { color: #333; }
    code { background-color: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
    .steps { margin-left: 20px; }
</style>";
echo "</head><body>";

echo "<h1>PHP Extension Checker for BrainView AI</h1>";

// Check PHP version
echo "<div class='extension " . (version_compare(PHP_VERSION, '7.0.0') >= 0 ? 'installed' : 'missing') . "'>";
echo "<strong>PHP Version:</strong> " . PHP_VERSION;
echo version_compare(PHP_VERSION, '7.0.0') >= 0 
    ? " ✓" 
    : " ✗ (PHP 7.0 or higher recommended)";
echo "</div>";

// Check for GD extension
echo "<div class='extension " . (extension_loaded('gd') ? 'installed' : 'missing') . "'>";
echo "<strong>GD Library:</strong> " . (extension_loaded('gd') ? "Installed ✓" : "Not installed ✗");
if (!extension_loaded('gd')) {
    echo "<p>The GD extension is required for image processing in BrainView AI.</p>";
    echo "<p><strong>How to enable GD in XAMPP:</strong></p>";
    echo "<ol class='steps'>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Click the 'Config' button for Apache</li>";
    echo "<li>Select 'PHP (php.ini)'</li>";
    echo "<li>Find the line <code>;extension=gd</code> and remove the semicolon</li>";
    echo "<li>Save the file</li>";
    echo "<li>Restart Apache</li>";
    echo "</ol>";
}
if (extension_loaded('gd')) {
    $gdInfo = gd_info();
    echo "<p>GD Version: " . (isset($gdInfo['GD Version']) ? $gdInfo['GD Version'] : 'Unknown') . "</p>";
}
echo "</div>";

// Check for MySQLi extension
echo "<div class='extension " . (extension_loaded('mysqli') ? 'installed' : 'missing') . "'>";
echo "<strong>MySQLi:</strong> " . (extension_loaded('mysqli') ? "Installed ✓" : "Not installed ✗");
if (!extension_loaded('mysqli')) {
    echo "<p>The MySQLi extension is required for database connectivity.</p>";
}
echo "</div>";

// Check for curl extension
echo "<div class='extension " . (extension_loaded('curl') ? 'installed' : 'missing') . "'>";
echo "<strong>cURL:</strong> " . (extension_loaded('curl') ? "Installed ✓" : "Not installed ✗");
if (!extension_loaded('curl')) {
    echo "<p>The cURL extension is used for API communication.</p>";
}
echo "</div>";

// Check for json extension
echo "<div class='extension " . (extension_loaded('json') ? 'installed' : 'missing') . "'>";
echo "<strong>JSON:</strong> " . (extension_loaded('json') ? "Installed ✓" : "Not installed ✗");
if (!extension_loaded('json')) {
    echo "<p>The JSON extension is required for data interchange.</p>";
}
echo "</div>";

// Display configuration recommendations
echo "<h2>Additional Information</h2>";
echo "<div class='extension warning'>";
echo "<p>Make sure these settings are configured in your php.ini file:</p>";
echo "<ul>";
echo "<li><code>upload_max_filesize = 10M</code> (or higher)</li>";
echo "<li><code>post_max_size = 10M</code> (or higher)</li>";
echo "<li><code>memory_limit = 128M</code> (or higher)</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='patientManager.html'>Return to Patient Manager</a></p>";
echo "</body></html>";
?> 