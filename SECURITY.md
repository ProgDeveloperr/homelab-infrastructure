# Política de seguridad

## Versiones contempladas

La rama principal representa la versión mantenida de este proyecto personal. Las revisiones de seguridad se aplican sobre el estado más reciente del repositorio.

## Reporte de vulnerabilidades

No publique credenciales, rutas privadas, direcciones internas ni detalles explotables en una incidencia pública.

Utilice un reporte privado de seguridad de GitHub cuando esté disponible. Si esa opción no aparece, contacte al propietario mediante su perfil antes de compartir detalles técnicos sensibles.

Incluya, cuando sea posible:

- módulo afectado;
- descripción del comportamiento observado;
- pasos mínimos para reproducirlo;
- impacto estimado;
- propuesta de mitigación;
- confirmación de que no se adjuntaron secretos ni datos personales.

## Alcance

Son especialmente relevantes los reportes relacionados con:

- exposición de información del entorno;
- validación insuficiente de rutas;
- acceso no autorizado a estados o archivos;
- omisión de controles CSRF o mismo origen;
- habilitación accidental de operaciones sensibles;
- fallos en la separación entre la aplicación web y servicios privilegiados.

## Fuera de alcance

Este repositorio no incluye la infraestructura productiva, las credenciales, los datos reales ni los servicios privados del homelab. Los problemas que dependan exclusivamente de componentes no publicados deberán evaluarse en su entorno correspondiente.

## Divulgación responsable

Permita un tiempo razonable para investigar y corregir el problema antes de hacerlo público. No intente acceder, modificar o eliminar información que no le pertenezca.
