

# Guía de Trabajo Colaborativo con Git y GitHub

Este documento explica cómo trabajar en equipo usando Git y GitHub. Incluye los comandos más utilizados para crear ramas, hacer commits, cambiar de rama, subir cambios y colaborar de manera organizada entre dos personas.



## 🧑‍🤝‍🧑 ¿Cómo trabajamos en equipo?

### 🔁 Flujo de trabajo sugerido

1. **Cada integrante trabaja en su propia rama** basada en `main`.
2. Las ramas deben tener nombres descriptivos, por ejemplo: `rama-login`, `rama-funcionalidad-x`.
3. Al terminar una tarea, se hace un **pull request** para unirla a `main`.
4. Antes de trabajar, siempre se recomienda hacer un `git pull origin main` para tener los últimos cambios.
5. Comunicación constante para evitar conflictos en los mismos archivos.

---

## 🔧 Comandos básicos de Git para colaborar

### 📌 Configuración inicial

```bash
git config --global user.name "Tu Nombre"
git config --global user.email "tuemail@ejemplo.com"
````

---

### 🔀 Crear y cambiar de rama

```bash
git checkout -b nombre-de-la-rama
```

> Crea una nueva rama y te mueve a ella.

```bash
git checkout nombre-de-la-rama
```

> Cambia a una rama existente.

```bash
git branch
```

> Lista todas las ramas locales.

---

### 💾 Guardar cambios

```bash
git add .
```

> Agrega todos los archivos modificados al área de preparación.

```bash
git commit -m "Descripción clara del cambio"
```

> Guarda el snapshot del cambio.

---

### ⬆️ Subir cambios a GitHub

```bash
git push origin nombre-de-la-rama
```

> Sube tu rama y cambios a GitHub.

---

### 🔄 Mantener la rama actual actualizada

```bash
git pull origin main
```

> Trae los últimos cambios de la rama `main` y los fusiona con tu rama actual.

---

### 🔁 Unir ramas (merge)

Antes de hacer un merge, asegúrate de estar en la rama `main`:

```bash
git checkout main
git pull origin main
git merge nombre-de-la-rama
```

---

### 🧽 Eliminar una rama

```bash
git branch -d nombre-de-la-rama
```

> Borra la rama localmente (solo si ya está fusionada).

```bash
git push origin --delete nombre-de-la-rama
```

> Borra la rama de GitHub.

---

## 🔄 Recomendaciones de trabajo

* Siempre hacer `pull` antes de comenzar a trabajar.
* Hacer commits frecuentes y descriptivos.
* Usar ramas separadas por tarea para evitar conflictos.
* Revisar y comentar los pull requests del compañero antes de hacer el merge.
* Mantener la rama `main` limpia y funcional.

---

## 📘 Ejemplo de flujo de trabajo

1. `git checkout -b rama-contacto`
2. Trabajas en tu funcionalidad.
3. `git add .`
4. `git commit -m "Agrega formulario de contacto"`
5. `git push origin rama-contacto`
6. Vas a GitHub y haces un Pull Request a `main`
7. El compañero revisa y aprueba el PR
8. Se hace el merge en GitHub
9. Todos hacen `git pull origin main` para tener lo nuevo

---

📌 **Consejo final**: Git es más fácil cuando se usa bien desde el principio. Mantener una buena comunicación y separar claramente las tareas ayuda a que el trabajo en equipo sea mucho más ordenado y fluido.
