import os
import io
import base64
import numpy as np
import joblib
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


@app.route('/predict', methods=['POST'])
def predict():
    """
    Handle POST request from the web app, process the image, and return prediction results
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

        # Preprocess the image
        X_scaled = preprocess_image(pil_image)

        # Make prediction with SVM
        prediction = svm_model.predict(X_scaled)[0]

        # Get probability estimates for confidence
        probabilities = svm_model.predict_proba(X_scaled)[0]
        confidence = max(probabilities) * 100  # Confidence as percentage

        print(probabilities, confidence) # For test

        # Fixed accuracy value (Not sure the difference from accuracy, the same now)
        accuracy = round(confidence, 2) # Just Placeholder

        # Determine analysis text based on prediction
        analysis_text = "Hemorrhage detected" if prediction == 1 else "No Hemorrhage detected"

        # Return JSON response
        return jsonify({
            "success": True,
            "analysis": analysis_text,
            "confidence": round(confidence, 2),
            "accuracy": accuracy
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