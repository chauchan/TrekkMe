import { Timestamp } from 'firebase/firestore';

// Devuelve la fecha de hace N horas (con N negativo, dentro de N horas)
export function haceHoras(horas: number): Timestamp {
  const milisegundos = horas * 60 * 60 * 1000;
  return Timestamp.fromMillis(Date.now() - milisegundos);
}

// Firestore guarda las fechas como Timestamp. Acepta null para las fechas opcionales
export function aTimestamp(fecha: Date | null): Timestamp | null {
  if (fecha === null) {
    return null;
  }
  return Timestamp.fromDate(fecha);
}

// Al revés: de Timestamp (o vacío) a Date (o null)
export function aFecha(valor: Timestamp | null | undefined): Date | null {
  if (!valor) {
    return null;
  }
  return valor.toDate();
}
