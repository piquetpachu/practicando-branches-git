document.getElementById('formServicio').addEventListener('submit', async e => {
  e.preventDefault();

  const form = e.target;
  const datos = {
    titulo: form.titulo.value,
    descripcion: form.descripcion.value,
    precio: form.precio.value
  };

  const res = await fetch('../api/servicios/agregar_servicio.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(datos)
  });

  const result = await res.json();
  document.getElementById('mensaje').textContent = result.success ? "✅ Servicio agregado" : "❌ Error al agregar";
  form.reset();
});
const formData = new FormData();
formData.append('nombre', 'Nueva categoría');

fetch('../api/categorias/categorias.php', {
  method: 'POST',
  body: formData,
})
  .then(res => res.json())
  .then(data => console.log(data));

  fetch("../api/categorias/categorias.php")
  .then((res) => res.json())
  .then((data) => {
    const select = document.getElementById("categoriaSelect");
    data.forEach(cat => {
      const option = document.createElement("option");
      option.value = cat.id;
      option.textContent = cat.nombre;
      select.appendChild(option);
    });
  });
