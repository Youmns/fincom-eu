document.addEventListener("DOMContentLoaded", () => {
  const supportedLanguages = ["en", "de", "es", "ru", "fr"];
  const modal = document.getElementById("language-modal");
  const languageLinks = document.querySelectorAll(".language-options a");
  const languageBtn = document.getElementById("language-btn");

  // Exit early if critical elements are missing
  if (!modal || !languageBtn) {
    console.warn("Language modal or button not found.");
    return;
  }

  // Show modal on button click
  languageBtn.addEventListener("click", () => {
    modal.style.display = "flex";
    const firstLink = modal.querySelector(".language-options a");
    if (firstLink) firstLink.focus();
  });

  // Close modal on Esc key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.style.display === "flex") {
      modal.style.display = "none";
    }
  });

  // Close modal on backdrop click
  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });

  // Trap focus within modal
  const focusableElements = modal.querySelectorAll("a, button");
  const firstFocusable = focusableElements[0];
  const lastFocusable = focusableElements[focusableElements.length - 1];
  modal.addEventListener("keydown", (e) => {
    if (e.key === "Tab") {
      if (e.shiftKey && document.activeElement === firstFocusable) {
        e.preventDefault();
        lastFocusable.focus();
      } else if (!e.shiftKey && document.activeElement === lastFocusable) {
        e.preventDefault();
        firstFocusable.focus();
      }
    }
  });

  // Redirect on language selection
  languageLinks.forEach((link) => {
    link.addEventListener("click", (event) => {
      event.preventDefault();
      const selectedLang = link.getAttribute("data-lang");

      if (!selectedLang || !supportedLanguages.includes(selectedLang)) {
        console.warn(`Invalid language: ${selectedLang}`);
        return;
      }

      modal.style.display = "none"; // Close the modal

      // Store selected language (optional)
      localStorage.setItem("userLanguage", selectedLang);

      // Redirect logic
      let targetUrl = "/";
      if (selectedLang !== "en") {
        targetUrl = `/${selectedLang}/${selectedLang}.html`;
      }

      window.location.href = targetUrl;
    });
  });
});
