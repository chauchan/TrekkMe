import { ChangeDetectionStrategy, Component, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import {
  AlertController,
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
  ESTADO_EN_OPERATIVO,
  Excursion,
} from '../../../models/excursion';
import { RESCATISTA_ACTUAL } from '../../../models/rescatista';
import { ExcursionService } from '../../../services/excursion.service';

// Lista de casos asignados al rescatista actual.
// Desde cada caso se abre su bitácora, su ficha y su vista de terreno, y se puede pedir el cierre
@Component({
  selector: 'app-casos',
  templateUrl: './casos.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
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
export class CasosPage {

  // Para comparar en el template
  estadoEnOperativo = ESTADO_EN_OPERATIVO;

  // Estado de la pantalla
  casos = signal<Excursion[]>([]);
  cargando = signal(true);
  error = signal('');

  constructor(
    private servicio: ExcursionService,
    private alertas: AlertController,
    private toasts: ToastController
  ) { }

  // Ionic lo llama cada vez que se entra a la página
  ionViewWillEnter() {
    this.cargar();
  }

  // Pide los casos del rescatista actual y actualiza los signals
  cargar() {
    this.cargando.set(true);
    this.error.set('');

    this.servicio.listarCasos(RESCATISTA_ACTUAL).subscribe({
      next: (casos) => {
        this.casos.set(casos);
        this.cargando.set(false);
      },
      error: (error) => {
        this.error.set(error.message);
        this.cargando.set(false);
      },
    });
  }

  // Pregunta antes de pedir el cierre del caso
  async confirmarCierre(caso: Excursion) {
    const alerta = await this.alertas.create({
      header: 'Cerrar caso',
      message: '¿Encontraron a la persona? El cierre queda pendiente hasta que Carabineros lo confirme.',
      buttons: [
        { text: 'Cancelar', role: 'cancel' },
        {
          text: 'Pedir cierre',
          handler: () => {
            this.pedirCierre(caso);
          },
        },
      ],
    });
    await alerta.present();
  }

  // Deja el caso en "Cierre pendiente", avisa con un toast y recarga la lista
  async pedirCierre(caso: Excursion) {
    try {
      await this.servicio.cambiarEstado(caso.id, ESTADO_EN_OPERATIVO, ESTADO_CIERRE_PENDIENTE);
      const toast = await this.toasts.create({
        message: 'Cierre solicitado. Falta la confirmación de Carabineros.',
        duration: 3000,
        color: 'success',
      });
      await toast.present();
    } catch (error: any) {
      const toast = await this.toasts.create({
        message: error.message,
        duration: 4000,
        color: 'danger',
      });
      await toast.present();
    }
    this.cargar();
  }
}
