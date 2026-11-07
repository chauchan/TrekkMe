import { ChangeDetectionStrategy, Component, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import {
  IonButton,
  IonContent,
  IonHeader,
  IonInput,
  IonItem,
  IonList,
  IonSpinner,
  IonText,
  IonTitle,
  IonToolbar,
} from '@ionic/angular';
import { LoginService } from './login.service';

@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
  // El servicio es propio de esta página: se crea con ella y ninguna otra lo usa
  providers: [LoginService],
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    FormsModule,
    IonButton,
    IonContent,
    IonHeader,
    IonInput,
    IonItem,
    IonList,
    IonSpinner,
    IonText,
    IonTitle,
    IonToolbar,
  ],
})
export class LoginPage {

  // Campos del formulario
  correo = signal('');
  clave = signal('');

  // Estado de la pantalla
  entrando = signal(false);
  error = signal('');

  constructor(
    private servicio: LoginService,
    private router: Router
  ) { }

  // Inicia sesión y lleva a la primera pantalla del perfil
  async entrar() {
    this.entrando.set(true);
    this.error.set('');
    try {
      const perfil = await this.servicio.iniciarSesion(this.correo().trim(), this.clave());
      this.clave.set('');
      if (perfil === 'carabinero') {
        this.router.navigateByUrl('/carabinero/bandeja');
      } else {
        this.router.navigateByUrl('/rescatista/casos');
      }
    } catch (error: any) {
      this.error.set(error.message);
    }
    this.entrando.set(false);
  }
}
