import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { doc, getDoc } from 'firebase/firestore';
import { url } from '../../../core/api';
import { aBitacora } from '../../../core/conversores';
import { traducirError } from '../../../core/errores';
import { COLECCION_BITACORA, db } from '../../../core/firebase';
import { Bitacora, FUERA_DE_SENDERO, Sendero } from '../../../models/bitacora';

// Servicio del formulario de la bitácora (actor: rescatista). Lo provee solo BitacoraFormPage.
@Injectable()
export class BitacoraFormService {
  private http = inject(HttpClient);

  // Senderos para el selector "Ruta" (catálogo: no necesita tiempo real)
  async listarRutas(): Promise<Sendero[]> {
    try {
      const respuesta = await firstValueFrom(this.http.get<{ data: any[] }>(url('/senderos')));
      return respuesta.data.map((s) => ({ id: String(s.id), nombre: s.nombre }));
    } catch (error) {
      throw traducirError(error);
    }
  }

  // Un registro por su ID, o null si no existe (lectura única de Firestore)
  async obtener(id: string): Promise<Bitacora | null> {
    try {
      const documento = await getDoc(doc(db, COLECCION_BITACORA, id));
      if (!documento.exists()) {
        return null;
      }
      return aBitacora(documento.id, documento.data());
    } catch (error) {
      throw traducirError(error);
    }
  }

  // Crea un registro nuevo dentro de un caso. El servidor pone el rescatista, la fecha y la versión 1
  async crear(casoId: string, senderoId: string, horaInicio: Date, horaFin: Date | null, texto: string): Promise<void> {
    try {
      await firstValueFrom(
        this.http.post(url(`/rescatista/casos/${casoId}/bitacora`), this.cuerpo(senderoId, horaInicio, horaFin, texto))
      );
    } catch (error) {
      throw traducirError(error);
    }
  }

  // Edita el registro. Se manda la versión que se leyó: si alguien lo cambió antes,
  // el servidor responde 409 ("Alguien modificó este registro...") y no se pisa nada.
  async editar(
    id: string,
    senderoId: string,
    horaInicio: Date,
    horaFin: Date | null,
    texto: string,
    versionLeida: number
  ): Promise<void> {
    try {
      await firstValueFrom(
        this.http.put(url(`/rescatista/bitacora/${id}`), {
          ...this.cuerpo(senderoId, horaInicio, horaFin, texto),
          version: versionLeida,
        })
      );
    } catch (error) {
      throw traducirError(error);
    }
  }

  // Arma los datos que espera la API (snake_case y fechas en texto ISO)
  private cuerpo(senderoId: string, horaInicio: Date, horaFin: Date | null, texto: string) {
    return {
      sendero_id: senderoId === FUERA_DE_SENDERO ? null : senderoId,
      hora_inicio: horaInicio.toISOString(),
      hora_fin: horaFin === null ? null : horaFin.toISOString(),
      texto: texto,
    };
  }
}
