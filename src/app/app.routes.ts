import { Routes } from '@angular/router';

export const routes: Routes = [
  // Al entrar a la app, vamos directo a la bitácora
  {
    path: '',
    redirectTo: 'bitacora',
    pathMatch: 'full',
  },
  // Lista de registros
  {
    path: 'bitacora',
    loadComponent: () => import('./pages/bitacora-lista/bitacora-lista.page').then((m) => m.BitacoraListaPage),
  },
  // Formulario para crear (tiene que ir antes de "bitacora/:id")
  {
    path: 'bitacora/nuevo',
    loadComponent: () => import('./pages/bitacora-form/bitacora-form.page').then((m) => m.BitacoraFormPage),
  },
  // El mismo formulario, para editar el registro con ese id
  {
    path: 'bitacora/:id',
    loadComponent: () => import('./pages/bitacora-form/bitacora-form.page').then((m) => m.BitacoraFormPage),
  },
];
