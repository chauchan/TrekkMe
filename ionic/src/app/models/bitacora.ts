// Un registro de la bitácora del operativo: un tramo de búsqueda hecho por un rescatista
export interface Bitacora {
  id?: string;              // PK: ID del documento
  excursionId: string;      // FK → colección "excursiones" (el caso al que pertenece)
  rescatistaId: string;     // FK → usuarios
  ruta: string;             // nombre del sendero recorrido (para mostrar)
  senderoId: string | null; // FK → senderos (null = fuera de sendero)
  horaInicio: Date;         // cuándo empezó el tramo
  horaFin: Date | null;     // cuándo terminó (null = todavía en curso)
  texto: string;            // qué pasó
  registradoAt: Date;       // cuándo se creó el registro
  version: number;          // sube en cada edición
}

// Un sendero del catálogo, para el selector "Ruta"
export interface Sendero {
  id: string;
  nombre: string;
}

// Valor del selector cuando la búsqueda no siguió ningún sendero
export const FUERA_DE_SENDERO = 'fuera';

// Senderos de ejemplo para el selector "Ruta".
// Más adelante esta lista vendrá de la base de datos (senderos guardados).
export const RUTAS = [
  'PN Nonguén - Sendero Los Canelos',
  'PN Nonguén - Sendero Los Copihues',
  'PN Nahuelbuta - Piedra del Águila',
  'Laguna del Laja - Sierra Velluda',
  'Fuera de sendero',
];
