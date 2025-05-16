document.getElementById('formServicio').addEventListener('submit', async e => {
  e.preventDefault();

  const form = e.target;
  const datos = {
    titulo: form.titulo.value,
    descripcion: form.descripcion.value,
    precio: form.precio.value
  };

  const res = await fetch('../api/agregar_servicio.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(datos)
  });

  const result = await res.json();
  document.getElementById('mensaje').textContent = result.success ? "✅ Servicio agregado" : "❌ Error al agregar";
  form.reset();
});
