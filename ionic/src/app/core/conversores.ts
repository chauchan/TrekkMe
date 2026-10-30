import { Bitacora } from '../models/bitacora';
import {
  Acompanante,
  Aviso,
  ESTADO_CERRADA,
  ESTADO_CIERRE_PENDIENTE,
  ESTADO_EN_ALERTA,
  ESTADO_EN_CURSO,
  ESTADO_EN_OPERATIVO,
  ESTADO_PLANIFICADA,
  Excursion,
} from '../models/excursion';
import { aFecha } from './fechas';

// Firestore guarda el estado como código; la app lo muestra con su etiqueta
const ETIQUETAS_ESTADO: Record<string, string> = {
  planificada: ESTADO_PLANIFICADA,
  en_curso: ESTADO_EN_CURSO,
  en_alerta: ESTADO_EN_ALERTA,
  en_operativo: ESTADO_EN_OPERATIVO,
  cierre_pendiente: ESTADO_CIERRE_PENDIENTE,
  cerrada: ESTADO_CERRADA,
};

// 'en_alerta' → 'En alerta'. Si llega un código desconocido, se muestra tal cual
export function etiquetaEstado(codigo: string): string {
  return ETIQUETAS_ESTADO[codigo] ?? codigo;
}

// Arma un objeto Excursion, campo por campo, con un documento de Firestore
export function aExcursion(id: string, datos: any): Excursion {
  // Convierte cada acompañante, campo por campo
  const acompanantes: Acompanante[] = [];
  for (const persona of datos.acompanantes || []) {
    acompanantes.push({
      nombre: persona.nombre,
      rut: persona.rut,
      edad: persona.edad,
      condicionesMedicas: persona.condicionesMedicas || '',
      tieneCuenta: persona.tieneCuenta ?? false,
      telefono: persona.telefono || '',
      experiencia: persona.experiencia || '',
    });
  }

  return {
    id: id,
    excursionista: datos.excursionista,
    rut: datos.rut,
    telefono: datos.telefono,
    edad: datos.edad,
    experiencia: datos.experiencia,
    condicionesMedicas: datos.condicionesMedicas || '',
    acompanantes: acompanantes,
    ruta: datos.ruta,
    contactoNombre: datos.contactoNombre,
    contactoTelefono: datos.contactoTelefono,
    // Firestore guarda las fechas como Timestamp: las pasamos a Date
    salidaAt: datos.salidaAt.toDate(),
    retornoAt: datos.retornoAt.toDate(),
    // Firestore guarda el código: lo pasamos a la etiqueta que usa la app
    estado: etiquetaEstado(datos.estado),
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

// Deja la excursión como la ve el carabinero: sin datos médicos y con los acompañantes
// reducidos a nombre y RUT. (Los documentos de "avisos" ya vienen así; esto es una segunda protección.)
function quitarDatosMedicos(excursion: Excursion): Aviso {
  const { condicionesMedicas, acompanantes, ...resto } = excursion;
  return {
    ...resto,
    acompanantes: acompanantes.map((persona) => ({ nombre: persona.nombre, rut: persona.rut })),
  };
}

// Arma un Aviso con un documento de la colección "avisos" (misma firma que aExcursion y aBitacora)
export function aAviso(id: string, datos: any): Aviso {
  return quitarDatosMedicos(aExcursion(id, datos));
}

// Arma un objeto Bitacora, campo por campo, con un documento de Firestore
export function aBitacora(id: string, datos: any): Bitacora {
  return {
    id: id,
    excursionId: datos.excursionId,
    rescatistaId: datos.rescatistaId,
    ruta: datos.ruta || 'Fuera de sendero',
    senderoId: datos.senderoId ?? null,
    // Los registros antiguos no tienen hora de inicio: usamos la fecha de creación
    horaInicio: aFecha(datos.horaInicio) ?? datos.registradoAt.toDate(),
    // La hora de fin es opcional (null = el tramo sigue en curso)
    horaFin: aFecha(datos.horaFin),
    texto: datos.texto,
    registradoAt: datos.registradoAt.toDate(),
    version: datos.version,
  };
}
