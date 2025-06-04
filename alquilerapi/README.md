# Sistema de Gestión de Alquileres - API

Este es un sistema de gestión de alquileres que proporciona una API para administrar departamentos, inquilinos, alquileres y pagos.

## Funcionalidades

### Consultas Principales
- **Departamentos Libres**: Obtiene lista de departamentos disponibles para alquilar
- **Alquileres Activos**: Muestra todos los alquileres que están en curso
- **Inquilinos con Deuda**: Lista los inquilinos que tienen pagos pendientes o parciales
- **Ingresos por Día**: Genera un reporte de ingresos en un rango de fechas específico

### Gestión de Inquilinos
- Registro de nuevos inquilinos con la siguiente información:
  - Nombre completo
  - DNI
  - Teléfono
  - Email
  - Dirección de origen
  - Datos del vehículo (marca, modelo, patente)

## Estructura de la Base de Datos

### Tablas Principales
- `departamentos`: Almacena información de las unidades disponibles
- `inquilinos`: Datos personales de los inquilinos
- `alquileres`: Registros de contratos de alquiler
- `pagos`: Control de pagos y estados

## Uso de la API

### Obtener Departamentos Libres
```php
obtenerDepartamentosLibres($pdo);
```

### Consultar Alquileres Activos
```php
alquileresActivos($pdo);
```

### Verificar Inquilinos con Deuda
```php
inquilinosConDeuda($pdo);
```

### Generar Reporte de Ingresos
```php
ingresosPorDia($pdo, $fecha_inicio, $fecha_fin);
```

### Registrar Nuevo Inquilino
```php
$datos = [
    'nombre_completo' => 'Juan Pérez',
    'dni' => '12345678',
    'telefono' => '1234567890',
    'email' => 'juan@ejemplo.com',
    'direccion_origen' => 'Calle 123',
    'marca_vehiculo' => 'Toyota',
    'modelo_vehiculo' => 'Corolla',
    'patente_vehiculo' => 'ABC123'
];
registrarInquilino($pdo, $datos);
```

## Requisitos
- PHP 7.4 o superior
- MySQL/MariaDB
- PDO PHP Extension

## Configuración
1. Asegúrese de tener configurado el archivo de conexión en `config/conexion.php`
2. Configure los parámetros de la base de datos según su entorno

## Contribución
Si desea contribuir al proyecto, por favor:
1. Fork el repositorio
2. Cree una rama para su funcionalidad (`git checkout -b feature/AmazingFeature`)
3. Commit sus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abra un Pull Request

## Licencia
Este proyecto está bajo la Licencia MIT - vea el archivo `LICENSE.md` para más detalles.