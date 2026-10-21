import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import {
  IonBackButton,
  IonButton,
  IonButtons,
  IonContent,
  IonHeader,
  IonItem,
  IonLabel,
  IonList,
  IonListHeader,
  IonMenuButton,
  IonSpinner,
  IonText,
  IonTitle,
  IonToolbar,
} from '@ionic/angular';
import { Excursion } from '../../../models/excursion';
import { FichaService } from './ficha.service';

// Ficha de búsqueda: todo lo que el rescatista necesita saber de la persona antes de salir a terreno.
// Es de solo lectura.
@Component({
  selector: 'app-ficha',
  templateUrl: './ficha.page.html',
  // El servicio es propio de esta página: se crea con ella y ninguna otra lo usa
  providers: [FichaService],
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
    IonListHeader,
    IonMenuButton,
    IonSpinner,
    IonText,
    IonTitle,
    IonToolbar,
  ],
})
export class FichaPage implements OnInit {

  // ID del caso (viene en la URL)
  id = '';

  // Estado de la pantalla
  caso = signal<Excursion | null>(null);
  cargando = signal(true);
  error = signal('');

  constructor(
    private servicio: FichaService,
    private rutaActual: ActivatedRoute
  ) { }

  ngOnInit() {
    // Lee el :id de la URL /rescatista/casos/:id
    this.id = this.rutaActual.snapshot.paramMap.get('id')!;
    this.cargar();
  }

  // Trae el caso desde el servicio
  async cargar() {
    this.cargando.set(true);
    this.error.set('');
    try {
      const caso = await this.servicio.obtener(this.id);
      if (caso === null) {
        this.error.set('El caso no existe.');
      } else {
        this.caso.set(caso);
      }
    } catch (error: any) {
      this.error.set(error.message);
    }
    this.cargando.set(false);
  }

  // Cuántas horas han pasado desde la hora comprometida de retorno (con un decimal)
  horasDeAtraso(retornoAt: Date): number {
    return this.servicio.horasDeAtraso(retornoAt);
  }

  // Arma el enlace para ver un punto en OpenStreetMap
  enlaceMapa(lat: number, lng: number): string {
    return 'https://www.openstreetmap.org/?mlat=' + lat + '&mlon=' + lng + '#map=15/' + lat + '/' + lng;
  }
}
