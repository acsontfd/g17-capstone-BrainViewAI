document.addEventListener("DOMContentLoaded", () => {
  // Get the theme toggle button
  const themeToggle = document.getElementById("theme-toggle");
  
  // Check if user previously set a preference
  const currentTheme = localStorage.getItem("theme");
  
  // Apply saved theme or default to light
  if (currentTheme === "dark") {
    document.body.classList.add("dark-mode");
  }
  
  // Update icons based on current theme
  updateThemeIcons();
  
  // Add click event to theme toggle button
  if (themeToggle) {
    themeToggle.addEventListener("click", () => {
      // Toggle dark mode class on body
      document.body.classList.toggle("dark-mode");
      
      // Save user preference to localStorage
      if (document.body.classList.contains("dark-mode")) {
        localStorage.setItem("theme", "dark");
      } else {
        localStorage.setItem("theme", "light");
      }
      
      // Update the moon/sun icons
      updateThemeIcons();
    });
  }
  
  // Function to update icon visibility based on theme
  function updateThemeIcons() {
    const moonIcon = document.querySelector(".moon-icon");
    const sunIcon = document.querySelector(".sun-icon");
    
    if (moonIcon && sunIcon) {
      if (document.body.classList.contains("dark-mode")) {
        moonIcon.style.opacity = "0";
        sunIcon.style.opacity = "1";
      } else {
        moonIcon.style.opacity = "1";
        sunIcon.style.opacity = "0";
      }
    }
  }

  // Check theme on page load and periodically to ensure sync
  function checkTheme() {
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "dark" && !document.body.classList.contains("dark-mode")) {
      document.body.classList.add("dark-mode");
      updateThemeIcons();
    } else if (savedTheme === "light" && document.body.classList.contains("dark-mode")) {
      document.body.classList.remove("dark-mode");
      updateThemeIcons();
    }
  }

  // Check theme every second to ensure sync across tabs
  setInterval(checkTheme, 1000);
}); 