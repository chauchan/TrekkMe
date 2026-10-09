import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import {
  AlertController,
  IonBackButton,
  IonBadge,
  IonButton,
  IonButtons,
  IonContent,
  IonHeader,
  IonItem,
  IonLabel,
  IonList,
  IonSelect,
  IonSelectOption,
  IonSpinner,
  IonText,
  IonTitle,
  IonToolbar,
  ToastController,
} from '@ionic/angular';
import {
  ESTADO_CERRADA,
  ESTADO_CIERRE_PENDIENTE,
  ESTADO_EN_ALERTA,
  ESTADO_EN_OPERATIVO,
  Excursion,
} from '../../../models/excursion';
import { RESCATISTAS } from '../../../models/rescatista';
import { ExcursionService } from '../../../services/excursion.service';

// Detalle de un aviso. Según su estado, el carabinero puede:
// - En alerta: cerrarlo como falsa alarma, o escalarlo a operativo asignando un rescatista
// - Cierre pendiente: confirmar o rechazar el cierre que pidió el rescatista
@Component({
  selector: 'app-aviso',
  templateUrl: './aviso.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    DatePipe,
    FormsModule,
    IonBackButton,
    IonBadge,
    IonButton,
    IonButtons,
    IonContent,
    IonHeader,
    IonItem,
    IonLabel,
    IonList,
    IonSelect,
    IonSelectOption,
    IonSpinner,
    IonText,
    IonTitle,
    IonToolbar,
  ],
})
export class AvisoPage implements OnInit {

  // ID del aviso (viene en la URL)
  id = '';

  // Para comparar en el template
  estadoEnAlerta = ESTADO_EN_ALERTA;
  estadoCierrePendiente = ESTADO_CIERRE_PENDIENTE;

  // Opciones del selector de rescatista
  rescatistas = RESCATISTAS;

  // Rescatista elegido para asignarle el caso al escalar
  rescatistaElegido = signal('');

  // Estado de la pantalla
  aviso = signal<Excursion | null>(null);
  cargando = signal(true);
  guardando = signal(false);
  error = signal('');

  constructor(
    private servicio: ExcursionService,
    private rutaActual: ActivatedRoute,
    private alertas: AlertController,
    private toasts: ToastController
  ) { }

  ngOnInit() {
    // Lee el :id de la URL /carabinero/aviso/:id
    this.id = this.rutaActual.snapshot.paramMap.get('id')!;
    this.cargar();
  }

  // Trae el aviso desde el servicio
  async cargar() {
    this.cargando.set(true);
    this.error.set('');
    try {
      const aviso = await this.servicio.obtener(this.id);
      if (aviso === null) {
        this.error.set('El aviso no existe.');
      } else {
        this.aviso.set(aviso);
      }
    } catch (error: any) {
      this.error.set(error.message);
    }
    this.cargando.set(false);
  }

  // Color de la etiqueta según el estado
  colorEstado(estado: string): string {
    if (estado === ESTADO_EN_ALERTA) {
      return 'danger';
    }
    if (estado === ESTADO_CIERRE_PENDIENTE) {
      return 'warning';
    }
    return 'medium';
  }

  // Busca el nombre de un rescatista en la lista fija
  nombreRescatista(id: string): string {
    for (const rescatista of RESCATISTAS) {
      if (rescatista.id === id) {
        return rescatista.nombre;
      }
    }
    return id;
  }

  // Pregunta antes de cerrar la alerta como falsa alarma
  async confirmarFalsaAlarma() {
    const alerta = await this.alertas.create({
      header: 'Cerrar como falsa alarma',
      message: '¿Confirmas que el excursionista está bien? El aviso pasará al histórico.',
      buttons: [
        { text: 'Cancelar', role: 'cancel' },
        {
          text: 'Cerrar aviso',
          handler: () => {
            this.cambiarEstado(ESTADO_EN_ALERTA, ESTADO_CERRADA, 'Aviso cerrado como falsa alarma');
          },
        },
      ],
    });
    await alerta.present();
  }

  // Pregunta antes de escalar a operativo de búsqueda
  async confirmarEscalar() {
    const alerta = await this.alertas.create({
      header: 'Escalar a operativo',
      message: '¿Seguro? El caso se asignará a ' + this.nombreRescatista(this.rescatistaElegido()) + '.',
      buttons: [
        { text: 'Cancelar', role: 'cancel' },
        {
          text: 'Escalar',
          role: 'destructive',
          handler: () => {
            this.escalar();
          },
        },
      ],
    });
    await alerta.present();
  }

  // Pregunta antes de confirmar el cierre que pidió el rescatista
  async confirmarCierre() {
    const alerta = await this.alertas.create({
      header: 'Confirmar cierre',
      message: '¿Confirmas que la persona fue encontrada? El caso pasará al histórico.',
      buttons: [
        { text: 'Cancelar', role: 'cancel' },
        {
          text: 'Confirmar cierre',
          handler: () => {
            this.cambiarEstado(ESTADO_CIERRE_PENDIENTE, ESTADO_CERRADA, 'Caso cerrado');
          },
        },
      ],
    });
    await alerta.present();
  }

  // Pregunta antes de rechazar el cierre: el caso vuelve a operativo
  async confirmarRechazo() {
    const alerta = await this.alertas.create({
      header: 'Rechazar cierre',
      message: '¿Seguro? El caso volverá a estar en operativo.',
      buttons: [
        { text: 'Cancelar', role: 'cancel' },
        {
          text: 'Rechazar cierre',
          role: 'destructive',
          handler: () => {
            this.cambiarEstado(ESTADO_CIERRE_PENDIENTE, ESTADO_EN_OPERATIVO, 'Cierre rechazado: el caso sigue en operativo');
          },
        },
      ],
    });
    await alerta.present();
  }

  // Escala el aviso a operativo y lo asigna al rescatista elegido
  async escalar() {
    this.guardando.set(true);
    try {
      await this.servicio.escalar(this.id, this.rescatistaElegido());
      await this.avisar('Aviso escalado a operativo de búsqueda', 'success');
    } catch (error: any) {
      await this.avisar(error.message, 'danger');
    }
    this.guardando.set(false);
    this.cargar();
  }

  // Cambia el estado del aviso, avisa con un toast y vuelve a cargarlo
  async cambiarEstado(estadoEsperado: string, estadoNuevo: string, mensajeExito: string) {
    this.guardando.set(true);
    try {
      await this.servicio.cambiarEstado(this.id, estadoEsperado, estadoNuevo);
      await this.avisar(mensajeExito, 'success');
    } catch (error: any) {
      await this.avisar(error.message, 'danger');
    }
    this.guardando.set(false);
    this.cargar();
  }

  // Muestra un toast verde ('success') o rojo ('danger')
  async avisar(mensaje: string, color: string) {
    const toast = await this.toasts.create({
      message: mensaje,
      duration: 3000,
      color: color,
    });
    await toast.present();
  }
}
