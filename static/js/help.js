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
    });
  