document.addEventListener("DOMContentLoaded", () => {
    
  // Toggle password visibility
  document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', () => {
      const input = button.previousElementSibling;
      if (input.type === "password") {
        input.type = "text";
        button.textContent = "🙈"; // Change icon to "hide"
      } else {
        input.type = "password";
        button.textContent = "👁️"; // Change icon to "show"
      }
    });
  });
});