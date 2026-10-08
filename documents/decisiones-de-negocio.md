# TrekkMe — Decisiones de negocio tomadas en la app Ionic y qué implican para Flutter y Laravel

Este documento reúne las decisiones de negocio que se tomaron mientras se construía la app Ionic (carabinero y rescatista), y lo que cada una obliga a hacer en la **app Flutter** (excursionista) y en el **backend Laravel**.

Varias decisiones **cambian el modelo de datos y el contrato de API** que el grupo había acordado. Según el acta, esos dos documentos solo se modifican por acuerdo de los tres integrantes, así que este archivo sirve como lista de lo que hay que conversar.

Cada decisión indica su origen:

- **Decidida**: la definió el equipo de Ionic explícitamente.
- **Propuesta**: se implementó como supuesto razonable, pero nadie la confirmó. Hay que validarla.

---

## Resumen: qué cambia respecto a lo acordado

| # | Cambio | Afecta a |
|---|---|---|
| 1 | La bitácora pasa de "un texto" a "una ida a terreno" con ruta, hora de inicio y hora de fin | Laravel |
| 2 | La bitácora se puede listar, editar y borrar (el contrato solo tenía crear) | Laravel |
| 3 | Edición de bitácora con control de versión | Laravel |
| 4 | El carabinero elige a qué rescatista asigna el caso al escalar | Laravel |
| 5 | Nuevo estado **"Cierre pendiente"**: el rescatista pide el cierre y el carabinero lo confirma o rechaza | Laravel, Flutter |
| 6 | La excursión admite una **foto** y un **detalle** de texto libre, ambos opcionales | Flutter, Laravel |
| 7 | Se eliminan del equipo el color de chaqueta, la comida y el agua | Flutter, Laravel |
| 8 | Los acompañantes se agregan por **invitación** (si tienen cuenta) o con **cuatro datos mínimos** (si no) | Flutter, Laravel |
| 9 | Se mantiene la batería del teléfono junto a la última ubicación | Flutter, Laravel |
| 10 | La validación por QR en punto de control queda fuera por ahora | Flutter, Laravel |
| 11 | El login se posterga en Ionic | Laravel |

---

## 1. Bitácora del operativo

### 1.1 Un registro de bitácora es una ida a terreno — *Decidida*

**Decisión.** Un registro ya no es solo un texto. Representa **una salida a terreno** y tiene:

| Campo | Obligatorio | Notas |
|---|---|---|
| Ruta | Sí | Se elige de una lista de senderos |
| Hora de inicio | Sí | Fecha y hora |
| Hora de fin | No | Vacía significa que la salida sigue en curso |
| Texto | Sí | Qué pasó |

**Por qué.** En la práctica a un rescatista no le pedirían solo "escribir algo": se registra qué tramo se recorrió y en qué horario.

**Regla asociada.** La hora de fin no puede ser anterior a la de inicio.

**Lo que se quitó.** Los campos `lat` y `lng` que tenía la tabla `bitacora_operativo` en el modelo de datos no se usan en Ionic. Hay que decidir si se eliminan o se conservan como opcionales.

**Implica para Laravel**

- Migración de `bitacora_operativo`: agregar la ruta, `hora_inicio` y `hora_fin` (nullable).
- Decidir cómo se guarda la ruta: como texto, o como `sendero_id` (FK a `senderos`). Lo segundo es lo coherente con la decisión 1.2.
- Validar en el servidor que `hora_fin >= hora_inicio`.

**Implica para Flutter**

- Nada directo: el excursionista no ve la bitácora.

### 1.2 La ruta se elige de un catálogo de senderos — *Decidida*

**Decisión.** La ruta del registro es un selector, pensando en que más adelante existan "senderos comunes" guardados. Hoy Ionic usa una lista fija de cinco opciones, incluida "Fuera de sendero".

**Implica para Laravel**

- El catálogo `areas` / `senderos` (que ya existe en el modelo) debe poder consultarse también desde Ionic, no solo desde Flutter. Hoy `GET /areas` y `GET /areas/{id}/senderos` están en la sección de Flutter del contrato.
- Definir cómo se representa una búsqueda **fuera de sendero**: una opción especial, o permitir la ruta vacía.

**Implica para Flutter**

- El excursionista declara su ruta desde el mismo catálogo. Conviene que ambas apps usen exactamente los mismos nombres de sendero, para que la ruta declarada y la ruta recorrida por el rescatista sean comparables.

### 1.3 Un caso tiene muchos registros de bitácora — *Decidida*

**Decisión.** La bitácora se abre **desde un caso**, y un caso puede tener varios registros. Cuando alguien está realmente perdido, el caso queda abierto por días y se hacen múltiples idas a terreno.

**Implica para Laravel**

- Relación uno a muchos entre `excursiones` y `bitacora_operativo` (ya estaba así en el modelo: `excursion_id` como FK).
- Índice por `(excursion_id, registrado_at DESC)`, que es la consulta que hace la pantalla.

### 1.4 La bitácora es un mantenedor completo — *Decidida (por la pauta)*

**Decisión.** Los registros se pueden **crear, listar, editar y eliminar**. La pauta de la entrega exige un mantenedor completo.

**Implica para Laravel**

El contrato actual solo tiene `POST /rescatista/casos/{id}/bitacora`. Faltan:

| Método | Ruta sugerida | Para qué |
|---|---|---|
| `GET` | `/rescatista/casos/{id}/bitacora` | Listar los registros de un caso |
| `GET` | `/rescatista/casos/{id}/bitacora/{registro}` | Traer uno, para editarlo |
| `PUT` | `/rescatista/casos/{id}/bitacora/{registro}` | Editar |
| `DELETE` | `/rescatista/casos/{id}/bitacora/{registro}` | Eliminar |

**Pregunta abierta.** Una bitácora de un operativo real es un registro oficial. ¿Debería poder borrarse o editarse libremente? Alternativas: permitir editar solo al autor, permitir borrar solo mientras el caso esté abierto, o no borrar nunca y solo anular. Para la entrega se dejó libre.

### 1.5 Edición con control de versión — *Decidida (por la pauta)*

**Decisión.** Cada registro tiene un número de `version` que sube en cada edición. Si dos personas editan el mismo registro, la segunda recibe el mensaje "Alguien modificó este registro. Vuelve a abrirlo e intenta de nuevo." y no pisa los cambios de la primera.

**Implica para Laravel**

- Columna `version` (entero, parte en 1) en `bitacora_operativo`.
- El `PUT` debe recibir la versión que el cliente leyó. Si no coincide con la guardada, responder **409 Conflict** sin guardar; si coincide, guardar y sumar 1.
- La comparación y la escritura deben ir en una transacción.

### 1.6 Se puede seguir anotando con el cierre pendiente — *Propuesta*

**Decisión.** Mientras el caso espera la confirmación de Carabineros, el rescatista puede seguir creando y editando registros.

**Implica para Laravel**

- La Policy de la bitácora debe permitir escribir en los estados "en operativo" y "cierre pendiente", y bloquear cuando el caso está cerrado.

---

## 2. Casos y asignación

### 2.1 Un caso es una excursión en operativo — *Decidida (ya estaba en el diseño)*

**Decisión.** No existe una entidad "caso" aparte. El carabinero ve la excursión como **aviso**; cuando se escala, el rescatista la ve como **caso**.

**Implica para Laravel**

- Una sola tabla `excursiones`. Los endpoints `/carabinero/...` y `/rescatista/...` son vistas distintas de la misma entidad, con campos y permisos diferentes.

### 2.2 El carabinero elige al rescatista al escalar — *Decidida*

**Decisión.** Para escalar un aviso a operativo, el carabinero debe **elegir un rescatista**. No se puede escalar sin asignar. El estado y el rescatista se guardan juntos.

El contrato decía que `POST /carabinero/excursiones/{id}/escalar` "asigna rescatista", pero no decía quién lo elige.

**Implica para Laravel**

- `POST .../escalar` debe **recibir** `rescatista_id` en el cuerpo y validarlo (que exista, que sea rescatista, que esté validado y que corresponda a la jurisdicción).
- Falta un endpoint para llenar el selector, por ejemplo `GET /carabinero/rescatistas`, que devuelva los rescatistas disponibles para la jurisdicción del carabinero.
- Guardar `rescatista_id` y `escalado_por_id` en la misma transacción que el cambio de estado.

**Pregunta abierta.** Hoy se asigna **un** rescatista. Un operativo real lo hace un equipo. ¿Se asigna una persona responsable, o una unidad completa?

### 2.3 El rescatista solo ve los casos que le asignaron — *Decidida*

**Decisión.** "Casos asignados" muestra únicamente los casos del rescatista que inició sesión, en estado "en operativo" o "cierre pendiente". Un caso asignado a otro rescatista no aparece.

**Implica para Laravel**

- `GET /rescatista/casos` filtra por `rescatista_id` del usuario autenticado y por esos dos estados.
- La ficha, el mapa y la bitácora de un caso ajeno deben responder **403**, no solo ocultarse en la lista. La seguridad real está en el servidor.

---

## 3. Cierre del caso

### 3.1 Cierre en dos pasos — *Decidida*

**Decisión.** El rescatista puede cerrar su caso si encontró a la persona, pero el cierre **debe confirmarlo Carabineros**. Por eso aparece un estado nuevo:

```
En operativo ──(rescatista pide el cierre)──▶ Cierre pendiente ──(carabinero confirma)──▶ Cerrada
```

Mientras está pendiente, el rescatista ve la etiqueta "Cierre pendiente de Carabineros" y el carabinero ve el aviso destacado en su bandeja.

**Esto cambia la máquina de estados.** El modelo de datos tiene siete estados y pasa directo de `EN OPERATIVO` a `CERRADA`.

**Implica para Laravel**

- Agregar el estado `cierre_pendiente` al enum de `excursiones.estado` y a la máquina de estados.
- Reemplazar `POST /rescatista/casos/{id}/cerrar` por una **solicitud** de cierre, por ejemplo `POST /rescatista/casos/{id}/solicitar-cierre` (→ `cierre_pendiente`).
- Nuevo endpoint para el carabinero, por ejemplo `POST /carabinero/excursiones/{id}/confirmar-cierre` (→ `cerrada`, con `motivo_cierre = rescate` y `cerrada_por_id`).
- La purga de la copia offline del rescatista debe ocurrir al **confirmarse** el cierre, no al solicitarlo.
- Incluir `cierre_pendiente` en la bandeja del carabinero.
- Decidir si se notifica al carabinero (push o aviso en bandeja) cuando llega una solicitud.

**Implica para Flutter**

- La app del excursionista y el enlace público del contacto de confianza deben saber mostrar el estado nuevo. Lo razonable es presentarlo como "encontrado, en proceso de cierre", o directamente no distinguirlo de "en operativo" para el público.

### 3.2 El carabinero puede rechazar el cierre — *Propuesta*

**Decisión.** Si el carabinero no está de acuerdo, puede **rechazar** la solicitud y el caso vuelve a "en operativo". Se agregó para que un cierre pedido por error se pueda deshacer.

**Implica para Laravel**

- Endpoint `POST /carabinero/excursiones/{id}/rechazar-cierre` (→ `en_operativo`).
- Conviene registrar el motivo del rechazo; hoy Ionic no lo pide.

### 3.3 Cierre como falsa alarma — *Decidida (ya estaba en el diseño)*

**Decisión.** Desde "en alerta", el carabinero puede cerrar el aviso como falsa alarma, y pasa al histórico.

**Implica para Laravel**

- Ya está en el contrato (`POST /carabinero/excursiones/{id}/cerrar`). Guardar `motivo_cierre = falsa_alarma`.

### 3.4 Los cambios de estado son condicionales — *Decidida (por la pauta)*

**Decisión.** Un cambio de estado solo se aplica si el aviso sigue en el estado que el usuario vio en pantalla. Si dos carabineros gestionan el mismo aviso, el segundo recibe "Otra persona ya gestionó este aviso."

**Implica para Laravel**

- Cada endpoint de transición debe validar el estado actual dentro de una transacción y responder **409 Conflict** si ya cambió. Coincide con la regla del diseño de que ningún cliente escribe el estado directamente.

---

## 4. Ficha de búsqueda

### 4.1 Foto y detalle libre al crear la excursión — *Decidida*

**Decisión.** Al crear una excursión, el excursionista puede subir **una foto** y escribir un **detalle** de texto libre. Los dos son **opcionales**. El detalle puede contener lo que quiera: qué lleva, cómo va vestido, apariencia, etc.

**Implica para Flutter**

- En el formulario de aviso de salida: un selector de foto (cámara o galería) y un campo de texto multilínea.
- Comprimir la foto antes de enviarla, por el tamaño y por la conectividad débil.
- Como la app funciona sin conexión, la foto y el detalle deben guardarse localmente y subirse al sincronizar.
- Decidir qué es la foto: ¿de la persona, del grupo, de la ropa del día? Conviene decirlo en la pantalla, porque cambia su utilidad para el rescatista.

**Implica para Laravel**

- Dos columnas nuevas en `excursiones`: la ruta del archivo de la foto y `detalle` (text, nullable).
- Endpoint de subida de imagen (multipart), con validación de tipo y tamaño, y almacenamiento de archivos.
- La foto es un **dato personal**: debe servirse con autorización (URL firmada o endpoint protegido), no como archivo público. Aplica la Ley 21.719 y el RNF de auditoría.
- Incluir `foto` y `detalle` en la respuesta de la ficha.
- Definir cuándo se borra la foto (por ejemplo, un tiempo después del cierre).

### 4.2 Se eliminan chaqueta, comida y agua — *Decidida*

**Decisión.** La ficha ya no muestra el color de la chaqueta ni la cantidad de comida y agua. Esa información, si el excursionista quiere darla, va en el detalle libre. Se mantienen la **carpa** (y su color) y la **linterna**.

**Implica para Flutter**

- Quitar esos tres campos del formulario de equipo. El formulario queda más corto, lo que ayuda a que la gente sí avise.

**Implica para Laravel**

- Simplificar el JSON `equipo` de `excursiones`: quedan `carpa`, `color_carpa` y `linterna`.
- Actualizar el ejemplo de la ficha en el contrato de API, que hoy incluye `color_chaqueta`, `comida_dias` y `agua_litros`.

### 4.3 Acompañantes: invitación o datos mínimos — *Decidida*

**Decisión.** Si en la excursión va más de una persona, el rescatista debe poder ver los datos de todos:

| Situación del acompañante | Qué se ve en la ficha |
|---|---|
| **Tiene cuenta en la app**: se le invita a participar y acepta | Su información completa (nombre, RUT, edad, condiciones médicas, teléfono, experiencia) |
| **No tiene cuenta** | Como mínimo: **RUT, nombre, edad y condición médica**, ingresados por quien crea la excursión |

Además, el RUT pasa a mostrarse también para quien crea la excursión.

**Esto cambia el modelo.** Hoy `excursiones.participantes` es un JSON de "nombres y contactos".

**Implica para Flutter** (es el cambio más grande para esta app)

- Al crear la excursión, por cada acompañante elegir entre "invitar a un usuario" o "agregar sin cuenta".
- **Invitar**: buscar al usuario (definir por qué dato: RUT, correo o teléfono) y enviar la invitación.
- **Recibir invitaciones**: una pantalla o notificación para aceptar o rechazar. Se relaciona con la sección "Excursiones que sigo", pero es distinto: el invitado es participante, no contacto de confianza.
- **Agregar sin cuenta**: formulario con RUT, nombre, edad y condición médica, con validación del RUT.
- Definir qué pasa si la invitación no se responde antes de la salida (ver preguntas abiertas).
- Todo esto debe funcionar con la sincronización diferida de la app.

**Implica para Laravel**

- Reemplazar el JSON `participantes` por una tabla, por ejemplo `excursion_participantes`:

| Campo | Notas |
|---|---|
| `excursion_id` | FK |
| `usuario_id` | FK, nullable. Con valor si tiene cuenta |
| `estado_invitacion` | `pendiente` / `aceptada` / `rechazada`. Solo para los que tienen cuenta |
| `rut`, `nombre`, `edad`, `condiciones_medicas` | Para los que no tienen cuenta |

- Endpoints de invitación: crear, listar las recibidas, aceptar y rechazar. Más una notificación al invitado.
- La respuesta de la ficha debe traer la lista de acompañantes ya resuelta: datos del usuario si tiene cuenta, datos ingresados si no.
- Para tener RUT y edad de los usuarios con cuenta, `usuarios` necesita el RUT (ya está) y la fecha de nacimiento (no está en el modelo).
- `condiciones_medicas` de los participantes sin cuenta es un **dato sensible** igual que el de los usuarios: cifrado en reposo y acceso auditado.

**Riesgo legal a revisar.** Quien crea la excursión ingresa la condición médica de **otra persona** que no tiene cuenta y por lo tanto no dio su consentimiento en la app. Con la Ley 21.719 los datos de salud requieren consentimiento explícito. Hay que definir cómo se resuelve: una declaración de quien ingresa los datos, un consentimiento que el acompañante acepte por enlace, o dejar ese campo como opcional.

### 4.4 Los datos médicos solo los ve el rescatista — *Propuesta*

**Decisión.** En el detalle del aviso, el carabinero ve cuántas personas van y sus nombres, pero **no** las condiciones médicas. Esas aparecen solo en la ficha de búsqueda del rescatista asignado.

**Por qué.** El acta pide que el acceso a datos de salud se restrinja por perfil.

**Implica para Laravel**

- El endpoint del carabinero no debe incluir datos médicos; el de la ficha del rescatista sí, con registro en auditoría.
- **Validar con el grupo**: puede que el carabinero sí los necesite para decidir si escalar (por ejemplo, una persona diabética con seis horas de atraso).

### 4.5 Batería junto a la última ubicación — *Decidida*

**Decisión.** La ficha y la vista de terreno muestran el porcentaje de batería que tenía el teléfono **cuando registró la última ubicación**. Se revisó si era factible y se decidió mantenerlo.

**Por qué sirve.** Una última ubicación con 3% de batería sugiere que el teléfono se apagó ahí; una con 78% sugiere que se quedó sin señal.

**Implica para Flutter**

- Leer el nivel de batería cada vez que se guarda un punto GPS (paquete `battery_plus`, o el dato que entrega el plugin de ubicación en segundo plano) y guardarlo junto al punto en la base local.
- Enviarlo en el envío del rastro.
- Probar en un teléfono real: el simulador de iOS no reporta batería.

**Implica para Laravel**

- Columna `bateria` en la tabla `rastro` y aceptarla en el endpoint de envío del rastro.
- La ficha devuelve la batería del último punto. Ya estaba en el ejemplo del contrato.

### 4.6 Horas de atraso — *Propuesta*

**Decisión.** La ficha muestra cuántas horas han pasado desde la hora comprometida de retorno. Ionic lo calcula en pantalla.

**Implica para Laravel**

- El contrato ya contempla `horas_de_atraso` en la ficha. Es mejor que lo calcule el servidor, para no depender del reloj del teléfono del rescatista.

---

## 5. Vista de terreno

### 5.1 Qué necesita el rescatista en terreno — *Decidida en lo general, propuesta en el detalle*

**Decisión.** La vista de terreno muestra: el mapa con la última ubicación del excursionista, la posición propia del rescatista con la distancia en línea recta, un resumen de a quién se busca (con foto y detalle), y accesos directos a la bitácora y a la ficha.

**Implica para Laravel**

- Ninguna nueva: usa los mismos datos de la ficha.

### 5.2 Actualización manual en vez de notificación — *Propuesta (limitación de la entrega)*

**Decisión.** En lugar de la notificación push cuando llega una ubicación nueva, hay un botón "Buscar ubicación nueva".

**Implica para Laravel**

- Las notificaciones push al rescatista siguen pendientes (registro del token del dispositivo y envío al sincronizarse un punto).

**Implica para Flutter**

- Ninguna. Sigue siendo necesario que sincronice el rastro apenas recupere señal.

---

## 6. Acceso y alcance

### 6.1 El login se posterga — *Decidida*

**Decisión.** En esta entrega no hay usuario ni contraseña. La pantalla de inicio tiene dos botones, "Entrar como carabinero" y "Entrar como rescatista". El rescatista que entra es siempre el mismo.

**Implica para Laravel**

- El login con token (Sanctum) y el campo `perfil` en la respuesta siguen siendo necesarios: es lo que reemplazará a los dos botones.
- Mientras no exista, Ionic no puede probar permisos por perfil ni por jurisdicción.

### 6.2 Carabinero y rescatista separados dentro de la misma app — *Decidida*

**Decisión.** Son dos conjuntos de pantallas separados (`/carabinero/...` y `/rescatista/...`) dentro de una sola aplicación.

**Implica para Laravel**

- Mantener los prefijos `/carabinero` y `/rescatista` del contrato, cada uno con su Policy.

### 6.3 La validación por QR queda fuera — *Decidida*

**Decisión.** Se eliminó la pantalla de validación en punto de control. Tal como estaba (buscar un aviso escribiendo su código) no aportaba nada y no era cómoda de usar.

**Implica para Flutter**

- No hace falta mostrar el código QR del aviso en esta etapa.

**Implica para Laravel**

- `POST /carabinero/validar-qr` no es necesario por ahora.

**Si se retoma.** El valor real está en **registrar que la persona pasó por el control y a qué hora**, no solo en buscar el aviso. Ese dato le sirve al rescatista para saber que el excursionista entró de verdad.

### 6.4 Las excursiones no se crean en Ionic — *Decidida (ya estaba en el diseño)*

**Decisión.** La app institucional no crea excursiones: las crea el excursionista. Para poder probar, Ionic tiene un botón que carga nueve casos ficticios.

**Implica para Flutter**

- Es la única fuente real de excursiones. Hasta que exista, las otras dos apps trabajan con datos de ejemplo.

**Implica para Laravel**

- Conviene tener *seeders* con casos equivalentes a los de Ionic (uno por estado, con y sin ubicación, con y sin acompañantes), para que las tres apps prueben contra lo mismo.

---

## 7. Lista de trabajo por aplicación

### Flutter

- [ ] Formulario de aviso: agregar **foto** y **detalle** opcionales.
- [ ] Formulario de aviso: quitar color de chaqueta, comida y agua.
- [ ] Acompañantes: **invitar** a usuarios con cuenta.
- [ ] Acompañantes: pantalla para **aceptar o rechazar** invitaciones.
- [ ] Acompañantes: formulario de **RUT, nombre, edad y condición médica** para quienes no tienen cuenta.
- [ ] Guardar la **batería** con cada punto GPS y enviarla al sincronizar.
- [ ] Mostrar el estado nuevo **"cierre pendiente"** (o decidir que no se distingue).
- [ ] Usar el mismo catálogo de senderos que Ionic.
- [ ] Sincronizar foto, detalle y acompañantes cuando la excursión se creó sin conexión.

### Laravel

- [ ] `bitacora_operativo`: agregar ruta, `hora_inicio`, `hora_fin` y `version`; decidir qué pasa con `lat` y `lng`.
- [ ] Bitácora: endpoints para listar, traer uno, editar y eliminar.
- [ ] Bitácora: edición con control de versión (409 si no coincide).
- [ ] Estado nuevo `cierre_pendiente` en la máquina de estados.
- [ ] Endpoints: solicitar cierre (rescatista), confirmar y rechazar cierre (carabinero).
- [ ] `escalar` recibe `rescatista_id`; endpoint para listar los rescatistas disponibles.
- [ ] `GET /rescatista/casos` filtrado por el rescatista autenticado; 403 en casos ajenos.
- [ ] Transiciones de estado condicionales (409 si el estado ya cambió).
- [ ] `excursiones`: columnas de **foto** y **detalle**; subida y entrega protegida de la imagen.
- [ ] Simplificar el JSON `equipo`.
- [ ] Tabla de **participantes** e invitaciones, con sus endpoints.
- [ ] Fecha de nacimiento en `usuarios`, para poder mostrar la edad.
- [ ] `bateria` en `rastro`.
- [ ] Catálogo de senderos accesible desde Ionic.
- [ ] Actualizar el ejemplo de la ficha en el contrato de API.
- [ ] Seeders con los mismos casos de prueba que Ionic.

### Ionic (cuando exista el backend)

- [ ] Reescribir los dos servicios para usar la API en vez de Firestore.
- [ ] Reemplazar los botones de perfil por el login real.
- [ ] Reemplazar las listas fijas de rescatistas y rutas por las del backend.
- [ ] Quitar el botón de casos de ejemplo.

---

## 8. Preguntas abiertas para el grupo

1. **Bitácora:** ¿se puede editar y borrar libremente, o debe ser un registro que no se borra? ¿Solo el autor puede editar?
2. **Bitácora:** ¿se eliminan `lat` y `lng`, o se conservan como opcionales?
3. **Asignación:** ¿se asigna un rescatista o una unidad completa? ¿Se puede reasignar un caso?
4. **Cierre:** ¿el carabinero debe indicar un motivo al rechazar? ¿Se le notifica cuando llega una solicitud?
5. **Cierre:** ¿qué ven el excursionista y su contacto de confianza mientras el cierre está pendiente?
6. **Foto:** ¿de qué debe ser la foto, y cuándo se elimina?
7. **Acompañantes:** ¿por qué dato se busca al usuario que se quiere invitar?
8. **Acompañantes:** si un invitado no responde antes de la salida, ¿cuenta como participante? ¿Se le piden igual los datos mínimos?
9. **Acompañantes:** ¿cómo se resuelve el consentimiento para ingresar la condición médica de alguien sin cuenta?
10. **Datos médicos:** ¿el carabinero debe verlos para decidir si escalar?
11. **Fuera de sendero:** ¿cómo se registra una búsqueda que no sigue ningún sendero del catálogo?
