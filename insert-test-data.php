<?php
/**
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if authenticated
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to run this script. Please login first.");
}

$user_id = $_SESSION['user_id'];

// Check if GD library is installed
$gd_available = function_exists('imagecreatetruecolor');

try {
    require_once 'db-connection.php';
    $db = new Database();
    $db->conn->begin_transaction();
    $stmt = $db->conn->prepare("SELECT COUNT(*) as count FROM ct_scans WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo "Test data already exists for this user (found {$row['count']} CT scans).<br>";
        $stmt->close();
        $db->conn->commit();
        $db->closeConnection();
        exit;
    }
    $imagePath = 'assets/sample-ct-scan.jpg';
    if (!file_exists($imagePath)) {
        if ($gd_available) {
            $img = imagecreatetruecolor(512, 512);
            $bgColor = imagecolorallocate($img, 240, 240, 240);
            imagefill($img, 0, 0, $bgColor);
            $textColor = imagecolorallocate($img, 50, 50, 50);
            imagestring($img, 5, 160, 240, 'Sample CT Scan', $textColor);
            //BUFFER HERE
            ob_start();
            imagejpeg($img, null, 90);
            $imageData = ob_get_contents();
            ob_end_clean();
            imagedestroy($img);
        } else {
            $imageData = "This is a placeholder for an image. GD library is not available.";
            echo "Warning: GD library is not installed. Using text placeholders instead of images.<br>";
        }
    } else {
        $imageData = file_get_contents($imagePath);
    }
    
    // Insert CT scanss
    $stmt = $db->conn->prepare("INSERT INTO ct_scans (user_id, image_data, image_name, upload_date) VALUES (?, ?, ?, NOW())");
    $imageName = "test_scan_" . date('Ymd_His') . ".jpg";
    $stmt->bind_param("sss", $user_id, $imageData, $imageName);
    $stmt->send_long_data(1, $imageData);
    $stmt->execute();
    $ctScanId = $db->conn->insert_id;
    $stmt->close();
    
    echo "Created test CT scan with ID: $ctScanId<br>";
    $patients = [
        'TEST001' => 'Hemorrhage detected',
        'TEST002' => 'No Hemorrhage detected'
    ];
    
    foreach ($patients as $patientId => $classification) {
        if ($gd_available) {
            $contourImg = imagecreatetruecolor(512, 512);
            $bgColor = imagecolorallocate($contourImg, 0, 0, 0);
            imagefill($contourImg, 0, 0, $bgColor);
            $contourColor = imagecolorallocate($contourImg, 0, 255, 0);
            imageellipse($contourImg, 256, 256, 200, 200, $contourColor);
            
            ob_start();
            imagepng($contourImg);
            $contourData = ob_get_contents();
            ob_end_clean();
            imagedestroy($contourImg);

            $edgeImg = imagecreatetruecolor(512, 512);
            $bgColor = imagecolorallocate($edgeImg, 0, 0, 0);
            imagefill($edgeImg, 0, 0, $bgColor);
            $edgeColor = imagecolorallocate($edgeImg, 255, 255, 255);
            imagerectangle($edgeImg, 156, 156, 356, 356, $edgeColor);
            
            ob_start();
            imagepng($edgeImg);
            $edgeData = ob_get_contents();
            ob_end_clean();
            imagedestroy($edgeImg);

            $thresholdImg = imagecreatetruecolor(512, 512);
            $bgColor = imagecolorallocate($thresholdImg, 0, 0, 0);
            imagefill($thresholdImg, 0, 0, $bgColor);
            $maskColor = imagecolorallocate($thresholdImg, 255, 255, 255);
            imagefilledellipse($thresholdImg, 256, 256, 180, 180, $maskColor);
            
            ob_start();
            imagepng($thresholdImg);
            $thresholdData = ob_get_contents();
            ob_end_clean();
            imagedestroy($thresholdImg);

            $overlayImg = imagecreatetruecolor(512, 512);
            $bgColor = imagecolorallocate($overlayImg, 10, 10, 10);
            imagefill($overlayImg, 0, 0, $bgColor);
            $overlayColor = imagecolorallocate($overlayImg, 255, 0, 0);
            imagefilledarc($overlayImg, 256, 256, 200, 200, 45, 315, $overlayColor, IMG_ARC_PIE);
            
            ob_start();
            imagepng($overlayImg);
            $overlayData = ob_get_contents();
            ob_end_clean();
            imagedestroy($overlayImg);
        } else {
            // GD library not available, use simple placeholder data
            $contourData = "Contour image placeholder";
            $edgeData = "Edge image placeholder";
            $thresholdData = "Threshold mask placeholder";
            $overlayData = "Damage overlay placeholder";
        }
        
        // Confidence and accuracy values
        $confidence = ($classification == 'Hemorrhage detected') ? 85.7 : 92.3;
        $accuracy = round(confidence, 2);
        
        // Insert analysis result
        $stmt = $db->conn->prepare("INSERT INTO analysis_results 
            (ct_scan_id, patient_id, classification, confidence, accuracy, contour_image, edge_image, threshold_mask_image, damage_overlay_image, analysis_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $null1 = NULL; // For contour_image
        $null2 = NULL; // For edge_image
        $null3 = NULL; // For threshold_mask_image
        $null4 = NULL; // For damage_overlay_image
        
        $stmt->bind_param("issddbbbb", $ctScanId, $patientId, $classification, $confidence, $accuracy, $null1, $null2, $null3, $null4);
        $stmt->send_long_data(5, $contourData);
        $stmt->send_long_data(6, $edgeData);
        $stmt->send_long_data(7, $thresholdData);
        $stmt->send_long_data(8, $overlayData);
        $stmt->execute();
        
        $analysisId = $db->conn->insert_id;
        $stmt->close();
        
        echo "Created test analysis result for patient $patientId with ID: $analysisId<br>";
    }
    $db->conn->commit();
    $db->closeConnection();
    
    echo "<br>Test data creation completed successfully!<br>";
    echo "You can now go to the <a href='patientManager.html'>Patient Manager</a> to view the test data.";
    
} catch (Exception $e) {
//Rollback error
    if (isset($db) && $db->conn) {
        $db->conn->rollback();
        $db->closeConnection();
    }
    
    echo "Error creating test data: " . $e->getMessage();
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?> 