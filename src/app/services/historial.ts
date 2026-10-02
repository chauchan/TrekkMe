import { Injectable, inject, signal } from '@angular/core';
import { Router, NavigationEnd } from '@angular/router';
import { filter } from 'rxjs';

export interface Miga {
  nombre: string;
  url: string;
}

@Injectable({ providedIn: 'root' })
export class HistorialService {
  private router = inject(Router);

  // El nombre que se muestra para cada ruta
  private nombres: Record<string, string> = {
    '/tabs/tab1': 'Tab 1',
    '/tabs/tab2': 'Tab 2',
    '/tabs/tab3': 'Tab 3',
  };

  // La lista de pasos (se actualiza sola en pantalla)
  migas = signal<Miga[]>([]);

  constructor() {
    this.agregar(this.router.url);
    // Cada vez que termina una navegación, anotamos la nueva página
    this.router.events
      .pipe(filter((e) => e instanceof NavigationEnd))
      .subscribe((e) => this.agregar(e.urlAfterRedirects));
  }

  private agregar(url: string) {
    const nombre = this.nombres[url];
    if (!nombre) return;

    const lista = this.migas();
    const posicion = lista.findIndex((m) => m.url === url);

    if (posicion >= 0) {
      // Ya pasé por aquí: corto la lista hasta esa página
      this.migas.set(lista.slice(0, posicion + 1));
    } else {
      // Página nueva: la agrego al final
      this.migas.set([...lista, { nombre, url }]);
    }
  }
}