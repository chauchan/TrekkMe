import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { doc } from 'firebase/firestore';
import { aExcursion } from '../../../core/conversores';
import { COLECCION_EXCURSIONES, db } from '../../../core/firebase';
import { escucharUno } from '../../../core/tiempo-real';
import { Excursion } from '../../../models/excursion';

// Un punto en el mapa
export interface Posicion {
  lat: number;
  lng: number;
}

// Servicio de la vista de terreno (actor: rescatista). Lo provee solo TerrenoPage.
@Injectable()
export class TerrenoService {

  // El caso, en vivo: la última ubicación y la batería se actualizan sin recargar
  obtener(id: string): Observable<Excursion | null> {
    return escucharUno(doc(db, COLECCION_EXCURSIONES, id), aExcursion);
  }

  // Pide al teléfono (o al navegador) la posición actual del rescatista.
  // Pendiente: cambiarlo por @capacitor/geolocation cuando se instale el plugin.
  miPosicion(): Promise<Posicion> {
    return new Promise((resolver, rechazar) => {
      navigator.geolocation.getCurrentPosition(
        (posicion) => resolver({ lat: posicion.coords.latitude, lng: posicion.coords.longitude }),
        () => rechazar(new Error('No se pudo obtener tu posición. Revisa el permiso de ubicación.'))
      );
    });
  }

  // Distancia en línea recta entre dos puntos, en metros (fórmula del haversine)
  distanciaEnMetros(desde: Posicion, hasta: Posicion): number {
    const radioTierra = 6371000;
    const aRadianes = Math.PI / 180;
    const diferenciaLat = (hasta.lat - desde.lat) * aRadianes;
    const diferenciaLng = (hasta.lng - desde.lng) * aRadianes;
    const a =
      Math.sin(diferenciaLat / 2) * Math.sin(diferenciaLat / 2) +
      Math.cos(desde.lat * aRadianes) * Math.cos(hasta.lat * aRadianes) *
      Math.sin(diferenciaLng / 2) * Math.sin(diferenciaLng / 2);
    return radioTierra * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }
}
