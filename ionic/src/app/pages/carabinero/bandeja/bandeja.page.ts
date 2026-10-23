import { ChangeDetectionStrategy, Component, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import {
  IonBadge,
  IonButton,
  IonButtons,
  IonContent,
  IonHeader,
  IonItem,
  IonLabel,
  IonList,
  IonMenuButton,
  IonSpinner,
  IonText,
  IonTitle,
  IonToolbar,
  ToastController,
} from '@ionic/angular';
import { colorEstado } from '../../../core/estados';
import { Aviso } from '../../../models/excursion';
import { BandejaService } from './bandeja.service';

// Bandeja del turno: todos los avisos que todavía no están cerrados
@Component({
  selector: 'app-bandeja',
  templateUrl: './bandeja.page.html',
  // El servicio es propio de esta página: se crea con ella y ninguna otra lo usa
  providers: [BandejaService],
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    DatePipe,
    RouterLink,
    IonBadge,
    IonButton,
    IonButtons,
    IonContent,
    IonHeader,
    IonItem,
    IonLabel,
    IonList,
    IonMenuButton,
    IonSpinner,
    IonText,
    IonTitle,
    IonToolbar,
  ],
})
export class BandejaPage {

  // Estado de la pantalla
  avisos = signal<Aviso[]>([]);
  cargando = signal(true);
  error = signal('');

  constructor(
    private servicio: BandejaService,
    private toasts: ToastController
  ) { }

  // Ionic lo llama cada vez que se entra a la página (también al volver del detalle)
  ionViewWillEnter() {
    this.cargar();
  }

  // Pide los avisos activos (todos menos los cerrados) y actualiza los signals
  cargar() {
    this.cargando.set(true);
    this.error.set('');


    this.servicio.listar().subscribe({
      next: (avisos) => {
        this.avisos.set(avisos);
        this.cargando.set(false);
      },
      error: (error) => {
        this.error.set(error.message);
        this.cargando.set(false);
      },
    });
  }

  // Color de la etiqueta según el estado (función común de core/estados.ts)
  colorEstado = colorEstado;

  // Crea los avisos de ejemplo y sus registros de bitácora, y recarga la bandeja
  async cargarEjemplos() {
    try {
      await this.servicio.cargarEjemplos();
      const toast = await this.toasts.create({
        message: 'Casos de ejemplo cargados',
        duration: 2000,
        color: 'success',
      });
      await toast.present();
    } catch (error: any) {
      const toast = await this.toasts.create({
        message: error.message,
        duration: 3000,
        color: 'danger',
      });
      await toast.present();
    }
    this.cargar();
  }
}
