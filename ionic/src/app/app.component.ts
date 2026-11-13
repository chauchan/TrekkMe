import { HttpClient } from '@angular/common/http';
import { Component, inject } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import {
  IonApp,
  IonContent,
  IonHeader,
  IonItem,
  IonLabel,
  IonList,
  IonListHeader,
  IonMenu,
  IonMenuToggle,
  IonRouterOutlet,
  IonTitle,
  IonToolbar,
} from '@ionic/angular';
import { firstValueFrom } from 'rxjs';
import { url } from './core/api';
import { cerrarSesion, obtenerPerfil } from './core/sesion';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  imports: [
    RouterLink,
    IonApp,
    IonContent,
    IonHeader,
    IonItem,
    IonLabel,
    IonList,
    IonListHeader,
    IonMenu,
    IonMenuToggle,
    IonRouterOutlet,
    IonTitle,
    IonToolbar,
  ],
})
export class AppComponent {
  private http = inject(HttpClient);
  private router = inject(Router);

  // Para mostrar en el menú solo las opciones del perfil que entró
  perfil = obtenerPerfil;

  // Cierra la sesión en la API y en Firebase, y vuelve al login
  async salir() {
    try {
      if (obtenerPerfil()) {
        await firstValueFrom(this.http.post(url('/logout'), {}));
      }
    } catch {
      // Si el servidor no responde, igual se cierra la sesión en este dispositivo
    }
    await cerrarSesion();
    this.router.navigateByUrl('/login');
  }
}
