<?php
// Include the menu images configuration
$menuImages = include __DIR__ . '/includes/menu_images.php';

// Create the food directory if it doesn't exist
$imageDir = __DIR__ . '/assets/img/food/';
if (!is_dir($imageDir)) {
    mkdir($imageDir, 0777, true);
}

// Function to generate a clean, text-free placeholder image
function generatePlaceholderImage($filename, $text) {
    // Image dimensions (square)
    $width = 400;
    $height = 400;
    
    // Create a blank image
    $image = @imagecreatetruecolor($width, $height);
    if (!$image) {
        error_log("Failed to create image: " . print_r(error_get_last(), true));
        return false;
    }
    
    // Generate a consistent color based on the text
    $hash = md5($text);
    $r = hexdec(substr($hash, 0, 2));
    $g = hexdec(substr($hash, 2, 2));
    $b = hexdec(substr($hash, 4, 2));
    
    // Make sure the color isn't too light
    $r = max(50, min(200, $r));
    $g = max(50, min(200, $g));
    $b = max(50, min(200, $b));
    
    // Create background color
    $bgColor = imagecolorallocate($image, $r, $g, $b);
    if ($bgColor === false) {
        error_log("Failed to allocate background color");
        return false;
    }
    
    // Fill the entire image with the background color
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
    
    // Create directory if it doesn't exist
    $dir = dirname($filename);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            error_log("Failed to create directory: $dir");
            return false;
        }
    }
    
    // Save the image with maximum quality
    $result = imagejpeg($image, $filename, 100); // 100% quality
    imagedestroy($image);
    
    if (!$result) {
        error_log("Failed to save image: $filename");
        return false;
    }
    
    return true;
}

// Generate placeholders for each menu item
$successCount = 0;
$errorCount = 0;

echo "<h2>Generating Placeholder Images</h2>";
echo "<ul>";

foreach ($menuImages as $item => $filename) {
    $filepath = $imageDir . $filename;
    echo "<li>Generating: " . htmlspecialchars($item) . "... ";
    
    if (file_exists($filepath)) {
        echo "<span style='color: orange;'>Skipped (exists)</span>";
    } else {
        if (generatePlaceholderImage($filepath, $item)) {
            echo "<span style='color: green;'>Success</span>";
            $successCount++;
        } else {
            echo "<span style='color: red;'>Failed - check error log</span>";
            $errorCount++;
        }
    }
    
    echo "</li>";
}

echo "</ul>";

// Create a default placeholder if it doesn't exist
$defaultFile = $imageDir . 'default.jpg';
if (!file_exists($defaultFile)) {
    $defaultImage = imagecreatetruecolor(400, 300);
    $bgColor = imagecolorallocate($defaultImage, 200, 200, 200);
    $textColor = imagecolorallocate($defaultImage, 100, 100, 100);
    imagefill($defaultImage, 0, 0, $bgColor);
    $text = 'Image Coming Soon';
    $font = 5;
    $textWidth = imagefontwidth($font) * strlen($text);
    $x = (400 - $textWidth) / 2;
    $y = (300 - imagefontheight($font)) / 2;
    imagestring($defaultImage, $font, $x, $y, $text, $textColor);
    imagejpeg($defaultImage, $defaultFile, 90);
    imagedestroy($defaultImage);
}

// Show summary
echo "<h3>Summary</h3>";
echo "<p>Generated: $successCount placeholder images</p>";
if ($errorCount > 0) {
    echo "<p style='color: red;'>Failed to generate: $errorCount images (check error log for details)</p>";
}

// Check if GD is installed
if (!extension_loaded('gd')) {
    echo "<div style='background: #ffdddd; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<strong>Warning:</strong> GD extension is not enabled. Please enable the GD extension in your php.ini file.";
    echo "</div>";
}

echo "<div style='margin-top: 20px; padding: 10px; background: #f0f0f0;'>";
echo "<a href='menu.php' class='btn'>View Menu</a> ";
echo "<a href='index.php' class='btn'>Go to Home</a>";
echo "</div>";

// For security, you might want to delete this file after use
// Uncomment the following line to automatically delete this file after generation
// if (file_exists(__FILE__)) {
//     unlink(__FILE__);
// }
// unlink(__FILE__);
?>
