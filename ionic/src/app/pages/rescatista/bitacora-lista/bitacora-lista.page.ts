import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import {
  AlertController,
  IonBackButton,
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
import { Bitacora } from '../../../models/bitacora';
import { BitacoraListaService } from './bitacora-lista.service';

@Component({
  selector: 'app-bitacora-lista',
  templateUrl: './bitacora-lista.page.html',
  // El servicio es propio de esta página: se crea con ella y ninguna otra lo usa
  providers: [BitacoraListaService],
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    DatePipe,
    RouterLink,
    IonBackButton,
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
export class BitacoraListaPage implements OnInit {

  // ID del caso al que pertenece esta bitácora (viene en la URL)
  casoId = '';

  // Estado de la pantalla: cuando un signal cambia, la vista se actualiza
  registros = signal<Bitacora[]>([]);
  cargando = signal(true);
  error = signal('');

  constructor(
    private servicio: BitacoraListaService,
    private rutaActual: ActivatedRoute,
    private alertas: AlertController,
    private toasts: ToastController
  ) { }

  ngOnInit() {
    // Lee el :casoId de la URL /rescatista/casos/:casoId/bitacora
    this.casoId = this.rutaActual.snapshot.paramMap.get('casoId')!;
  }

  // Ionic lo llama cada vez que se entra a la página (también al volver del formulario)
  ionViewWillEnter() {
    this.cargar();
  }

  // Pide los registros del caso al servicio y actualiza los signals
  cargar() {
    this.cargando.set(true);
    this.error.set('');

    this.servicio.listar(this.casoId).subscribe({
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
