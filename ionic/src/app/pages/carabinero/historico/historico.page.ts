import { ChangeDetectionStrategy, Component, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { Subscription } from 'rxjs';
import {
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
} from '@ionic/angular';
import { Aviso } from '../../../models/excursion';
import { HistoricoService } from './historico.service';

// Histórico: los avisos ya cerrados. Reemplaza el libro de guardia en papel
@Component({
  selector: 'app-historico',
  templateUrl: './historico.page.html',
  // El servicio es propio de esta página: se crea con ella y ninguna otra lo usa
  providers: [HistoricoService],
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
    IonMenuButton,
    IonSpinner,
    IonText,
    IonTitle,
    IonToolbar,
  ],
})
export class HistoricoPage {

  // Estado de la pantalla
  avisos = signal<Aviso[]>([]);
  cargando = signal(true);
  error = signal('');

  // Conexión en vivo con Firestore: se corta al salir de la página
  private suscripcion?: Subscription;

  constructor(private servicio: HistoricoService) { }

  // Ionic lo llama cada vez que se entra a la página
  ionViewWillEnter() {
    this.cargar();
  }

  // Ionic lo llama al salir de la página: deja de escuchar Firestore
  ionViewWillLeave() {
    this.suscripcion?.unsubscribe();
  }

  // Escucha los avisos cerrados: la lista se actualiza sola
  cargar() {
    this.suscripcion?.unsubscribe();   // por si se llama desde "Reintentar"
    this.cargando.set(true);
    this.error.set('');

    this.suscripcion = this.servicio.listar().subscribe({
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
}
