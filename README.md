# Módulo Rastreador de Autodescubrimiento (Moodle)

El módulo **Rastreador de Autodescubrimiento** (Self Discovery Tracker) es una actividad diseñada para centralizar el progreso del estudiante a través de un circuito de 5 pruebas psicométricas y de autoconocimiento.

Funciona como un "hub" o centro de control que verifica en tiempo real el estado de completitud de los bloques asociados (Estilos de Aprendizaje, Personalidad, Orientación Vocacional, Habilidades Socioemocionales y Mapa de Identidad), proporcionando una interfaz unificada y gestionando la finalización de la actividad en el curso.

## Contenido

- [Funcionalidades](#funcionalidades)
- [Recorrido Visual](#recorrido-visual)
- [Sección técnica](#sección-técnica)
- [Instalación](#instalación)
- [Operación y soporte](#operación-y-soporte)
- [Contribuciones](#contribuciones)
- [Equipo de desarrollo](#equipo-de-desarrollo)

---

## Funcionalidades

### Para estudiantes
- **Dashboard Centralizado**: Una vista única donde el estudiante puede ver el estado (No Iniciado, En Progreso, Completado) de cada uno de sus 5 tests.
- **Acceso Directo**: Botones de acción rápida para iniciar o continuar cada test sin tener que navegar por todo el curso buscándolos.
- **Feedback Visual**: Indicadores de barras de progreso y colores de estado para motivar la finalización del circuito.
- **Validación de Completitud**: La actividad se marca automáticamente como "Completada" en el curso solo cuando el estudiante ha finalizado satisfactoriamente los 5 instrumentos.

### Para docentes / administradores
- **Control de Progreso del Curso**: Permite al docente configurar la actividad como requisito de finalización del curso.
- **Gestión Simplificada**: En lugar de rastrear 5 actividades por separado, el docente solo necesita monitorear esta actividad integradora.
- **Vista Diferenciada**: Los docentes ven una interfaz adaptada que les indica que están en modo de visualización de staff, ocultando los prompts de autocompletado personal.
- **Soporte de Calificaciones**: El módulo incluye integración con el libro de calificaciones de Moodle, permitiendo asignar de manera automática una nota al estudiante basada en su progreso en el circuito de autodescubrimiento.

---
## Recorrido Visual

### 1. Experiencia del Estudiante

**Vista General de la Actividad:**
Al ingresar a la actividad, el estudiante encuentra un panel con las 5 tarjetas correspondientes a las pruebas. Inicialmente, todas pueden estar en estado "No Iniciado".

<p align="center">
  <img src="https://github.com/user-attachments/assets/7da5e66e-d2be-4ebf-b1d6-a04ec526709e" alt="Vista Estudiante Inicial" width="800">
</p>

**Progreso en Tiempo Real:**
A medida que el estudiante avanza en las pruebas, el dashboard se actualiza. Los tests que están a medio camino muestran el estado "En Progreso" y un botón para continuar.

<p align="center">
  <img src="https://github.com/user-attachments/assets/990381a0-11de-482d-84ba-0e740763dd99" alt="Vista Estudiante En Progreso" width="800">
</p>

**Circuito Completado:**
Una vez finalizados todos los tests, el dashboard muestra el estado de éxito total. Esto dispara la finalización de la actividad en Moodle.

<p align="center">
  <img src="https://github.com/user-attachments/assets/cdc0647e-e17f-42e2-9d86-4e9ec006770b" alt="Vista Estudiante Completado" width="800">
</p>

**Vista desde el Curso:**
En la página principal del curso, la actividad muestra su estado de finalización.

*Estado incompleto:*
<p align="center">
  <img src="https://github.com/user-attachments/assets/9abd5055-2b84-42e0-98ea-22d3ba33b414" alt="Vista Curso Incompleto" width="600">
</p>

*Estado completado (Check verde):*
<p align="center">
  <img src="https://github.com/user-attachments/assets/4ec7ac77-2bc6-46f1-9278-1b52aa2b3e34" alt="Vista Curso Completado" width="600">
</p>

### 2. Configuración y Docencia

**Selector de Actividad:**
El módulo aparece en el selector de actividades de Moodle con su icono distintivo.

<p align="center">
  <img src="https://github.com/user-attachments/assets/9d280d09-22fb-4caa-add0-a78f03cca9b3" alt="Selector de Actividad" width="800">
</p>

**Opciones de Configuración:**
El docente puede configurar el nombre y la descripción. La sección crítica es "Finalización de actividad", donde se habilita la regla personalizada de manera predeterminada "Requerir todos los tests completados".

<p align="center">
  <img src="https://github.com/user-attachments/assets/cdfa86d6-eadf-49e5-add9-d1e79bf490c9" alt="Opciones de Actividad" width="800">
</p>

**Vista del Profesor:**
El docente ve una nota informativa con indicaciones claras de que esta herramienta es para el seguimiento del estudiante y puede ver el reporte completo en el Sistema Integral del Mapa de Identidad ([bloque student_path](https://github.com/ISCOUTB/student_path)).

<p align="center">
  <img src="https://github.com/user-attachments/assets/79141cb6-b0f9-43ec-b829-09b197ba4781" alt="Vista Profesor" width="800">
</p>

---

## Sección técnica

### 1) Arquitectura y Dependencias
Este módulo es un **Activity Module (`mod`)** que depende de la existencia de otros plugins (Bloques) para funcionar correctamente. No almacena las respuestas de los tests por sí mismo; actúa como un agregador de estados.

**Plugins Requeridos:**
- `block_student_path` (Mapa de Identidad)
- `block_chaside` (Orientación Vocacional)
- `block_learning_style` (Estilos de Aprendizaje)
- `block_personality_test` (Test de Personalidad)
- `block_tmms_24` (Habilidades Socioemocionales)

### 2) Lógica de Completitud (`tracker_helper`)
La clase `tracker_helper` es el núcleo lógico. Realiza consultas a las tablas de base de datos de cada bloque dependiente para verificar si el usuario actual (`$USER->id`) tiene registros marcados como finalizados.

### 3) Reglas de Finalización Custom
El módulo implementa `completion_info` con una regla personalizada llamada `completionalltests`.
- En `mod_form.php`, se añade el checkbox de configuración.
- En `lib.php`, la función `selfdiscoverytracker_cm_info_dynamic` ajusta la visualización para docentes.
- La validación se dispara cada vez que el estudiante ve la actividad (`view.php`), asegurando que si completó un test en otro momento, el tracker se actualice inmediatamente.

---
## Instalación
1. Tener instalados los bloques dependientes mencionados en la sección técnica.
2. Descargar el plugin desde las *releases* del repositorio oficial: https://github.com/ISCOUTB/selfdiscoverytracker/releases
3. En Moodle (como administrador):
   - Ir a **Administración del sitio → Extensiones → Instalar plugins**.
   - Subir el archivo ZIP.
   - Completar el asistente de instalación.
4. En un curso, agregar la actividad **Rastreador de Autodescubrimiento** desde el selector de actividades.

---

## Operación y soporte

### Consideraciones
- **Versión de Moodle**: Compatible con Moodle 4.0+.
- **Versión de PHP**: PHP 7.4+ recomendado.
- **Integridad de Datos**: Si un bloque dependiente es desinstalado, el Rastreador mostrará errores y dejará de reportar progreso para esa sección específica.

### Resolución de problemas
- **La actividad no se marca como completada**: Verifique que el estudiante haya llegado a la pantalla de resultados finales en cada uno de los 5 tests individuales.
- **Error de dependencia**: Si al instalar aparece un mensaje de *missing dependency*, instale primero los bloques del ecosistema.

---

## Contribuciones
¡Las contribuciones son bienvenidas! Si deseas mejorar este bloque, por favor sigue estos pasos:
1. Haz un fork del repositorio.
2. Crea una nueva rama para tu característica o corrección de errores.
3. Realiza tus cambios y asegúrate de que todo funcione correctamente.
4. Envía un pull request describiendo tus cambios.

---
## Equipo de desarrollo
- Jairo Enrique Serrano Castañeda
- Yuranis Henriquez Núñez
- Isaac David Sánchez Sánchez
- Santiago Andrés Orejuela Cueter
- María Valentina Serna González

<div align="center">
<strong>Con ❤️ para la Universidad Tecnológica de Bolívar</strong>
</div>

