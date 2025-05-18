const lista = document.getElementById('listaServicios');

function cargarServicios() {
  fetch('../api/servicios/servicios.php')
    .then(res => res.json())
    .then(data => {
      lista.innerHTML = '';
      data.forEach(servicio => {
        const item = document.createElement('li');
        item.innerHTML = `
          <input type="text" value="${servicio.titulo}" id="titulo-${servicio.id}">
          <input type="text" value="${servicio.descripcion}" id="desc-${servicio.id}">
          <input type="number" value="${servicio.precio}" id="precio-${servicio.id}">
          <button onclick="editar(${servicio.id})">Editar</button>
          <button onclick="eliminar(${servicio.id})">Eliminar</button>
        `;
        lista.appendChild(item);
      });
    });
}

window.editar = async function(id) {
  const titulo = document.getElementById(`titulo-${id}`).value;
  const descripcion = document.getElementById(`desc-${id}`).value;
  const precio = document.getElementById(`precio-${id}`).value;

  const res = await fetch('../api/servicios/editar_servicio.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, titulo, descripcion, precio })
  });

  const result = await res.json();
  alert(result.success ? "✅ Editado correctamente" : "❌ Error al editar");
  cargarServicios();
};

window.eliminar = async function(id) {
  if (!confirm("¿Estás seguro de eliminar este servicio?")) return;

  const res = await fetch('../api/servicios/eliminar_servicio.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id })
  });

  const result = await res.json();
  alert(result.success ? "🗑️ Eliminado correctamente" : "❌ Error al eliminar");
  cargarServicios();
};

cargarServicios();

fetch('../api/categorias/categorias.php?id=3', {
  method: 'PUT',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({ nombre: 'Nombre actualizado' }),
})
  .then(res => res.json())
  .then(data => console.log(data));
