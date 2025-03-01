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
              const userId = data.user_id; // Get the user_id from session
              const nameParts = userId.split(" "); // Split in case it's a full name
              const initials = nameParts.map(part => part[0].toUpperCase()).join("");
              
              document.querySelector(".user-name").textContent = userId; // Show user_id instead of name
              document.querySelector(".user-avatar").textContent = initials;

              // Show the main content
              appContainer.style.display = "block";
          }
      } catch (error) {
          console.error("Error during authentication check:", error);
          alert("Unauthorized access. Please login.");
          window.location.href = "login.html";
      }
  };

  checkAuth();


  // Continue with the page rendering only if the user is authenticated
  const userDropdownBtn = document.getElementById("user-dropdown-btn");
  const userDropdownMenu = document.getElementById("user-dropdown-menu");
  const logoutLink = document.getElementById("logout-link");
  const addPatientBtn = document.getElementById("add-patient-btn");
  const searchBar = document.getElementById("search-bar");
  const patientList = document.getElementById("patient-list");
  const addPatientModal = document.getElementById("add-patient-modal");
  const closeModalBtn = document.getElementById("close-modal-btn");
  const addPatientForm = document.getElementById("add-patient-form");

  // Toggle dropdown menu visibility
  userDropdownBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    userDropdownMenu.classList.toggle("show");
  });

  // Close dropdown when clicking outside
  window.addEventListener("click", () => {
    userDropdownMenu.classList.remove("show");
  });

  // Logout functionality for dashboard logout button
  if (logoutLink) {
    logoutLink.addEventListener("click", () => {
      // Show confirmation dialog
      const confirmation = confirm("Are you sure you want to log out?");
      if (confirmation) {
        // Trigger the logout.php script to destroy the session on the server
        fetch('logout.php')
          .then(response => {
            if (response.ok) {
              // Clear user data from localStorage
              localStorage.removeItem("user");

              // Redirect to the login page
              window.location.href = "login.html";
            } else {
              throw new Error("Logout failed on the server.");
            }
          })
          .catch(error => {
            console.error("Error during logout:", error);
            alert("An error occurred while logging out. Please try again.");
          });
      }
    });
  }

  let patients = JSON.parse(localStorage.getItem("patients")) || [];
  let nextPatientId = parseInt(localStorage.getItem("nextPatientId")) || 1;

  const renderPatients = () => {
    if (!patientList) return;
    patientList.innerHTML = ''; // Clear list
    patients.forEach(patient => {
      const row = document.createElement("tr");
      row.innerHTML = `
        <td>${patient.id}</td>
        <td>${patient.name}</td>
        <td>${patient.age}</td>
        <td>${patient.condition}</td>
        <td>${patient.lastVisit}</td>
        <td class="actions">
          <button class="btn-secondary" data-action="view-ct" data-id="${patient.id}">View CT Scan</button>
          <button class="btn-secondary" data-action="view" data-id="${patient.id}">View</button>
          <button class="btn-danger" data-action="delete" data-id="${patient.id}">Delete</button>
        </td>
      `;
      patientList.appendChild(row);
    });
  };

  const deletePatient = (id) => {
    patients = patients.filter(patient => patient.id !== id);
    localStorage.setItem("patients", JSON.stringify(patients));
    renderPatients();
  };

  patientList.addEventListener("click", (e) => {
    const action = e.target.dataset.action;
    const id = e.target.dataset.id;
    if (action === "view-ct") alert(`Viewing CT scan for patient ID: ${id}`);
    if (action === "view") alert(`Viewing details for patient ID: ${id}`);
    if (action === "delete") deletePatient(id);
  });

  addPatientBtn.addEventListener('click', () => {
    addPatientModal.style.display = 'flex';
  });

  closeModalBtn.addEventListener('click', () => {
    addPatientModal.style.display = 'none';
  });

  window.addEventListener('click', (e) => {
    if (e.target === addPatientModal) {
      addPatientModal.style.display = 'none';
    }
  });

  addPatientForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('patient-name').value.trim();
    const age = parseInt(document.getElementById('patient-age').value);
    const condition = document.getElementById('patient-condition').value.trim();

    if (!name || isNaN(age) || !condition) {
      alert("Please fill in all fields correctly.");
      return;
    }

    const newPatient = {
      id: `PT${nextPatientId}`,
      name,
      age,
      condition,
      lastVisit: new Date().toISOString().split('T')[0],
    };

    patients.push(newPatient);
    nextPatientId++;
    localStorage.setItem('patients', JSON.stringify(patients));
    localStorage.setItem('nextPatientId', nextPatientId);
    renderPatients();
    addPatientModal.style.display = 'none';
  });

  searchBar?.addEventListener("input", () => {
    const query = searchBar.value.toLowerCase();
    Array.from(patientList.querySelectorAll("tr")).forEach(row => {
      const matches = Array.from(row.cells).some(cell => 
        cell.textContent.toLowerCase().includes(query)
      );
      row.style.display = matches ? "" : "none";
    });
  });

  renderPatients();
});