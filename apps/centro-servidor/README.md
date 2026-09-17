# Centro Servidor

Panel web de observabilidad para consultar el estado general de un servidor doméstico desde una interfaz unificada. Forma parte de **Homelab Infrastructure** y presenta información generada por recolectores externos sin almacenar datos productivos dentro del proyecto.

## Objetivo

Reunir en un único panel el estado de servicios, almacenamiento, red, backups y eventos operativos del homelab, manteniendo separadas la recolección de datos y la presentación web.

## Funcionalidades

- Resumen general del servidor.
- Estado de servicios y contenedores monitoreados.
- Información de red y conectividad.
- Estado del mecanismo Network Guardian.
- Salud del almacenamiento mediante información SMART procesada externamente.
- Estado de backups y antigüedad de la última ejecución.
- Registro de actividad y eventos recientes.
- Valores predeterminados seguros cuando una fuente de estado no está disponible.

## Arquitectura

Centro Servidor funciona como una capa de presentación. Los recolectores del sistema generan archivos JSON y la aplicación PHP los consume en modo lectura.

```text
Recolectores del sistema
          │
          ▼
Archivos JSON de estado
          │
          ▼
Lectores PHP
          │
          ▼
Panel web de observabilidad
```

Esta separación evita que el frontend necesite acceso directo a Docker, systemd, dispositivos de almacenamiento o credenciales administrativas.

## Tecnologías

- PHP
- HTML5
- CSS3
- JSON
- Apache o cualquier servidor web compatible con PHP

## Estructura

```text
centro-servidor/
├── includes/
│   ├── paneles/
│   ├── estado_backups.php
│   ├── estado_eventos.php
│   ├── estado_general.php
│   ├── estado_network_guardian.php
│   ├── estado_red.php
│   ├── estado_servicios.php
│   └── estado_smart.php
├── recursos/
│   └── css/
├── .env.example
├── actividad.php
├── backups.php
├── disco.php
├── index.php
├── red.php
└── servicios.php
```

## Configuración

La aplicación utiliza variables de entorno. El archivo `.env.example` es solamente una referencia: las variables deben inyectarse desde Apache, Docker, systemd o el mecanismo de despliegue elegido.

| Variable | Descripción | Valor público de ejemplo |
| --- | --- | --- |
| `HOMELAB_STATE_DIR` | Directorio que contiene los archivos JSON de estado | `/var/lib/homelab/state` |
| `HOMELAB_SERVER_HOST` | Host utilizado como fallback para construir enlaces | `homelab-server.local` |

Archivos esperados dentro de `HOMELAB_STATE_DIR`:

```text
backups.json
eventos.json
network-guardian.json
red.json
servicios.json
servidor.json
smart.json
```

## Instalación

1. Copiar el proyecto dentro del directorio publicado por el servidor web.
2. Configurar `HOMELAB_STATE_DIR` y `HOMELAB_SERVER_HOST`.
3. Conceder al servidor web permisos de lectura sobre los JSON de estado.
4. Mantener los recolectores y sus permisos fuera del directorio público.
5. Abrir `index.php` y comprobar cada sección del panel.

Ejemplo para un contenedor:

```yaml
environment:
  HOMELAB_STATE_DIR: /var/lib/homelab/state
  HOMELAB_SERVER_HOST: homelab-server.local
```

## Seguridad

- El repositorio no contiene credenciales, claves, bases de datos ni estados productivos.
- Los datos publicados son ejemplos y no representan una infraestructura real.
- Los JSON deben montarse en modo de solo lectura siempre que sea posible.
- El servidor web no debe recibir acceso al socket de Docker ni privilegios administrativos.
- El panel está pensado inicialmente para una red privada. Una publicación en Internet requiere autenticación robusta, HTTPS, control de acceso y una revisión de seguridad específica.

## Estado del proyecto

Proyecto funcional en evolución. La versión pública fue preparada como una exportación sanitizada e independiente de la instalación productiva.

## Autor

Desarrollado por [ProgDeveloperr](https://github.com/ProgDeveloperr) como parte de su laboratorio personal de desarrollo y administración de sistemas.
