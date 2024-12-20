document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.querySelector('form[name="loginForm"]');
    const registerForm = document.querySelector('form[name="registerForm"]');
    const errorContainer = document.getElementById('errorContainer');

    // Utility function to show errors
    function showError(message) {
        if (errorContainer) {
            errorContainer.textContent = message;
            errorContainer.style.display = 'block';
        } else {
            alert(message);
        }
    }

    // Toggle password visibility
    document.querySelectorAll('.password-container').forEach(container => {
        const eyeIcon = container.querySelector('.toggle-password');
        const passwordInput = container.querySelector('input[type="password"]');

        if (eyeIcon && passwordInput) {
            eyeIcon.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeIcon.textContent = '🙈';
                } else {
                    passwordInput.type = 'password';
                    eyeIcon.textContent = '👁️';
                }
            });
        }
    });

    // Fetch wrapper with error handling
    function postData(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: Object.keys(data)
                .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(data[key])}`)
                .join('&')
        })
        .then(response => {
            // Check if response is OK (status in 200-299 range)
            if (!response.ok) {
                // Try to parse error response
                return response.text().then(text => {
                    throw new Error(text || 'Network response was not ok');
                });
            }
            return response.json();
        });
    }

    // Login Form Handling
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const userId = document.getElementById('userId').value;
            const password = document.getElementById('password').value;

            // Reset error container
            if (errorContainer) {
                errorContainer.textContent = '';
                errorContainer.style.display = 'none';
            }

            postData('login_handler.php', { userId, password })
                .then(data => {
                    if (data.success) {
                        window.location.href = 'main.html';
                    } else {
                        showError(data.error || 'Login failed');
                    }
                })
                .catch(error => {
                    console.error('Login Error:', error);
                    showError('An unexpected error occurred during login');
                });
        });
    }

    // Registration Form Handling
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const userId = document.getElementById('userId').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            // Reset error container
            if (errorContainer) {
                errorContainer.textContent = '';
                errorContainer.style.display = 'none';
            }

            postData('register-handler.php', { 
                userId, 
                password, 
                confirmPassword 
            })
            .then(data => {
                if (data.success) {
                    window.location.href = 'login.html';
                } else {
                    showError(data.error || 'Registration failed');
                }
            })
            .catch(error => {
                console.error('Registration Error:', error);
                showError('An unexpected error occurred during registration');
            });
        });
    }
});