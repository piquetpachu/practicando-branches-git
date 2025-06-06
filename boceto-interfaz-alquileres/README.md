# Sistema de Gestión de Alquileres

Un sistema web completo para gestionar propiedades en alquiler, inquilinos, pagos y reservas.

## Características

- **Panel de Control**: Estadísticas rápidas y métricas sobre ocupación, inquilinos activos e ingresos  
- **Gestión de Departamentos**: Agregar, editar y seguimiento de unidades  
- **Gestión de Inquilinos**: Información completa de inquilinos e historial  
- **Calendario de Reservas**: Calendario visual de ocupación y reservas  
- **Seguimiento de Pagos**: Control de pagos, saldos pendientes e historial  
- **Reportes y Análisis**: Información del negocio y reportes financieros  

## Tecnologías Utilizadas

- HTML5 para la estructura  
- CSS3 para estilos (con soporte para tema claro/oscuro)  
- JavaScript vanilla para la funcionalidad  
- Font Awesome para iconos  
- Librería FullCalendar para el calendario de reservas  

## Estructura del Proyecto

### Componentes Principales

#### Gestión de Estado

La aplicación usa un objeto `state` central para manejar:

- Departamentos activos  
- Información de inquilinos  
- Registros de pagos  
- Estado de la UI (pestañas activas, temas, etc.)  

#### Interfaz de Usuario

- **Diseño Responsivo**: Enfoque *mobile-first* con vistas de tarjetas para móviles y tablas para escritorio  
- **Tema Claro/Oscuro**: Soporte de temas intercambiables con preferencia persistente  
- **Formularios Modales**: Formularios emergentes para agregar/editar datos  
- **Actualizaciones en Tiempo Real**: Actualización instantánea de la UI cuando cambian los datos  

## Funciones Principales

- `initializeApp()`: Inicializa la aplicación  
- `renderDepartments()`: Muestra información de departamentos  
- `renderTenants()`: Muestra listado de inquilinos  
- `renderPayments()`: Maneja registros de pagos  
- `renderCalendar()`: Gestiona calendario de reservas  

## Uso

1. Clonar el repositorio  
2. Abrir `index.html` en un navegador moderno  
3. Navegar por las pestañas usando la barra superior  
4. Usar los botones "+" para agregar nuevos registros  
5. Hacer clic en los elementos para ver o editar detalles  

## Funcionalidades por Sección

### Panel de Control

- Estadísticas generales  
- Tarjetas de estado rápido  
- Notificaciones de actividad  

### Departamentos

- Listado de unidades  
- Control de estado de ocupación  
- Gestión de detalles  

### Inquilinos

- Perfiles completos  
- Información de vehículos  
- Seguimiento de estadía  

### Calendario

- Calendario visual de ocupación  
- Gestión de reservas  
- Verificación de disponibilidad  

### Pagos

- Registro de pagos  
- Seguimiento de saldos  
- Historial de pagos  

### Reportes

- Resúmenes financieros  
- Tasas de ocupación  
- Análisis de ingresos  

## Estilos

La aplicación usa **variables CSS** para temas:

## Compatibilidad

- Chrome (última versión)  
- Firefox (última versión)  
- Safari (última versión)  
- Edge (última versión)  

## Notas

- Esta es una implementación solo frontend  
- Los datos se almacenan en memoria y se reinician al recargar la página  
- Se debe agregar una API backend para persistir los datos  
