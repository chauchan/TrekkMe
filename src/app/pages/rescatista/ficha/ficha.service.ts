import { Injectable } from '@angular/core';
import { obtenerPorId } from '../../../core/excursiones-firestore';
import { Excursion } from '../../../models/excursion';

// Servicio de la ficha de búsqueda (actor: rescatista). Lo provee solo FichaPage.
// A diferencia del carabinero, el rescatista sí recibe los datos médicos.
@Injectable()
export class FichaService {

  // La ficha completa del caso, o null si no existe
  obtener(id: string): Promise<Excursion | null> {
    return obtenerPorId(id);
  }

  // Cuántas horas han pasado desde la hora comprometida de retorno (con un decimal).
  // Cuando exista Laravel, este dato lo debe calcular el servidor (horas_de_atraso)
  horasDeAtraso(retornoAt: Date): number {
    const milisegundos = Date.now() - retornoAt.getTime();
    const horas = milisegundos / (60 * 60 * 1000);
    return Math.round(horas * 10) / 10;
  }
}
