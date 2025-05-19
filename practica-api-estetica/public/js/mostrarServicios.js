
fetch("../api/servicios/servicios.php")
  .then((res) => res.json())
  .then((data) => {
    const lista = document.getElementById("servicios");
    data.forEach((servicio) => {
      const item = document.createElement("article");
      item.innerHTML = `<strong>${servicio.titulo}</strong> - $${servicio.precio}<br>${servicio.descripcion}`;
      lista.appendChild(item);
    });
  })
  .catch((error) => console.error("Error al obtener servicios:", error));
