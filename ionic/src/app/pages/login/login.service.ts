import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { url } from '../../core/api';
import { traducirError } from '../../core/errores';
import { Perfil, guardarSesion } from '../../core/sesion';

// Servicio del inicio de sesión. Lo provee solo LoginPage.
@Injectable()
export class LoginService {
  private http = inject(HttpClient);

  // Pide el token a la API, entra a Firebase y devuelve el perfil para saber adónde ir
  async iniciarSesion(correo: string, clave: string): Promise<Perfil> {
    try {
      // La API envuelve la respuesta en "data" (ver laravel/API.md)
      const { data: respuesta } = await firstValueFrom(
        this.http.post<{ data: any }>(url('/login'), { email: correo, password: clave })
      );

      if (respuesta.perfil !== 'carabinero' && respuesta.perfil !== 'rescatista') {
        throw new Error('Esta aplicación es solo para carabineros y rescatistas.');
      }

      await guardarSesion({
        firebaseToken: respuesta.firebase_token,
        perfil: respuesta.perfil,
        usuarioId: String(respuesta.usuario_id),
        jurisdiccionId: respuesta.jurisdiccion_id === null ? null : String(respuesta.jurisdiccion_id),
      });

      return respuesta.perfil;
    } catch (error) {
      throw traducirError(error);
    }
  }
}
