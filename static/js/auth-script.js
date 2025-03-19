document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById('login-form');
  const registerForm = document.forms['registerForm']; // Handle new account form
  const errorContainer = document.getElementById('errorContainer');

  // Toggle password visibility for all forms
  document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', () => {
      const input = button.previousElementSibling;
      if (input && input.type === "password") {
        input.type = "text";
        button.textContent = "🙈"; // Change icon to "hide"
      } else if (input) {
        input.type = "password";
        button.textContent = "👁️"; // Change icon to "show"
      }
    });
  });

  // Handle login form submission
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault(); // Prevent default form submission

      // Clear error messages
      if (errorContainer) {
        errorContainer.style.display = "none";
        errorContainer.textContent = "";
      }

      // Collect input data
      const userId = document.getElementById('userId').value.trim();
      const password = document.getElementById('password').value;

      // Validate inputs
      if (!userId || !password) {
        showError("All fields are required.");
        return;
      }

      try {
        // Send data to the server
        const formData = new URLSearchParams();
        formData.append('userId', userId); // Ensure 'userId' matches PHP key
        formData.append('password', password);
        
        const response = await fetch('login_handler.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: formData.toString(), // Correctly encoded form data
        });

        const result = await response.json();

        if (result.success) {
          alert('Login successful! Redirecting...');
          window.location.href = '/g17-capstone-BrainViewAI-0.1-fix/main.html'; // Redirect to main page
        } else {
          showError(result.error || 'Login failed. Please check your credentials.');
        }
      } catch (error) {
        console.error("Error during login:", error);
        showError("Something went wrong. Please try again later.");
      }
    });
  }

  if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // Prevent default form submission

        const userIdInput = document.getElementById('userId');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');

        if (!userIdInput || !passwordInput || !confirmPasswordInput) {
            showError("One or more input fields are missing.");
            return;
        }

        const userId = userIdInput.value.trim();
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (password !== confirmPassword) {
            showError("Passwords do not match.");
            return;
        }

        try {
            const formData = new URLSearchParams();
            formData.append('userId', userId);
            formData.append('password', password);
            formData.append('confirmPassword', confirmPassword);

            const response = await fetch('register-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString(),
            });

            const result = await response.json();

            if (result.success) {
                alert('Account created successfully! Redirecting to login page.');
                window.location.href = '/g17-capstone-BrainViewAI-0.1-fix/login.html';
            } else {
                showError(result.error || 'Registration failed. Please try again.');
            }
        } catch (error) {
            console.error("Error during registration:", error);
            showError("Something went wrong. Please try again later.");
        }
    });

  } else {
      console.error("Register form not found.");
  }



  // Utility function to show error messages
  function showError(message) {
    if (errorContainer) {
      errorContainer.style.display = "block";
      errorContainer.textContent = message;
    } else {
      alert(message); // Fallback for missing error container
    }
  }
});