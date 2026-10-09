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
import {
  ESTADO_CIERRE_PENDIENTE,
  ESTADO_EN_ALERTA,
  ESTADO_EN_CURSO,
  ESTADO_EN_OPERATIVO,
  ESTADO_PLANIFICADA,
  Excursion,
} from '../../../models/excursion';
import { BitacoraService } from '../../../services/bitacora.service';
import { ExcursionService } from '../../../services/excursion.service';

// Bandeja del turno: todos los avisos que todavía no están cerrados
@Component({
  selector: 'app-bandeja',
  templateUrl: './bandeja.page.html',
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
  avisos = signal<Excursion[]>([]);
  cargando = signal(true);
  error = signal('');

  constructor(
    private servicio: ExcursionService,
    private servicioBitacora: BitacoraService,
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

    const estadosActivos = [
      ESTADO_PLANIFICADA,
      ESTADO_EN_CURSO,
      ESTADO_EN_ALERTA,
      ESTADO_EN_OPERATIVO,
      ESTADO_CIERRE_PENDIENTE,
    ];

    this.servicio.listar(estadosActivos).subscribe({
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

  // Color de la etiqueta según el estado: rojo (Alerta) y ámbar (Sol) piden una acción del carabinero
  colorEstado(estado: string): string {
    if (estado === ESTADO_EN_ALERTA) {
      return 'danger';
    }
    if (estado === ESTADO_CIERRE_PENDIENTE) {
      return 'warning';
    }
    if (estado === ESTADO_EN_OPERATIVO) {
      return 'secondary';
    }
    return 'medium';
  }

  // Crea los avisos de ejemplo y sus registros de bitácora, y recarga la bandeja
  async cargarEjemplos() {
    try {
      await this.servicio.cargarEjemplos();
      await this.servicioBitacora.cargarEjemplos();
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
