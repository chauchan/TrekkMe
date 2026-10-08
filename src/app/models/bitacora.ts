// Un registro de la bitácora del operativo
export interface Bitacora {
  id?: string;           // PK: ID del documento
  excursionId: string;   // FK → colección "excursiones"
  rescatistaId: string;  // FK → colección "usuarios"
  texto: string;
  registradoAt: Date;
  version: number;       // sube en cada edición
}
