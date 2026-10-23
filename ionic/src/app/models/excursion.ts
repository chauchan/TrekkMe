// Una persona que acompaña al excursionista que creó la excursión.
// Si tiene cuenta en la app (aceptó la invitación) conocemos todos sus datos;
// si no, solo los que escribió quien creó la excursión.
export interface Acompanante {
  nombre: string;
  rut: string;
  edad: number;
  condicionesMedicas: string;
  tieneCuenta: boolean;   // true = aceptó la invitación con su propia cuenta
  telefono: string;       // solo si tiene cuenta ('' si no)
  experiencia: string;    // solo si tiene cuenta ('' si no)
}

// Una excursión avisada por un excursionista.
// El carabinero la ve como "aviso" y, cuando pasa a operativo, el rescatista la ve como "caso".
export interface Excursion {
  id: string;                // PK: ID del documento
  excursionista: string;     // nombre de quien dio el aviso
  rut: string;
  telefono: string;
  edad: number;
  experiencia: string;       // Principiante, Intermedio o Avanzado
  condicionesMedicas: string; // dato sensible: solo se muestra en la ficha de búsqueda
  acompanantes: Acompanante[]; // las demás personas que van (vacío si va solo)
  ruta: string;              // sendero declarado
  contactoNombre: string;    // contacto de confianza
  contactoTelefono: string;
  salidaAt: Date;            // hora de salida
  retornoAt: Date;           // hora comprometida de retorno
  estado: string;            // uno de los ESTADO_... de abajo
  rescatistaId: string;      // FK → rescatista asignado al escalar ('' si no hay)

  // Lo que el excursionista puede agregar al crear la excursión (las dos cosas son opcionales)
  fotoUrl: string;           // dirección de la foto que subió ('' si no subió)
  detalle: string;           // texto libre: qué lleva, cómo va vestido, etc. ('' si no escribió)

  // Equipo que declaró llevar
  llevaCarpa: boolean;
  colorCarpa: string;        // '' si no lleva carpa
  llevaLinterna: boolean;

  // Última ubicación que alcanzó a enviar su teléfono (null si nunca envió una)
  ultimaLat: number | null;
  ultimaLng: number | null;
  ultimaUbicacionAt: Date | null;
  bateria: number | null;    // porcentaje de batería en ese momento
}

// Estados posibles de una excursión
export const ESTADO_PLANIFICADA = 'Planificada';
export const ESTADO_EN_CURSO = 'En curso';
export const ESTADO_EN_ALERTA = 'En alerta';        // no confirmó el retorno a tiempo
export const ESTADO_EN_OPERATIVO = 'En operativo';  // el carabinero la escaló a búsqueda
export const ESTADO_CIERRE_PENDIENTE = 'Cierre pendiente';  // el rescatista pidió cerrar; falta Carabineros
export const ESTADO_CERRADA = 'Cerrada';

// Lo que ve el carabinero de un acompañante: solo quién es, sin datos médicos
export interface AcompananteAviso {
  nombre: string;
  rut: string;
}

// Lo que ve el carabinero de una excursión: todo menos los datos médicos,
// que solo aparecen en la ficha de búsqueda del rescatista asignado
export interface Aviso extends Omit<Excursion, 'condicionesMedicas' | 'acompanantes'> {
  acompanantes: AcompananteAviso[];
}
