# BrainView AI

BrainView AI is a web-based platform designed for classifying brain fractures in Traumatic Brain Injury (TBI) patients using a Hybrid Machine Learning Model. The system allows medical professionals to upload CT scans for automated analysis and provides recommendations based on the Glasgow Coma Scale (GCS) and other factors.

## Features

- **CT Scan Analysis**: Upload and classify CT scans to detect brain fractures.
- **User Management**: Manage users and roles (doctors, surgeons, medical professionals).
- **Patient Manager**: Maintain patient records and analysis history.
- **Interactive UI**: User-friendly interface for seamless navigation.
- **Flask API Backend**: Handles ML model inference and data processing.
- **Machine Learning Model**: SVM-based classification for brain fractures.

---

## 1. Frontend

The frontend of BrainView AI is built using HTML, CSS, and JavaScript. It provides an intuitive interface for doctors and medical professionals to:

- Upload CT scan images
- View classification results
- Manage patient records
- Adjust user settings
- Contact administrators for support

### Tech Stack
- **HTML5** & **CSS3**: Structuring and styling the UI.
- **JavaScript (Vanilla JS)**: Dynamic functionality.
- **Bootstrap** (optional for UI components).
- **Fetch API**: Communicates with the Flask backend.

### UI Components
- **Navigation Bar**: Provides access to core features (CT Scan Analyzer, Patient Manager, User Settings, Help).
- **CT Scan Uploader**: Users can upload images for classification.
- **Result Display**: Shows classification confidence and recommendations.
- **Settings Page**: Allows customization of user preferences.

---

## 2. Backend (Flask API)

The Flask API serves as the backend for handling image uploads, processing machine learning inferences, and managing authentication.

---

## 3. Machine Learning Model

### Workflow
1. **Preprocessing**: Images are resized and converted to grayscale.
2. **Feature Extraction**: Extracts relevant features using OpenCV and NumPy.
3. **Classification**: Uses an SVM model for fracture classification.
4. **Inference**: Returns classification results with a confidence score.

---

### Directory Structure
```
BrainViewAI/
├── static/
│   ├── css/
│   │   ├── styles.css
│   ├── js/
│   │   ├── app.js
├── templates/
│   ├── index.html
│   ├── settings.html
├── app.py  # Flask API
├── model.pkl  # Trained ML model
├── README.md
```

---

## Future Enhancements
- **Improved ML Model**: Incorporate deep learning models for better accuracy.
- **User Authentication**: Secure login with JWT authentication.
- **Database Integration**: Store patient records using SQLite or PostgreSQL.

---

## Contributors
- **Tia'a Faang Der** and team

BrainView AI is an ongoing research project aimed at improving TBI diagnosis using AI.

