import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import {
  IonBackButton,
  IonButton,
  IonButtons,
  IonContent,
  IonHeader,
  IonSpinner,
  IonText,
  IonTextarea,
  IonTitle,
  IonToolbar,
  ToastController,
} from '@ionic/angular';
import { BitacoraService } from '../../services/bitacora.service';

@Component({
  selector: 'app-bitacora-form',
  templateUrl: './bitacora-form.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    FormsModule,
    IonBackButton,
    IonButton,
    IonButtons,
    IonContent,
    IonHeader,
    IonSpinner,
    IonText,
    IonTextarea,
    IonTitle,
    IonToolbar,
  ],
})
export class BitacoraFormPage implements OnInit {

  // ID del registro que se edita. Si es null, estamos creando uno nuevo
  id: string | null = null;

  // Estado de la pantalla
  texto = signal('');
  cargando = signal(false);
  guardando = signal(false);
  error = signal('');

  // Versión que tenía el registro cuando lo abrimos (se usa al editar)
  private versionLeida = 0;

  constructor(
    private servicio: BitacoraService,
    private ruta: ActivatedRoute,
    private router: Router,
    private toasts: ToastController
  ) { }

  ngOnInit() {
    // Lee el :id de la URL. En /bitacora/nuevo no hay id, así que queda null
    this.id = this.ruta.snapshot.paramMap.get('id');

    if (this.id !== null) {
      this.cargar(this.id);
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
        this.texto.set(registro.texto);
        this.versionLeida = registro.version;
      }
    } catch (error: any) {
      this.error.set(error.message);
    }
    this.cargando.set(false);
  }

  // Guarda: edita si hay id, crea si no hay
  async guardar() {
    this.guardando.set(true);
    try {
      let mensaje = '';
      if (this.id !== null) {
        await this.servicio.editar(this.id, this.texto(), this.versionLeida);
        mensaje = 'Registro actualizado';
      } else {
        await this.servicio.crear(this.texto());
        mensaje = 'Registro creado';
      }

      const toast = await this.toasts.create({
        message: mensaje,
        duration: 2000,
        color: 'success',
      });
      await toast.present();

      // Vuelve a la lista
      this.router.navigateByUrl('/bitacora');
    } catch (error: any) {
      const toast = await this.toasts.create({
        message: error.message,
        duration: 4000,
        color: 'danger',
      });
      await toast.present();
    }
    this.guardando.set(false);
  }
}
