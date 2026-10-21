import { ESTADO_CIERRE_PENDIENTE, ESTADO_EN_ALERTA, ESTADO_EN_OPERATIVO } from '../models/excursion';

// Color de la etiqueta según el estado: rojo (alerta) y ámbar (cierre pendiente) piden una acción
export function colorEstado(estado: string): string {
  if (estado === ESTADO_EN_ALERTA) {
    return 'danger';
  }
  if (estado === ESTADO_CIERRE_PENDIENTE) {
    return 'warning';
  }
  if (estado === ESTADO_EN_OPERATIVO) {
    return 'secondary';
  }
  return 'medium';
}
