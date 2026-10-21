import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import {
  IonBackButton,
  IonButton,
  IonButtons,
  IonContent,
  IonHeader,
  IonInput,
  IonItem,
  IonList,
  IonSelect,
  IonSelectOption,
  IonSpinner,
  IonText,
  IonTextarea,
  IonTitle,
  IonToolbar,
  ToastController,
} from '@ionic/angular';
import { BitacoraFormService } from './bitacora-form.service';

@Component({
  selector: 'app-bitacora-form',
  templateUrl: './bitacora-form.page.html',
  // El servicio es propio de esta página: se crea con ella y ninguna otra lo usa
  providers: [BitacoraFormService],
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    FormsModule,
    IonBackButton,
    IonButton,
    IonButtons,
    IonContent,
    IonHeader,
    IonInput,
    IonItem,
    IonList,
    IonSelect,
    IonSelectOption,
    IonSpinner,
    IonText,
    IonTextarea,
    IonTitle,
    IonToolbar,
  ],
})
export class BitacoraFormPage implements OnInit {

  // ID del caso al que pertenece el registro (viene en la URL)
  casoId = '';

  // ID del registro que se edita. Si es null, estamos creando uno nuevo
  id: string | null = null;

  // Opciones del selector "Ruta"
  rutas: string[] = [];

  // Campos del formulario.
  // Las horas se guardan como texto "2026-10-08T14:30", que es el formato del campo de fecha y hora
  ruta = signal('');
  horaInicio = signal('');
  horaFin = signal('');
  texto = signal('');

  // Estado de la pantalla
  cargando = signal(false);
  guardando = signal(false);
  error = signal('');

  // Versión que tenía el registro cuando lo abrimos (se usa al editar)
  private versionLeida = 0;

  constructor(
    private servicio: BitacoraFormService,
    private rutaActual: ActivatedRoute,
    private router: Router,
    private toasts: ToastController
  ) { }

  ngOnInit() {
    // Lee el :casoId y el :id de la URL. En .../bitacora/nuevo no hay id, así que queda null
    this.casoId = this.rutaActual.snapshot.paramMap.get('casoId')!;
    this.id = this.rutaActual.snapshot.paramMap.get('id');
    this.rutas = this.servicio.listarRutas();

    if (this.id !== null) {
      this.cargar(this.id);
    } else {
      // Registro nuevo: la hora de inicio parte en la hora actual
      this.horaInicio.set(this.aTexto(new Date()));
    }
  }

  // Trae el registro que se va a editar y llena el formulario
  async cargar(id: string) {
    this.cargando.set(true);
    try {
      const registro = await this.servicio.obtener(id);
      if (registro === null) {
        this.error.set('El registro no existe.');
      } else {
        this.ruta.set(registro.ruta);
        this.horaInicio.set(this.aTexto(registro.horaInicio));
        if (registro.horaFin !== null) {
          this.horaFin.set(this.aTexto(registro.horaFin));
        }
        this.texto.set(registro.texto);
        this.versionLeida = registro.version;
      }
    } catch (error: any) {
      this.error.set(error.message);
    }
    this.cargando.set(false);
  }

  // Convierte una fecha al texto que usa el campo de fecha y hora: "2026-10-08T14:30"
  aTexto(fecha: Date): string {
    const anio = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    const hora = String(fecha.getHours()).padStart(2, '0');
    const minuto = String(fecha.getMinutes()).padStart(2, '0');
    return anio + '-' + mes + '-' + dia + 'T' + hora + ':' + minuto;
  }

  // Muestra un toast rojo con un mensaje de error
  async avisarError(mensaje: string) {
    const toast = await this.toasts.create({
      message: mensaje,
      duration: 4000,
      color: 'danger',
    });
    await toast.present();
  }

  // Guarda: edita si hay id, crea si no hay
  async guardar() {
    const ruta = this.ruta();
    const texto = this.texto().trim();
    const horaInicio = new Date(this.horaInicio());

    // La hora de fin es opcional: si está vacía, el tramo sigue en curso
    let horaFin: Date | null = null;
    if (this.horaFin() !== '') {
      horaFin = new Date(this.horaFin());
    }

    // La hora de fin no puede ser anterior a la de inicio
    if (horaFin !== null && horaFin < horaInicio) {
      await this.avisarError('La hora de fin no puede ser anterior a la hora de inicio.');
      return;
    }

    this.guardando.set(true);
    try {
      let mensaje = '';
      if (this.id !== null) {
        await this.servicio.editar(this.id, ruta, horaInicio, horaFin, texto, this.versionLeida);
        mensaje = 'Registro actualizado';
      } else {
        await this.servicio.crear(this.casoId, ruta, horaInicio, horaFin, texto);
        mensaje = 'Registro creado';
      }

      const toast = await this.toasts.create({
        message: mensaje,
        duration: 2000,
        color: 'success',
      });
      await toast.present();

      // Vuelve a la bitácora del caso
      this.router.navigateByUrl('/rescatista/casos/' + this.casoId + '/bitacora');
    } catch (error: any) {
      await this.avisarError(error.message);
    }
    this.guardando.set(false);
  }
}
