import { ChangeDetectionStrategy, Component, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
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

  constructor(private servicio: HistoricoService) { }

  // Ionic lo llama cada vez que se entra a la página
  ionViewWillEnter() {
    this.cargar();
  }

  // Pide los avisos cerrados y actualiza los signals
  cargar() {
    this.cargando.set(true);
    this.error.set('');

    this.servicio.listar().subscribe({
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
