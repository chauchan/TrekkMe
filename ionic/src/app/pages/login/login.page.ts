import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { IonButton, IonContent, IonHeader, IonTitle, IonToolbar } from '@ionic/angular';

// Por ahora no hay usuario ni contraseña: se elige el perfil con un botón.
// Cuando exista el login real, el perfil lo dirá el servidor.
@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [RouterLink, IonButton, IonContent, IonHeader, IonTitle, IonToolbar],
})
export class LoginPage {

  constructor() { }

}
