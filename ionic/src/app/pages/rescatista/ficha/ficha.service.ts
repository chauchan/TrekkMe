import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { doc } from 'firebase/firestore';
import { aExcursion } from '../../../core/conversores';
import { COLECCION_EXCURSIONES, db } from '../../../core/firebase';
import { escucharUno } from '../../../core/tiempo-real';
import { Excursion } from '../../../models/excursion';

// Servicio de la ficha de búsqueda (actor: rescatista). Lo provee solo FichaPage.
// A diferencia del carabinero, el rescatista sí recibe los datos médicos.
@Injectable()
export class FichaService {

  // La ficha completa del caso, en vivo (null si no existe)
  obtener(id: string): Observable<Excursion | null> {
    return escucharUno(doc(db, COLECCION_EXCURSIONES, id), aExcursion);
  }

  // Cuántas horas han pasado desde la hora comprometida de retorno (con un decimal)
  horasDeAtraso(retornoAt: Date): number {
    const milisegundos = Date.now() - retornoAt.getTime();
    const horas = milisegundos / (60 * 60 * 1000);
    return Math.round(horas * 10) / 10;
  }
}
