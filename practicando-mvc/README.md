# estructura de carpetas Modelo-Vista-Controlador
En programación, MVC (Model-View-Controller) es un patrón de diseño arquitectónico que divide una aplicación en tres componentes principales: el Modelo, la Vista y el Controlador. Este patrón facilita la organización y la mantenibilidad del código, separando la lógica de la aplicación de la interfaz de usuario y los datos. 


| Carpeta            | Contenido                                                                 |
| ------------------ | ------------------------------------------------------------------------- |
| `public/`          | Es el único directorio accesible desde el navegador. Contiene `index.php` |
| `app/Controllers/` | Lógica que maneja cada endpoint (por ejemplo, `UsuarioController.php`)    |
| `app/Models/`      | Las clases que representan las tablas de la base de datos                 |
| `app/Routes/`      | Opcional, si preferís separar la definición de rutas                      |
| `config/`          | Archivos de configuración como DB, CORS, etc.                             |
| `storage/`         | Para logs o archivos subidos                                              |
| `.env`             | Almacena claves secretas, credenciales, y configuración sensible          |

