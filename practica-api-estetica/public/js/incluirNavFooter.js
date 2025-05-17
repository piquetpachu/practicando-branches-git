// public/js/includeNav.js
document.addEventListener("DOMContentLoaded", () => {
  fetch("nav.html")
    .then(res => res.text())
    .then(data => {
      document.getElementById("nav-container").innerHTML = data;
    })
    .catch(err => console.error("Error al cargar el nav:", err));
});
// public/js/layoutLoader.js
document.addEventListener("DOMContentLoaded", () => {
  // Cargar el nav
  fetch("nav.html")
    .then(res => res.text())
    .then(data => {
      document.getElementById("nav-container").innerHTML = data;
    });

  // Cargar el footer
  fetch("footer.html")
    .then(res => res.text())
    .then(data => {
      document.getElementById("footer-container").innerHTML = data;
    });
});
