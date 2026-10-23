// Un registro de la bitácora del operativo: un tramo de búsqueda hecho por un rescatista
export interface Bitacora {
  id?: string;           // PK: ID del documento
  excursionId: string;   // FK → colección "excursiones" (el caso al que pertenece)
  rescatistaId: string;  // FK → colección "usuarios"
  ruta: string;          // sendero recorrido, uno de RUTAS
  horaInicio: Date;      // cuándo empezó el tramo
  horaFin: Date | null;  // cuándo terminó (null = todavía en curso)
  texto: string;         // qué pasó
  registradoAt: Date;    // cuándo se creó el registro
  version: number;       // sube en cada edición
}

// Senderos de ejemplo para el selector "Ruta".
// Más adelante esta lista vendrá de la base de datos (senderos guardados).
export const RUTAS = [
  'PN Nonguén - Sendero Los Canelos',
  'PN Nonguén - Sendero Los Copihues',
  'PN Nahuelbuta - Piedra del Águila',
  'Laguna del Laja - Sierra Velluda',
  'Fuera de sendero',
];
