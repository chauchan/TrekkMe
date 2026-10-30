import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, firstValueFrom } from 'rxjs';
import { collection, orderBy, query, where } from 'firebase/firestore';
import { url } from '../../../core/api';
import { aBitacora } from '../../../core/conversores';
import { traducirError } from '../../../core/errores';
import { COLECCION_BITACORA, db } from '../../../core/firebase';
import { escuchar } from '../../../core/tiempo-real';
import { Bitacora } from '../../../models/bitacora';

// Servicio de la lista de la bitácora (actor: rescatista). Lo provee solo BitacoraListaPage.
@Injectable()
export class BitacoraListaService {
  private http = inject(HttpClient);

  // Registros de un caso, del más nuevo al más antiguo, en vivo
  listar(casoId: string): Observable<Bitacora[]> {
    const consulta = query(
      collection(db, COLECCION_BITACORA),
      where('excursionId', '==', casoId),
      orderBy('registradoAt', 'desc')
    );
    return escuchar(consulta, aBitacora);
  }

  // Borra un registro (lo hace el servidor; la lista se actualiza sola)
  async eliminar(id: string): Promise<void> {
    try {
      await firstValueFrom(this.http.delete(url(`/rescatista/bitacora/${id}`)));
    } catch (error) {
      throw traducirError(error);
    }
  }
}
