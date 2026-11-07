import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, firstValueFrom } from 'rxjs';
import { collection, query, where } from 'firebase/firestore';
import { url } from '../../../core/api';
import { aExcursion } from '../../../core/conversores';
import { traducirError } from '../../../core/errores';
import { COLECCION_EXCURSIONES, db } from '../../../core/firebase';
import { obtenerUsuarioId } from '../../../core/sesion';
import { escuchar } from '../../../core/tiempo-real';
import { Excursion } from '../../../models/excursion';

// Servicio de casos asignados (actor: rescatista). Lo provee solo CasosPage.
@Injectable()
export class CasosService {
  private http = inject(HttpClient);

  // Casos del rescatista que entró, en vivo: en operativo o esperando que Carabineros confirme el cierre
  listar(): Observable<Excursion[]> {
    const consulta = query(
      collection(db, COLECCION_EXCURSIONES),
      where('rescatistaId', '==', obtenerUsuarioId()),          // las reglas exigen este filtro
      where('estado', 'in', ['en_operativo', 'cierre_pendiente'])
    );
    return escuchar(consulta, aExcursion);
  }

  // En operativo → cierre pendiente (falta la confirmación de Carabineros)
  async solicitarCierre(id: string): Promise<void> {
    try {
      await firstValueFrom(
        this.http.post(url(`/rescatista/casos/${id}/solicitar-cierre`), { estado_esperado: 'en_operativo' })
      );
    } catch (error) {
      throw traducirError(error);
    }
  }
}
