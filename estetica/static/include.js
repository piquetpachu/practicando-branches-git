window.addEventListener("DOMContentLoaded", () => {
  fetch("servicios.html")
    .then(response => response.text())
    .then(html => {
      document.getElementById("servicios-incluidos").innerHTML = html;
      asignarEventosVerMas(); 
    });

  fetch("promociones.html")
    .then(response => response.text())
    .then(html => {
      document.getElementById("promociones-incluidos").innerHTML = html;
      
    });

  fetch("footer.html")
    .then(response => response.text())
    .then(html => {
      document.getElementById("footer-container").innerHTML = html;
    });
});
