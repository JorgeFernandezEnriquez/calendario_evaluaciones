# calendario_evaluaciones
# 📚 Sistema de Gestión de Evaluaciones

## 📌 Descripción General
Sistema web desarrollado en **PHP + MySQL** orientado a la gestión de evaluaciones escolares. Permite a profesores, UTP y administradores crear, visualizar y controlar evaluaciones dentro de un calendario interactivo.

El sistema incluye control de permisos por rol, validaciones académicas y un módulo de auditoría (log) para registrar todas las acciones realizadas.

---

## 🎯 Objetivos del Sistema
- Organizar evaluaciones por curso y asignatura.
- Evitar sobrecarga de evaluaciones en un mismo día.
- Permitir visualización clara mediante calendario.
- Controlar permisos según rol de usuario.
- Mantener trazabilidad de todas las acciones (auditoría).

---

## 👥 Roles del Sistema

### 🔹 Administrador
- Gestión completa del sistema.
- Crear, editar y eliminar evaluaciones.
- Gestionar usuarios, cursos y asignaturas.
- Asignar profesores a cursos/asignaturas.
- Visualizar logs del sistema.

---

### 🔹 UTP
- Visualización global de evaluaciones.
- Crear, editar y eliminar evaluaciones.
- Exportar calendario a PDF.
- Supervisión académica.

---

### 🔹 Profesor
- Crear evaluaciones solo en sus asignaturas.
- Editar/eliminar únicamente sus evaluaciones.
- Visualizar evaluaciones propias y de otros (modo lectura).
- Validaciones estrictas de horario y conflictos.

---

## 🧩 Funcionalidades Principales

### 📅 Calendario de Evaluaciones
- Vista semanal y mensual.
- Horario definido (08:00 a 18:00).
- Eventos con colores por profesor o tipo.
- Visualización de:
  - Hora
  - Asignatura
  - Profesor

---

### 📝 Gestión de Evaluaciones
- Crear evaluaciones con:
  - Curso
  - Asignatura
  - Fecha
  - Hora inicio
  - Duración
  - Tipo
  - Descripción

#### Validaciones:
- Máximo 2 evaluaciones por curso al día.
- Duración múltiplo de 45 minutos.
- Horario entre 08:30 y 18:00.
- Sin traslapes de horario.
- Profesores solo pueden usar sus asignaturas.

---

### 📊 Exportación
- Exportación de calendario a PDF:
  - Vista semanal
  - Vista mensual

---

### 📋 Módulo de Logs (Auditoría)

#### 📌 Descripción
Registra automáticamente todas las acciones realizadas sobre evaluaciones.

#### 🔍 Acciones registradas:
- Crear evaluación
- Editar evaluación
- Eliminar evaluación

#### 📄 Información almacenada:
- Usuario
- Rol
- Curso
- Asignatura
- Fecha y hora
- Tipo de evaluación
- Descripción
- Valores anteriores (en edición)
- Valores nuevos

#### 🎯 Objetivo:
- Trazabilidad completa del sistema
- Auditoría institucional
- Control de cambios

---

### 🔎 Filtros del Log
- Usuario
- Rol
- Curso (dependiente del usuario)
- Asignatura (dependiente del curso)
- Tipo de acción
- Fecha de registro
- Fecha de evaluación

---

## 🗄️ Base de Datos

### Tablas principales:
- `usuarios`
- `cursos`
- `asignaturas`
- `curso_asignatura`
- `curso_asignatura_profesor`
- `evaluaciones`
- `registros_evaluaciones`

---

## ⚙️ Tecnologías Utilizadas

- PHP (sin framework)
- MySQL / MariaDB
- JavaScript (Vanilla)
- FullCalendar.js
- HTML5 + CSS3

---

## 🔒 Seguridad

- Control de acceso por sesión (`$_SESSION`)
- Validación de roles
- Uso de consultas preparadas (`prepare`)
- Restricción de acciones por usuario

---

## 📈 Rendimiento

- Sistema optimizado para uso escolar.
- Uso de paginación en logs.
- Índices recomendados:

```sql
CREATE INDEX idx_usuario ON registros_evaluaciones(usuario_id);
CREATE INDEX idx_fecha ON registros_evaluaciones(fecha_registro);
CREATE INDEX idx_curso ON registros_evaluaciones(curso_id);