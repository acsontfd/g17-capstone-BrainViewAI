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

        // Toggle dropdown menu visibility
        const userDropdownBtn = document.getElementById("user-dropdown-btn");
        const userDropdownMenu = document.getElementById("user-dropdown-menu");
        const logoutLink = document.getElementById("logout-link");
        userDropdownBtn.addEventListener("click", (e) => {
            e.stopPropagation(); // Prevent the click from propagating to the window
            userDropdownMenu.classList.toggle("show");
        });
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
                            alert("Logout successful. Redirecting to login page...");
                            window.location.href = "login.html";
                        } else {
                            alert("Error logging out. Please try again.");
                        }
                    })
                    .catch(error => {
                        console.error("Error during logout:", error);
                        alert("Error logging out. Please try again.");
                    });
                }
            });
        }
});
  