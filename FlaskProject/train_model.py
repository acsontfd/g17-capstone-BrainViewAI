import os
import pandas as pd
import numpy as np
from PIL import Image
from skimage.transform import resize
from sklearn.cluster import KMeans
from sklearn.svm import SVC
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import classification_report, accuracy_score, confusion_matrix, ConfusionMatrixDisplay
import matplotlib.pyplot as plt
import joblib

# Define file paths (adjust these paths as needed)
IMAGE_DIR = r"C:\Users\Harrel\PycharmProjects\FlaskProject\head_ct\head_ct"
CSV_FILE = r"C:\Users\Harrel\PycharmProjects\FlaskProject\labels.csv"

def load_data(image_dir, csv_file, image_size=(64, 64), padding_length=3):

    # Load label CSV and pad image ids for consistency
    label_df = pd.read_csv(csv_file)
    label_df["padded_id"] = label_df["id"].apply(lambda x: str(x).zfill(padding_length))
    image_label_mapping = dict(zip(label_df["padded_id"], label_df["hemorrhage"]))

    images = []
    labels = []
    filenames = []

    # Iterate over image files
    for image_file in os.listdir(image_dir):
        image_id = os.path.splitext(image_file)[0]
        if image_id in image_label_mapping:
            image_path = os.path.join(image_dir, image_file)
            try:
                img = Image.open(image_path).convert("L")  # Convert to grayscale
                img_resized = resize(np.array(img), image_size)  # Resize
                images.append(img_resized.flatten())  # Flatten the image
                labels.append(image_label_mapping[image_id])
                filenames.append(image_file)
            except Exception as e:
                print(f"Error processing {image_file}: {e}")

    return np.array(images), np.array(labels), filenames

def main():
    # Parameters
    image_size = (64, 64)
    num_clusters = 2
    test_size = 0.2
    random_state = 42

    # Load and preprocess images
    images, labels, image_names = load_data(IMAGE_DIR, CSV_FILE, image_size)
    images_normalized = images / 255.0

    # Apply K-means clustering to generate additional features
    kmeans = KMeans(n_clusters=num_clusters, random_state=random_state)
    cluster_features = kmeans.fit_transform(images_normalized)

    # Combine normalized pixel data with cluster distances
    X_combined = np.hstack((images_normalized, cluster_features))

    # Split the data into training and test sets
    X_train, X_test, y_train, y_test = train_test_split(X_combined, labels, test_size=test_size, random_state=random_state)

    # Scale features
    scaler = StandardScaler()
    X_train_scaled = scaler.fit_transform(X_train)
    X_test_scaled = scaler.transform(X_test)

    # Train the SVM model with probability=True
    svm_model = SVC(kernel='linear', probability=True, random_state=random_state)
    svm_model.fit(X_train_scaled, y_train)

    # Evaluate the model
    y_pred = svm_model.predict(X_test_scaled)
    accuracy = accuracy_score(y_test, y_pred)  # Store accuracy for reference
    print("Classification Report:")
    print(classification_report(y_test, y_pred))
    print("Accuracy Score:", accuracy)

    # (Optional) Display confusion matrix
    cm = confusion_matrix(y_test, y_pred)
    disp = ConfusionMatrixDisplay(confusion_matrix=cm, display_labels=['No Hemorrhage', 'Hemorrhage'])
    disp.plot(cmap="viridis")
    plt.show()

    # Save the scaler, K-means model, and SVM model for deployment
    joblib.dump(scaler, 'scaler.pkl')
    joblib.dump(kmeans, 'kmeans_model.pkl')
    joblib.dump(svm_model, 'svm_model.pkl')
    print("Models saved successfully.")

if __name__ == "__main__":
    main()