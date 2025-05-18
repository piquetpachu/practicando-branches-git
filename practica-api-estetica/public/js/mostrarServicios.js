fetch("../api/servicios/servicios.php")
  .then((res) => res.json())
  .then((data) => {
    const lista = document.getElementById("servicios");
    data.forEach((servicio) => {
      const item = document.createElement("article");
      item.innerHTML = `<img src="${servicio.imagen}" alt="" width="150" height="100"> <strong><br>${servicio.titulo}</strong> - $${servicio.precio}<br>${servicio.descripcion} <br>${servicio.nombre}`;
      lista.appendChild(item);
    });
  })
  .catch((error) => console.error("Error al obtener servicios:", error));
fetch('../api/categorias/categorias.php')
  .then(res => res.json())
  .then(data => {
    data.forEach(categoria => {
      console.log(categoria.id, categoria.nombre);
    });
  })
  .catch(err => console.error("Error al obtener categorías:", err));
