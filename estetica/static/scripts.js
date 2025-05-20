function asignarEventosVerMas() {
  const botones = document.querySelectorAll(".boton-ver-mas");

  botones.forEach(boton => {
    boton.addEventListener("click", function () {
      const detalleActual = this.previousElementSibling;

      
      document.querySelectorAll(".detalle").forEach(detalle => {
        if (detalle !== detalleActual) {
          detalle.style.display = "none";
        }
      });

      
      document.querySelectorAll(".boton-ver-mas").forEach(b => {
        if (b !== this) {
          b.textContent = "Ver más";
        }
      });

      
      if (detalleActual.style.display === "block") {
        detalleActual.style.display = "none";
        this.textContent = "Ver más";
      } else {
        detalleActual.style.display = "block";
        this.textContent = "Ver menos";
      }
    });
  });
}

// Al cargar el DOM asigno los eventos, por si hay botones estáticos
document.addEventListener("DOMContentLoaded", function () {
  asignarEventosVerMas();
});

document.addEventListener("DOMContentLoaded", function () {
  const botones = document.querySelectorAll(".boton-ver-mas");

  botones.forEach((boton) => {
    boton.addEventListener("click", function () {
      const tarjeta = this.closest(".tarjeta-servicio");
      const detalle = tarjeta.querySelector(".detalle");

      if (detalle.classList.contains("oculto")) {
        detalle.classList.remove("oculto");
        this.textContent = "Ver menos";
      } else {
        detalle.classList.add("oculto");
        this.textContent = "Ver más";
      }
    });
  });
});
