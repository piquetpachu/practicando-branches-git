

# 📄 Documentación Técnica – **Base de Datos y API**

## 🧱 BASE DE DATOS: `web_salon`

### Tabla: `servicios`

| Campo         | Tipo          | Detalles                     |
| ------------- | ------------- | ---------------------------- |
| `id`          | INT           | AUTO\_INCREMENT, PRIMARY KEY |
| `titulo`      | VARCHAR(100)  | Título del servicio          |
| `descripcion` | TEXT          | Descripción completa         |
| `precio`      | DECIMAL(10,2) | Precio en moneda local       |

---

## 🌐 API – Endpoints disponibles

> Todos los endpoints se encuentran en la carpeta: `/api/`

---

### 📥 `GET` → `/api/servicios.php`

**Descripción:** Devuelve una lista de todos los servicios.

**Respuesta:**

```json
[
  {
    "id": 1,
    "titulo": "Corte de cabello",
    "descripcion": "Corte clásico con máquina",
    "precio": 1500.00
  },
  {
    "id": 2,
    "titulo": "Peinado",
    "descripcion": "Peinado para eventos",
    "precio": 1800.00
  }
]
```

---

### 📤 `POST` → `/api/agregar_servicio.php`

**Descripción:** Agrega un nuevo servicio.

**Headers:**

```http
Content-Type: application/json
```

**Body (JSON):**

```json
{
  "titulo": "Coloración",
  "descripcion": "Tinte completo",
  "precio": 2500
}
```

**Respuesta:**

```json
{ "success": true }
```

---

### 📝 `POST` → `/api/editar_servicio.php`

**Descripción:** Edita un servicio existente por ID.

**Headers:**

```http
Content-Type: application/json
```

**Body (JSON):**

```json
{
  "id": 1,
  "titulo": "Corte moderno",
  "descripcion": "Degradado con tijera",
  "precio": 1700
}
```

**Respuesta:**

```json
{ "success": true }
```

---

### ❌ `POST` → `/api/eliminar_servicio.php`

**Descripción:** Elimina un servicio por ID.

**Headers:**

```http
Content-Type: application/json
```

**Body (JSON):**

```json
{ "id": 1 }
```

**Respuesta:**

```json
{ "success": true }
```

---

## 📌 Notas para el FRONTEND

* Todos los endpoints devuelven datos en formato JSON.
* El `fetch()` en JS se puede usar con `GET` para listar y con `POST` + `body` para agregar, editar o eliminar.
* Los servicios deben mostrarse en base a lo que retorna el `GET /api/servicios.php`.
* Para eliminar o editar, el `id` es obligatorio.
---

¿Querés que esta documentación te la pase como archivo descargable (`PDF`, `TXT`, `Markdown`)? ¿O la preparamos también para promociones si ya estás por agregarlas?
