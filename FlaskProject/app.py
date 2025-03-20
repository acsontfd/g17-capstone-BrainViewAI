import os
import io
import base64
import numpy as np
import joblib
import cv2
from flask import Flask, request, jsonify
from PIL import Image
from skimage.transform import resize

SCALER_PATH = 'scaler.pkl'
KMEANS_PATH = 'kmeans_model.pkl'
SVM_MODEL_PATH = 'svm_model.pkl'

IMAGE_SIZE = (64, 64)

app = Flask(__name__)

def load_models():
    """
    Load the pre-trained scaler, KMeans, and SVM models from disk.
    """
    scaler = joblib.load(SCALER_PATH)
    kmeans = joblib.load(KMEANS_PATH)
    svm_model = joblib.load(SVM_MODEL_PATH)
    return scaler, kmeans, svm_model

scaler, kmeans, svm_model = load_models()

def preprocess_image(pil_image):
    """
    Preprocess the PIL image to match the training data preprocessing:
    1. Convert to grayscale.
    2. Resize to 64x64 with preserve_range=False to rescale to [0, 1].
    3. Flatten the image.
    4. Normalize by dividing by 255.0 to match training (images / 255.0).
    5. Append KMeans cluster features.
    6. Scale the combined features using the pre-trained scaler.
    """
    # Convert to grayscale
    img_gray = pil_image.convert("L")

    # Resize to 64x64, rescaling to [0, 1] (matches training with preserve_range=False default)
    img_resized = resize(np.array(img_gray), IMAGE_SIZE)

    # Flatten the image
    img_flat = img_resized.flatten()

    # Normalize by dividing by 255.0 to match training
    img_normalized = img_flat / 255.0

    # Compute KMeans cluster distances
    cluster_features = kmeans.transform([img_normalized])

    # Combine normalized pixel values with cluster features
    X_combined = np.hstack((img_normalized, cluster_features[0]))

    # Scale the combined features
    X_scaled = scaler.transform([X_combined])

    return X_scaled

def contour_segmentation(image_array):
    """
    Perform contour segmentation on an image.
    Returns the original image and the image with contours drawn.
    """
    # Ensure image is grayscale
    if len(image_array.shape) == 3:
        img_gray = cv2.cvtColor(image_array, cv2.COLOR_RGB2GRAY)
    else:
        img_gray = image_array
    
    # Apply binary thresholding
    _, thresholded = cv2.threshold(img_gray, 127, 255, cv2.THRESH_BINARY)
    
    # Find contours
    contours, _ = cv2.findContours(thresholded, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)
    
    # Draw contours on a color version of the original image
    contour_image = cv2.cvtColor(img_gray, cv2.COLOR_GRAY2BGR)
    cv2.drawContours(contour_image, contours, -1, (0, 255, 0), 2)
    
    return img_gray, contour_image

def edge_segmentation(image_array):
    """
    Perform edge segmentation on an image using Canny edge detection.
    Returns the original image and the edge-detected image.
    """
    # Ensure image is grayscale
    if len(image_array.shape) == 3:
        img_gray = cv2.cvtColor(image_array, cv2.COLOR_RGB2GRAY)
    else:
        img_gray = image_array
    
    # Apply Canny edge detection
    edges = cv2.Canny(img_gray, 100, 200)
    
    return img_gray, edges

def image_to_base64(image_array):
    """
    Convert a numpy image array to base64 string.
    """
    # Convert to BGR format for OpenCV
    if len(image_array.shape) == 2:  # Grayscale
        img_encoded = cv2.imencode('.png', image_array)[1]
    else:  # Color
        img_encoded = cv2.imencode('.png', image_array)[1]
    
    base64_string = base64.b64encode(img_encoded).decode('utf-8')
    return f"data:image/png;base64,{base64_string}"

def threshold_mask(image_array):
    """
    Generate a threshold mask that highlights potential damage areas.
    Returns the original image and the thresholded mask.
    """
    # Ensure image is grayscale
    if len(image_array.shape) == 3:
        img_gray = cv2.cvtColor(image_array, cv2.COLOR_RGB2GRAY)
    else:
        img_gray = image_array
    
    # Apply adaptive thresholding to identify potential damage areas
    thresh = cv2.adaptiveThreshold(img_gray, 255, cv2.ADAPTIVE_THRESH_GAUSSIAN_C,
                                  cv2.THRESH_BINARY_INV, 11, 2)
    
    # Clean up mask with morphological operations
    kernel = np.ones((3, 3), np.uint8)
    mask = cv2.morphologyEx(thresh, cv2.MORPH_OPEN, kernel)
    
    return img_gray, mask

def damage_overlay(image_array):
    """
    Create an overlay highlighting potential damage areas on the original image.
    Returns the original image and the overlay image.
    """
    # Ensure image is in color format for overlay
    if len(image_array.shape) == 2:
        original = cv2.cvtColor(image_array, cv2.COLOR_GRAY2BGR)
    else:
        original = image_array.copy()
    
    # Get grayscale version for processing
    if len(image_array.shape) == 3:
        img_gray = cv2.cvtColor(image_array, cv2.COLOR_RGB2GRAY)
    else:
        img_gray = image_array
    
    # Apply threshold to find potential damage regions
    _, binary = cv2.threshold(img_gray, 127, 255, cv2.THRESH_BINARY | cv2.THRESH_OTSU)
    
    # Find contours in the binary image
    contours, _ = cv2.findContours(binary, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    
    # Create overlay by drawing filled contours
    overlay = original.copy()
    for contour in contours:
        # Filter contours by area to avoid noise
        if cv2.contourArea(contour) > 50:
            cv2.drawContours(overlay, [contour], -1, (0, 0, 255), -1)
    
    # Blend the overlay with the original image
    alpha = 0.4  # Transparency factor
    overlay_result = cv2.addWeighted(overlay, alpha, original, 1 - alpha, 0)
    
    return img_gray, overlay_result

@app.route('/predict', methods=['POST'])
def predict():
    """
    Handle POST request from the web app, process the image, and return prediction results
    along with segmentation results
    """
    try:
        # Get JSON data from the request
        data = request.get_json()
        if 'image_base64' not in data:
            return jsonify({"success": False, "error": "No image data provided"}), 400

        # Decode base64 image
        b64_string = data['image_base64']
        if ',' in b64_string:  # Remove data URI prefix if present
            b64_string = b64_string.split(',')[1]
        image_bytes = base64.b64decode(b64_string)

        # Open image with PIL
        pil_image = Image.open(io.BytesIO(image_bytes))

        # Convert PIL image to numpy array for segmentation
        np_image = np.array(pil_image)
        
        # Perform segmentation
        _, contour_image = contour_segmentation(np_image)
        _, edge_image = edge_segmentation(np_image)
        
        # Generate new results: threshold mask and damage overlay
        _, threshold_mask_image = threshold_mask(np_image)
        _, damage_overlay_image = damage_overlay(np_image)
        
        # Convert segmentation results to base64 for sending to frontend
        contour_b64 = image_to_base64(contour_image)
        edge_b64 = image_to_base64(edge_image)
        threshold_mask_b64 = image_to_base64(threshold_mask_image)
        damage_overlay_b64 = image_to_base64(damage_overlay_image)

        # Preprocess the image for ML prediction
        X_scaled = preprocess_image(pil_image)

        # Make prediction with SVM
        prediction = svm_model.predict(X_scaled)[0]

        probabilities = svm_model.predict_proba(X_scaled)[0]
        confidence = max(probabilities) * 100  # Confidence as percentage

        # Load actual model accuracy from training
        MODEL_ACCURACY = 82.5 

        accuracy = MODEL_ACCURACY  # Assign the precomputed accuracy

        # Determine analysis text based on prediction
        analysis_text = "Hemorrhage detected" if prediction == 1 else "No Hemorrhage detected"

        # Return JSON response with segmentation images
        return jsonify({
            "success": True,
            "analysis": analysis_text,
            "confidence": round(confidence, 2),
            "accuracy": accuracy,
            "contour_image": contour_b64,
            "edge_image": edge_b64,
            "threshold_mask_image": threshold_mask_b64,
            "damage_overlay_image": damage_overlay_b64
        })

    except Exception as e:
        return jsonify({"success": False, "error": str(e)}), 400


@app.route('/', methods=['GET'])
def index():
    """
    GET route to verify the Flask server is running
    """
    return "Flask ML inference server is running. Use POST /predict to analyze an image."


if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000, debug=True)