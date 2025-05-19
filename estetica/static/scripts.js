document.addEventListener("DOMContentLoaded", function () {
  const botones = document.querySelectorAll(".boton-ver-mas");

  botones.forEach(boton => {
    boton.addEventListener("click", function () {
      const detalleActual = this.previousElementSibling;

      // Cerrar cualquier otro detalle abierto
      document.querySelectorAll(".detalle").forEach(detalle => {
        if (detalle !== detalleActual) {
          detalle.style.display = "none";
        }
      });

      // Cambiar texto de todos los botones a "Ver más"
      document.querySelectorAll(".boton-ver-mas").forEach(b => {
        if (b !== this) {
          b.textContent = "Ver más";
        }
      });

      // Alternar el detalle actual
      if (detalleActual.style.display === "block") {
        detalleActual.style.display = "none";
        this.textContent = "Ver más";
      } else {
        detalleActual.style.display = "block";
        this.textContent = "Ver menos";
      }
    });
  });
});

