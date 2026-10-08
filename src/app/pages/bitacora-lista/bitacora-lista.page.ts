import { ChangeDetectionStrategy, Component, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import {
  AlertController,
  IonButton,
  IonButtons,
  IonContent,
  IonHeader,
  IonItem,
  IonLabel,
  IonList,
  IonSpinner,
  IonText,
  IonTitle,
  IonToolbar,
  ToastController,
} from '@ionic/angular';
import { Bitacora } from '../../models/bitacora';
import { BitacoraService } from '../../services/bitacora.service';

@Component({
  selector: 'app-bitacora-lista',
  templateUrl: './bitacora-lista.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    DatePipe,
    RouterLink,
    IonButton,
    IonButtons,
    IonContent,
    IonHeader,
    IonItem,
    IonLabel,
    IonList,
    IonSpinner,
    IonText,
    IonTitle,
    IonToolbar,
  ],
})
export class BitacoraListaPage {

  // Estado de la pantalla: cuando un signal cambia, la vista se actualiza
  registros = signal<Bitacora[]>([]);
  cargando = signal(true);
  error = signal('');

  constructor(
    private servicio: BitacoraService,
    private alertas: AlertController,
    private toasts: ToastController
  ) { }

  // Ionic lo llama cada vez que se entra a la página (también al volver del formulario)
  ionViewWillEnter() {
    this.cargar();
  }

  // Pide los registros al servicio y actualiza los signals
  cargar() {
    this.cargando.set(true);
    this.error.set('');

    this.servicio.listar().subscribe({
      next: (registros) => {
        this.registros.set(registros);
        this.cargando.set(false);
      },
      error: (error) => {
        this.error.set(error.message);
        this.cargando.set(false);
      },
    });
  }

  // Pregunta antes de borrar
  async confirmarEliminar(registro: Bitacora) {
    const alerta = await this.alertas.create({
      header: 'Eliminar registro',
      message: '¿Seguro? Esta acción no se puede deshacer.',
      buttons: [
        { text: 'Cancelar', role: 'cancel' },
        {
          text: 'Eliminar',
          role: 'destructive',
          handler: () => {
            this.eliminar(registro);
          },
        },
      ],
    });
    await alerta.present();
  }

  // Borra el registro, avisa con un toast y recarga la lista
  async eliminar(registro: Bitacora) {
    try {
      await this.servicio.eliminar(registro.id!);
      const toast = await this.toasts.create({
        message: 'Registro eliminado',
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
