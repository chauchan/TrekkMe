import { ChangeDetectionStrategy, Component, OnInit, signal } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
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
import { ExcursionService } from '../../../services/excursion.service';

// Vista de terreno: lo mínimo que el rescatista necesita mientras busca, pensada para el teléfono.
// Muestra el mapa con la última ubicación del excursionista, la posición propia y un resumen del caso.
@Component({
  selector: 'app-terreno',
  templateUrl: './terreno.page.html',
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
export class TerrenoPage implements OnInit {

  // ID del caso (viene en la URL)
  id = '';

  // Estado de la pantalla
  caso = signal<Excursion | null>(null);
  cargando = signal(true);
  error = signal('');

  // Dirección del mapa que se muestra en el iframe (null si el caso no tiene ubicación)
  mapa = signal<SafeResourceUrl | null>(null);

  // Posición del rescatista, tomada del GPS del teléfono
  miLat = signal<number | null>(null);
  miLng = signal<number | null>(null);
  buscandoPosicion = signal(false);
  errorPosicion = signal('');

  constructor(
    private servicio: ExcursionService,
    private rutaActual: ActivatedRoute,
    private sanitizador: DomSanitizer
  ) { }

  ngOnInit() {
    // Lee el :id de la URL /rescatista/casos/:id/terreno
    this.id = this.rutaActual.snapshot.paramMap.get('id')!;
    this.cargar();
    this.buscarMiPosicion();
  }

  // Trae el caso desde el servicio. También sirve para revisar si llegó una ubicación nueva
  async cargar() {
    this.cargando.set(true);
    this.error.set('');
    try {
      const caso = await this.servicio.obtener(this.id);
      if (caso === null) {
        this.error.set('El caso no existe.');
      } else {
        this.caso.set(caso);
        if (caso.ultimaLat !== null && caso.ultimaLng !== null) {
          this.mapa.set(this.armarMapa(caso.ultimaLat, caso.ultimaLng));
        }
      }
    } catch (error: any) {
      this.error.set(error.message);
    }
    this.cargando.set(false);
  }

  // Arma la dirección de un mapa de OpenStreetMap centrado en un punto, con un marcador
  armarMapa(lat: number, lng: number): SafeResourceUrl {
    // "bbox" es el rectángulo que se ve: un poco al oeste, sur, este y norte del punto
    const margen = 0.01;
    const bbox = (lng - margen) + ',' + (lat - margen) + ',' + (lng + margen) + ',' + (lat + margen);
    const direccion = 'https://www.openstreetmap.org/export/embed.html?bbox=' + bbox + '&marker=' + lat + ',' + lng;

    // Angular bloquea las direcciones de un iframe por seguridad: hay que marcarla como confiable
    return this.sanitizador.bypassSecurityTrustResourceUrl(direccion);
  }

  // Pide al teléfono (o al navegador) la posición actual del rescatista
  buscarMiPosicion() {
    this.buscandoPosicion.set(true);
    this.errorPosicion.set('');

    navigator.geolocation.getCurrentPosition(
      (posicion) => {
        this.miLat.set(posicion.coords.latitude);
        this.miLng.set(posicion.coords.longitude);
        this.buscandoPosicion.set(false);
      },
      () => {
        this.errorPosicion.set('No se pudo obtener tu posición. Revisa el permiso de ubicación.');
        this.buscandoPosicion.set(false);
      }
    );
  }

  // Distancia en línea recta entre dos puntos, en metros (fórmula del haversine)
  distanciaEnMetros(lat1: number, lng1: number, lat2: number, lng2: number): number {
    const radioTierra = 6371000;
    const aRadianes = Math.PI / 180;
    const diferenciaLat = (lat2 - lat1) * aRadianes;
    const diferenciaLng = (lng2 - lng1) * aRadianes;
    const a =
      Math.sin(diferenciaLat / 2) * Math.sin(diferenciaLat / 2) +
      Math.cos(lat1 * aRadianes) * Math.cos(lat2 * aRadianes) *
      Math.sin(diferenciaLng / 2) * Math.sin(diferenciaLng / 2);
    return radioTierra * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  // Texto con la distancia entre el rescatista y la última ubicación del excursionista
  textoDistancia(caso: Excursion): string {
    const miLat = this.miLat();
    const miLng = this.miLng();
    if (miLat === null || miLng === null || caso.ultimaLat === null || caso.ultimaLng === null) {
      return '';
    }

    const metros = this.distanciaEnMetros(miLat, miLng, caso.ultimaLat, caso.ultimaLng);
    if (metros < 1000) {
      return 'Estás a ' + Math.round(metros) + ' metros de la última ubicación.';
    }
    const kilometros = Math.round(metros / 100) / 10;
    return 'Estás a ' + kilometros + ' km de la última ubicación.';
  }
}
