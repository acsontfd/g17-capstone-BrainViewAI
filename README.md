# BrainView AI

BrainView AI is a web-based platform designed to assist medical professionals in classifying brain fractures in Traumatic Brain Injury (TBI) patients using **hybrid machine learning models**. The application enables users to upload CT scan images for analysis and provides insights into the necessity of surgical intervention.

## Features
- **CT Scan Analyzer:** Allows users to upload and analyze brain CT scans.
- **Patient Manager:** Manages patient data securely within the platform.
- **User Settings:** Supports profile updates and security configurations.
- **Flask API Integration:** Handles image processing and model inference.
- **Fully Local Execution:** No cloud storage, ensuring data privacy.

## Tech Stack
### Frontend
- **HTML, CSS, JavaScript** (Vanilla JS, no frameworks)
- **Fetch API** for communicating with the backend

### Backend
- **Flask** (Python-based API for handling CT scan analysis)
- **OpenCV & NumPy** for image processing
- **Scikit-learn** for hybrid ML model inference
- **SQLite** for local database storage (no cloud-based databases)

## Installation
### Prerequisites
Ensure you have the following installed:
- **Python 3.8+**
- **Flask & Dependencies**
- **SQLite** (built-in with Python)
- **XAMPP** (for serving the frontend if required)

### Backend Setup (Flask API)
1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/brainview-ai.git
   cd brainview-ai/backend
   ```
2. Create and activate a virtual environment:
   ```bash
   python -m venv venv
   source venv/bin/activate  # On Windows, use `venv\Scripts\activate`
   ```
3. Install dependencies:
   ```bash
   pip install -r requirements.txt
   ```
4. Run the Flask server:
   ```bash
   python app.py
   ```
   The API will be available at `http://127.0.0.1:5000/`

### Frontend Setup
1. Navigate to the `frontend` directory:
   ```bash
   cd ../frontend
   ```
2. Open `index.html` in a browser or use XAMPP to serve the files locally.

## API Endpoints (Flask)
| Method | Endpoint | Description |
|--------|---------|-------------|
| `POST` | `/upload` | Uploads and processes a CT scan image |
| `GET` | `/results/<id>` | Retrieves analysis results for a given scan |

## Contributing
Contributions are welcome! Feel free to fork the repo and submit pull requests.

---

