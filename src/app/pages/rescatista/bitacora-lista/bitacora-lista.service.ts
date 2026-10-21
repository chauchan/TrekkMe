import { Injectable } from '@angular/core';
import { Observable, from } from 'rxjs';
import { collection, deleteDoc, doc, getDocs, orderBy, query, where } from 'firebase/firestore';
import { aBitacora } from '../../../core/conversores';
import { traducirError } from '../../../core/errores';
import { COLECCION_BITACORA, db } from '../../../core/firebase';
import { Bitacora } from '../../../models/bitacora';

// Servicio de la lista de la bitácora (actor: rescatista). Lo provee solo BitacoraListaPage.
@Injectable()
export class BitacoraListaService {

  // Registros de un caso, del más nuevo al más antiguo.
  // El caso es una excursión, por eso el campo se llama excursionId
  listar(casoId: string): Observable<Bitacora[]> {
    return from(this.buscarRegistros(casoId));
  }

  private async buscarRegistros(casoId: string): Promise<Bitacora[]> {
    try {
      const consulta = query(
        collection(db, COLECCION_BITACORA),
        where('excursionId', '==', casoId),
        orderBy('registradoAt', 'desc')
      );
      const resultado = await getDocs(consulta);
      return resultado.docs.map((documento) => aBitacora(documento.id, documento.data()));
    } catch (error) {
      throw traducirError(error);
    }
  }

  // Borra un registro
  async eliminar(id: string): Promise<void> {
    try {
      await deleteDoc(doc(db, COLECCION_BITACORA, id));
    } catch (error) {
      throw traducirError(error);
    }
  }
}
