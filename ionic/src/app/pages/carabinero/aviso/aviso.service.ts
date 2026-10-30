import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { doc, getDoc } from 'firebase/firestore';
import { url } from '../../../core/api';
import { aAviso } from '../../../core/conversores';
import { traducirError } from '../../../core/errores';
import { COLECCION_AVISOS, db } from '../../../core/firebase';
import { Aviso } from '../../../models/excursion';
import { Rescatista } from '../../../models/rescatista';

// Servicio del detalle del aviso (actor: carabinero). Lo provee solo AvisoPage.
// Lee de Firestore y escribe por la API: la página no decide de qué estado a qué estado se pasa.
@Injectable()
export class AvisoService {
  private http = inject(HttpClient);

  // Un aviso por su ID, sin datos médicos, o null si no existe
  async obtener(id: string): Promise<Aviso | null> {
    try {
      const documento = await getDoc(doc(db, COLECCION_AVISOS, id));
      if (!documento.exists()) {
        return null;
      }
      return aAviso(documento.id, documento.data());
    } catch (error) {
      throw traducirError(error);
    }
  }

  // Rescatistas de la jurisdicción, para el selector al escalar (catálogo: no necesita tiempo real)
  async listarRescatistas(): Promise<Rescatista[]> {
    try {
      const respuesta = await firstValueFrom(
        this.http.get<{ data: any[] }>(url('/carabinero/rescatistas'))
      );
      return respuesta.data.map((r) => ({ id: String(r.id), nombre: r.nombre }));
    } catch (error) {
      throw traducirError(error);
    }
  }

  // En alerta → en operativo, asignando el rescatista
  escalar(id: string, rescatistaId: string): Promise<void> {
    return this.transicion(id, 'escalar', 'en_alerta', { rescatista_id: rescatistaId });
  }

  // En alerta → cerrada (falsa alarma)
  cerrarFalsaAlarma(id: string): Promise<void> {
    return this.transicion(id, 'cerrar', 'en_alerta');
  }

  // Cierre pendiente → cerrada (el carabinero confirma lo que pidió el rescatista)
  confirmarCierre(id: string): Promise<void> {
    return this.transicion(id, 'confirmar-cierre', 'cierre_pendiente');
  }

  // Cierre pendiente → en operativo (el carabinero rechaza el cierre)
  rechazarCierre(id: string): Promise<void> {
    return this.transicion(id, 'rechazar-cierre', 'cierre_pendiente');
  }

  // Pide el cambio al servidor. "estadoEsperado" es el estado en que el carabinero vio el aviso:
  // si otra persona ya lo cambió, el servidor responde 409 y traducirError muestra su mensaje.
  private async transicion(id: string, accion: string, estadoEsperado: string, extra = {}): Promise<void> {
    try {
      await firstValueFrom(
        this.http.post(url(`/carabinero/excursiones/${id}/${accion}`), {
          estado_esperado: estadoEsperado,
          ...extra,
        })
      );
    } catch (error) {
      throw traducirError(error);
    }
  }
}
