import { Bitacora } from '../models/bitacora';
import { Acompanante, Aviso, Excursion } from '../models/excursion';
import { aFecha } from './fechas';

// Arma un objeto Excursion, campo por campo, con los datos de Firestore
export function aExcursion(id: string, datos: any): Excursion {
  // Convierte cada acompañante, campo por campo
  const acompanantes: Acompanante[] = [];
  for (const persona of datos.acompanantes || []) {
    acompanantes.push({
      nombre: persona.nombre,
      rut: persona.rut,
      edad: persona.edad,
      condicionesMedicas: persona.condicionesMedicas,
      tieneCuenta: persona.tieneCuenta,
      telefono: persona.telefono,
      experiencia: persona.experiencia,
    });
  }

  return {
    id: id,
    excursionista: datos.excursionista,
    rut: datos.rut,
    telefono: datos.telefono,
    edad: datos.edad,
    experiencia: datos.experiencia,
    condicionesMedicas: datos.condicionesMedicas,
    acompanantes: acompanantes,
    ruta: datos.ruta,
    contactoNombre: datos.contactoNombre,
    contactoTelefono: datos.contactoTelefono,
    // Firestore guarda las fechas como Timestamp: las pasamos a Date
    salidaAt: datos.salidaAt.toDate(),
    retornoAt: datos.retornoAt.toDate(),
    estado: datos.estado,
    // Solo las excursiones escaladas tienen rescatista
    rescatistaId: datos.rescatistaId || '',
    // La foto y el detalle son opcionales
    fotoUrl: datos.fotoUrl || '',
    detalle: datos.detalle || '',
    llevaCarpa: datos.llevaCarpa,
    colorCarpa: datos.colorCarpa,
    llevaLinterna: datos.llevaLinterna,
    // La última ubicación puede no existir todavía
    ultimaLat: datos.ultimaLat ?? null,
    ultimaLng: datos.ultimaLng ?? null,
    ultimaUbicacionAt: aFecha(datos.ultimaUbicacionAt),
    bateria: datos.bateria ?? null,
  };
}

// Lo que ve el carabinero de una excursión: los mismos datos, pero sin los médicos.
// Las condiciones médicas solo las ve el rescatista asignado, en la ficha de búsqueda.
export function aAviso(excursion: Excursion): Aviso {
  const { condicionesMedicas, acompanantes, ...resto } = excursion;
  return {
    ...resto,
    acompanantes: acompanantes.map((persona) => ({ nombre: persona.nombre, rut: persona.rut })),
  };
}

// Arma un objeto Bitacora, campo por campo, con los datos de Firestore
export function aBitacora(id: string, datos: any): Bitacora {
  return {
    id: id,
    excursionId: datos.excursionId,
    rescatistaId: datos.rescatistaId,
    ruta: datos.ruta || '',
    // Los registros antiguos no tienen hora de inicio: usamos la fecha de creación
    horaInicio: aFecha(datos.horaInicio) ?? datos.registradoAt.toDate(),
    // La hora de fin es opcional (null = el tramo sigue en curso)
    horaFin: aFecha(datos.horaFin),
    texto: datos.texto,
    registradoAt: datos.registradoAt.toDate(),
    version: datos.version,
  };
}
