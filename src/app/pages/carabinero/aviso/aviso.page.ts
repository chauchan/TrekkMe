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
import { colorEstado } from '../../../core/estados';
import { Aviso, ESTADO_CIERRE_PENDIENTE, ESTADO_EN_ALERTA } from '../../../models/excursion';
import { Rescatista } from '../../../models/rescatista';
import { AvisoService } from './aviso.service';

// Detalle de un aviso. Según su estado, el carabinero puede:
// - En alerta: cerrarlo como falsa alarma, o escalarlo a operativo asignando un rescatista
// - Cierre pendiente: confirmar o rechazar el cierre que pidió el rescatista
@Component({
  selector: 'app-aviso',
  templateUrl: './aviso.page.html',
  // El servicio es propio de esta página: se crea con ella y ninguna otra lo usa
  providers: [AvisoService],
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
  rescatistas: Rescatista[] = [];

  // Rescatista elegido para asignarle el caso al escalar
  rescatistaElegido = signal('');

  // Estado de la pantalla
  aviso = signal<Aviso | null>(null);
  cargando = signal(true);
  guardando = signal(false);
  error = signal('');

  constructor(
    private servicio: AvisoService,
    private rutaActual: ActivatedRoute,
    private alertas: AlertController,
    private toasts: ToastController
  ) { }

  ngOnInit() {
    // Lee el :id de la URL /carabinero/aviso/:id
    this.id = this.rutaActual.snapshot.paramMap.get('id')!;
    this.rescatistas = this.servicio.listarRescatistas();
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

  // Color de la etiqueta según el estado (función común de core/estados.ts)
  colorEstado = colorEstado;

  // Busca el nombre de un rescatista en la lista que entrega el servicio
  nombreRescatista(id: string): string {
    for (const rescatista of this.rescatistas) {
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
            this.ejecutar(() => this.servicio.cerrarFalsaAlarma(this.id), 'Aviso cerrado como falsa alarma');
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
            this.ejecutar(
              () => this.servicio.escalar(this.id, this.rescatistaElegido()),
              'Aviso escalado a operativo de búsqueda'
            );
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
            this.ejecutar(() => this.servicio.confirmarCierre(this.id), 'Caso cerrado');
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
            this.ejecutar(() => this.servicio.rechazarCierre(this.id), 'Cierre rechazado: el caso sigue en operativo');
          },
        },
      ],
    });
    await alerta.present();
  }

  // Ejecuta una acción del servicio (cerrar, escalar, confirmar o rechazar),
  // avisa con un toast y vuelve a cargar el aviso
  async ejecutar(accion: () => Promise<void>, mensajeExito: string) {
    this.guardando.set(true);
    try {
      await accion();
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
