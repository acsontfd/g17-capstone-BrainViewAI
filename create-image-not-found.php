<?php
/**
 */

// Check if GD library is installed
$gd_available = function_exists('imagecreatetruecolor');

// Display warning if GD is not available
if (!$gd_available) {
    echo "<div style='background-color: #ffdddd; border-left: 6px solid #f44336; padding: 10px;'>";
    echo "<strong>Warning:</strong> PHP GD library is not installed or enabled. ";
    echo "This script requires GD to create sample images.<br>";
    echo "Please enable the GD extension in your php.ini file and restart your web server.";
    echo "</div><br>";
    
    echo "Steps to enable GD library in XAMPP:<br>";
    echo "1. Open XAMPP Control Panel<br>";
    echo "2. Click 'Config' button for Apache<br>";
    echo "3. Select 'PHP (php.ini)'<br>";
    echo "4. Find the line ';extension=gd' and remove the semicolon<br>";
    echo "5. Save the file and restart Apache<br><br>";
    
    echo "<a href='patientManager.html'>Return to Patient Manager</a>";
    exit;
}

// Create directory if it doesn't exist
if (!file_exists('assets')) {
    mkdir('assets', 0755, true);
}

// Define the image path
$imagePath = 'assets/image-not-found.png';

// Only create the image if NOT exist
if (!file_exists($imagePath)) {
    $image = imagecreatetruecolor(300, 300);
    $bgColor = imagecolorallocate($image, 240, 240, 240);
    $textColor = imagecolorallocate($image, 120, 120, 120);
    $borderColor = imagecolorallocate($image, 200, 200, 200);
    imagefill($image, 0, 0, $bgColor);
    imagerectangle($image, 0, 0, 299, 299, $borderColor);
    imageline($image, 0, 0, 299, 299, $borderColor);
    imageline($image, 0, 299, 299, 0, $borderColor);
    imagestring($image, 5, 80, 140, 'Image Not Found', $textColor);
    imagepng($image, $imagePath);
    imagedestroy($image);
    
    echo "Created fallback image at $imagePath";
} else {
    echo "Fallback image already exists at $imagePath";
}

// Create a sample CT scan for testing if NOT Exist
$sampleCTPath = 'assets/sample-ct-scan.jpg';
if (!file_exists($sampleCTPath)) {
    $ctImage = imagecreatetruecolor(512, 512);
    $black = imagecolorallocate($ctImage, 0, 0, 0);
    $gray = imagecolorallocate($ctImage, 180, 180, 180);
    $white = imagecolorallocate($ctImage, 255, 255, 255);
    imagefill($ctImage, 0, 0, $black);
    imagefilledellipse($ctImage, 256, 256, 400, 350, $gray);
    imagefilledellipse($ctImage, 200, 220, 80, 80, $white);
    imagefilledellipse($ctImage, 320, 220, 80, 80, $white);
    imagefilledellipse($ctImage, 256, 320, 120, 70, $white);
    imagestring($ctImage, 5, 140, 50, 'Sample CT Scan Image', $white);
    imagejpeg($ctImage, $sampleCTPath, 90);
    imagedestroy($ctImage);
    
    echo "<br>Created sample CT scan at $sampleCTPath";
} else {
    echo "<br>Sample CT scan already exists at $sampleCTPath";
}

// SAMPLE placeholder images for segmentation types
$segmentationTypes = [
    'sample-contour.png' => 'Contour Segmentation',
    'sample-edge.png' => 'Edge Detection',
    'sample-threshold-mask.png' => 'Threshold Mask',
    'sample-damage-overlay.png' => 'Damage Area Overlay'
];

foreach ($segmentationTypes as $filename => $label) {
    $filePath = 'assets/' . $filename;
    
    if (!file_exists($filePath)) {
        $segImg = imagecreatetruecolor(512, 512);
        $black = imagecolorallocate($segImg, 0, 0, 0);
        $white = imagecolorallocate($segImg, 255, 255, 255);
        $red = imagecolorallocate($segImg, 255, 0, 0);
        $green = imagecolorallocate($segImg, 0, 255, 0);
        imagefill($segImg, 0, 0, $black);
        switch ($label) {
            case 'Contour Segmentation':
                imageellipse($segImg, 256, 256, 400, 350, $green);
                imageellipse($segImg, 256, 256, 250, 200, $green);
                break;
                
            case 'Edge Detection':
                imagerectangle($segImg, 156, 156, 356, 356, $white);
                imagerectangle($segImg, 206, 206, 306, 306, $white);
                break;
                
            case 'Threshold Mask':
                imagefilledellipse($segImg, 256, 256, 300, 250, $white);
                imagefilledellipse($segImg, 256, 256, 100, 80, $black);
                break;
                
            case 'Damage Area Overlay':
                imagefilledarc($segImg, 256, 256, 200, 200, 45, 315, $red, IMG_ARC_PIE);
                break;
        }
        //Add LABEL on image
        imagestring($segImg, 5, 20, 20, $label . ' (Sample)', $white);
        imagepng($segImg, $filePath);
        imagedestroy($segImg);
        
        echo "<br>Created sample {$label} at {$filePath}";
    } else {
        echo "<br>Sample {$label} already exists at {$filePath}";
    }
}

echo "<br><br><a href='patientManager.html'>Return to Patient Manager</a>";
?> 