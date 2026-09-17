# Centro de Desarrollo

Panel web liviano para organizar, detectar y consultar proyectos alojados en un servidor de desarrollo doméstico. Forma parte de **Homelab Infrastructure**, una plataforma personal construida para administrar servicios y aplicaciones desde una interfaz centralizada.

## Objetivo

Centralizar el acceso a los proyectos del laboratorio, ofrecer una vista uniforme de su estado y reducir tareas manuales de navegación y administración.

## Funcionalidades

- Detección de proyectos disponibles dentro del directorio configurado.
- Panel principal con navegación centralizada.
- Vista individual con información de cada proyecto.
- Creación controlada de nuevos proyectos desde la interfaz.
- Lectura de estado operativo generado por un proceso externo.
- Protección mediante tokens dinámicos para acciones sensibles.
- Interfaz construida sin frameworks de frontend.

## Tecnologías

- PHP
- HTML5
- CSS3
- JavaScript
- Apache o cualquier servidor web compatible con PHP

## Estructura

```text
centro-desarrollo/
├── includes/
│   ├── paneles/
│   ├── detector_proyectos.php
│   ├── estado-operativo.php
│   ├── gestor_proyectos.php
│   └── seguridad_panel.php
├── recursos/
│   ├── css/
│   └── js/
├── .env.example
├── crear-proyecto.php
├── index.php
├── proyecto.php
└── VERSION
```

## Configuración

La aplicación obtiene su configuración mediante variables de entorno. El archivo `.env.example` funciona solamente como referencia: las variables deben inyectarse desde Apache, Docker, systemd o el mecanismo utilizado en el despliegue.

| Variable | Descripción | Valor de ejemplo |
| --- | --- | --- |
| `HOMELAB_PROJECTS_PATH` | Directorio que contiene los proyectos publicados | `/var/www/html` |
| `HOMELAB_STATUS_FILE` | Archivo JSON con el estado operativo | `/var/lib/homelab/state/estado-operativo.json` |
| `HOMELAB_SERVER_HOST` | Host utilizado como fallback para construir enlaces | `192.0.2.10` |

La dirección `192.0.2.10` pertenece a un rango reservado para documentación y debe reemplazarse durante el despliegue.

## Instalación

1. Copiar el proyecto dentro del directorio publicado por el servidor web.
2. Configurar las variables de entorno necesarias.
3. Conceder permisos de lectura al usuario del servidor web.
4. Conceder permisos de escritura únicamente sobre los directorios que realmente lo requieran.
5. Abrir `index.php` desde el navegador y verificar la detección de proyectos.

Ejemplo de variables para un contenedor:

```yaml
environment:
  HOMELAB_PROJECTS_PATH: /var/www/html
  HOMELAB_STATUS_FILE: /var/lib/homelab/state/estado-operativo.json
  HOMELAB_SERVER_HOST: 192.0.2.10
```

## Seguridad

- El repositorio no contiene contraseñas, tokens persistentes ni datos productivos.
- Los estados, logs, backups, bases de datos y archivos temporales están excluidos del control de versiones.
- Los valores incluidos son ejemplos y no representan una infraestructura real.
- La aplicación está pensada inicialmente para una red privada. Antes de exponerla a Internet deben agregarse autenticación robusta, HTTPS, limitación de acceso y una revisión de seguridad específica.
- No debe montarse el socket de Docker dentro del servidor web.

## Estado del proyecto

Proyecto funcional en evolución. La versión pública fue preparada como una exportación sanitizada, separada de la instalación productiva.

## Autor

Desarrollado por [ProgDeveloperr](https://github.com/ProgDeveloperr) como parte de su laboratorio personal de desarrollo y administración de sistemas.
