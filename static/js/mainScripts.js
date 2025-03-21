document.addEventListener("DOMContentLoaded", () => {
    const appContainer = document.getElementById("app-container");
  
  
    // Initially, hide the main content
    appContainer.style.display = "none";
  
    const checkAuth = async () => {
        try {
            const response = await fetch('check-auth.php');
            const data = await response.json();
  
            if (!data.authenticated) {
                console.log("User not authenticated. Redirecting to login page...");
                alert("Unauthorized access. Please login.");
                window.location.href = "login.html";
            } else {
                console.log("User authenticated:", data.user_id);
                const userId = data.user_id;
                const nameParts = userId.split(" ");
                const initials = nameParts.map(part => part[0].toUpperCase()).join("");
  
                // If you have a real user name, replace this with the actual name
                document.querySelector(".user-name").textContent = userId;
                document.querySelector(".user-avatar").textContent = initials;
                appContainer.style.display = "block";
            }
        } catch (error) {
            console.error("Error during authentication check:", error);
            alert("Unauthorized access. Please login.");
            window.location.href = "login.html";
        }
    };
  
    checkAuth();
  
    // User dropdown logic
    const userDropdownBtn = document.getElementById("user-dropdown-btn");
    const userDropdownMenu = document.getElementById("user-dropdown-menu");
    const logoutLink = document.getElementById("logout-link");
  
    userDropdownBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        userDropdownMenu.classList.toggle("show");
    });
  
    window.addEventListener("click", () => {
        userDropdownMenu.classList.remove("show");
    });
  
    // Logout functionality
    if (logoutLink) {
        logoutLink.addEventListener("click", (e) => {
            e.preventDefault();
  
            const confirmation = confirm("Are you sure you want to log out?");
            if (confirmation) {
                localStorage.removeItem("user");
                window.location.href = "logout.php"; // Redirect to logout handler
            }
        });
    }
  
    // Mask logic
    const mask = document.createElement("div");
    mask.className = "mask";
    document.body.appendChild(mask);
  
    function showMask() {
        mask.classList.add("show");
    }
  
    function hideMask() {
        mask.classList.remove("show");
    }
  
    // Upload functionality
    const uploadBtn = document.getElementById("upload-btn");
    const uploadInput = document.getElementById("upload-input");
    const analyzeBtn = document.getElementById("analyze-btn");
    const imagePreview = document.getElementById("image-preview");
  
    uploadBtn.addEventListener("click", () => uploadInput.click());
  
    uploadInput.addEventListener("change", async (e) => {
        const file = e.target.files[0];
        if (file) {
            uploadBtn.disabled = true;
            uploadBtn.textContent = "Uploading...";
  
            const reader = new FileReader();
            reader.onload = (event) => {
                imagePreview.innerHTML = `<img src="${event.target.result}" alt="Uploaded CT Scan" class="uploaded-image">`;
            };
            reader.readAsDataURL(file);
  
            try {
                const formData = new FormData();
                formData.append('image', file);
  
                const response = await fetch('upload-image.php', { method: 'POST', body: formData });
  
                const result = await response.json();
                if (result.success) {
                    // Set the CT scan ID in the hidden field
                    document.getElementById('ct-scan-id').value = result.image_id;
                    analyzeBtn.disabled = false;
                } else {
                    alert('Upload failed: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Upload error:', error);
                alert('Upload failed. Please try again.');
            } finally {
                uploadBtn.disabled = false;
                uploadBtn.textContent = "Upload CT Scan";
            }
        }
    });
  
      analyzeBtn.addEventListener("click", async () => {
          analyzeBtn.classList.add('analyzing');
          analyzeBtn.disabled = true;
  
          const progressContainer = document.createElement("div");
          progressContainer.className = "progress-container";
          progressContainer.innerHTML = `
          <div class="progress-bar">
              <div class="progress-bar-fill"></div>
          </div>
          <div class="analysis-text">Analyzing CT Scan...</div>
      `;
          imagePreview.insertAdjacentElement('afterend', progressContainer);
          progressContainer.style.display = "block";
  
          setTimeout(() => {
              const progressBarFill = progressContainer.querySelector('.progress-bar-fill');
              progressBarFill.style.width = '100%';
          }, 100);
  
          try {
              const imageElement = document.querySelector('.uploaded-image');
              if (!imageElement) {
                  throw new Error('No image found to analyze');
              }
  
              await new Promise(resolve => setTimeout(resolve, 1500));
              const patientSelect = document.getElementById('patient-select');
              let patientId;
              
              if (patientSelect && patientSelect.value) {
                  patientId = patientSelect.value;
              } else {
                  // If no patient select exists, prompt for patient ID
                  const patientPrompt = prompt('Please enter the patient ID (e.g., PT1):');
                  if (!patientPrompt) {
                      throw new Error('Patient ID is required for analysis');
                  }
                  patientId = patientPrompt;
              }
  
              let ctScanId;
  
              const ctScanIdElement = document.getElementById('ct-scan-id');
              if (ctScanIdElement) {
                  ctScanId = ctScanIdElement.value;
              } else {
                  const ctScanIdFromStorage = localStorage.getItem('last_ct_scan_id');
                  
                  if (ctScanIdFromStorage) {
                      ctScanId = ctScanIdFromStorage;
                  } else {
                      const ctScanIdPrompt = prompt('Please enter the CT scan ID:');
                      if (!ctScanIdPrompt) {
                          throw new Error('CT Scan ID is required for analysis');
                      }
                      ctScanId = ctScanIdPrompt;
                  }
              }
  
              if (!ctScanId) {
                  throw new Error('CT Scan ID not found');
              }
  
              const imageSource = imageElement.src;
              const response = await fetch('analyze-scan.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify({ 
                      image: imageSource,
                      ctScanId: ctScanId,
                      patientId: patientId
                  })
              });
  
              if (!response.ok) {
                  const textResponse = await response.text();
                  console.error('Server response:', textResponse);
                  throw new Error(`Server returned ${response.status}: ${response.statusText}`);
              }
  
              const result = await response.json();
              if (!result.success) {
                  throw new Error(result.error || 'Analysis failed');
              }
  
              showMask();
              const resultPopup = document.createElement("div");
              resultPopup.classList.add("popup", "segmentation-popup");
              resultPopup.innerHTML = `
              <div class="popup-content">
                  <h3>Analysis Results</h3>
                  <p>Classification: ${result.analysis || 'N/A'}</p>
                  <p>Confidence Level: ${result.confidence || 'N/A'}%</p>
                  <p>Model Accuracy: ${result.accuracy || 'N/A'}%</p>
                  <p>Patient ID: ${patientId}</p>
                  
                  <div class="segmentation-images">
                      <div class="segmentation-image">
                          <h4>Contour Highlighting</h4>
                          <img src="${result.contour_image}" alt="Contour Segmentation" class="seg-img">
                      </div>
                      <div class="segmentation-image">
                          <h4>Edge Detection</h4>
                          <img src="${result.edge_image}" alt="Edge Segmentation" class="seg-img">
                      </div>
                      <div class="segmentation-image">
                          <h4>Threshold Mask</h4>
                          <img src="${result.threshold_mask_image}" alt="Threshold Mask" class="seg-img">
                      </div>
                      <div class="segmentation-image">
                          <h4>Damage Area Overlay</h4>
                          <img src="${result.damage_overlay_image}" alt="Damage Overlay" class="seg-img">
                      </div>
                  </div>
                  
                  <button id="popup-close-btn">View Detailed Report in Patient Manager</button>
              </div>
          `;
              document.body.appendChild(resultPopup);
              void resultPopup.offsetWidth;
              resultPopup.classList.add('show');
  
              document.getElementById("popup-close-btn").addEventListener("click", () => {
                  resultPopup.classList.remove('show');
                  setTimeout(() => {
                      resultPopup.remove();
                      hideMask();
                      window.location.href = "patientManager.html";
                  }, 600);
              });
  
          } catch (error) {
              console.error('Analysis error:', error);
              alert('Analysis failed: ' + error.message);
          } finally {
              analyzeBtn.classList.remove('analyzing');
              analyzeBtn.disabled = false;
              progressContainer.remove();
          }
      });
  });