// Un rescatista al que se le puede asignar un caso
export interface Rescatista {
  id: string;      // PK: ID del documento en la colección "usuarios"
  nombre: string;
}

// Lista fija de rescatistas para el selector del carabinero.
// Más adelante vendrá de la base de datos (usuarios con perfil rescatista).
export const RESCATISTAS: Rescatista[] = [
  { id: 'resc-001', nombre: 'Andrés Poblete' },
  { id: 'resc-002', nombre: 'Carolina Henríquez' },
  { id: 'resc-003', nombre: 'Tomás Riquelme' },
];

// Como todavía no hay login, al entrar como rescatista siempre somos este
export const RESCATISTA_ACTUAL = 'resc-001';
