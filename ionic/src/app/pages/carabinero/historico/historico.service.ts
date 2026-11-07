import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { collection, query, where } from 'firebase/firestore';
import { aAviso } from '../../../core/conversores';
import { COLECCION_AVISOS, db } from '../../../core/firebase';
import { obtenerJurisdiccionId } from '../../../core/sesion';
import { escuchar } from '../../../core/tiempo-real';
import { Aviso } from '../../../models/excursion';

// Servicio del histórico de avisos (actor: carabinero). Lo provee solo HistoricoPage.
@Injectable()
export class HistoricoService {

  // Avisos cerrados de la jurisdicción, en vivo: reemplazan el libro de guardia en papel
  listar(): Observable<Aviso[]> {
    const consulta = query(
      collection(db, COLECCION_AVISOS),
      where('jurisdiccionId', '==', obtenerJurisdiccionId()),
      where('estado', '==', 'cerrada')
    );
    return escuchar(consulta, aAviso);
  }
}
