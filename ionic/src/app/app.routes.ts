import { Routes } from '@angular/router';
import { perfilGuard } from './core/perfil.guard';

export const routes: Routes = [
  // Al entrar a la app, vamos al inicio de sesión
  {
    path: '',
    redirectTo: 'login',
    pathMatch: 'full',
  },
  {
    path: 'login',
    loadComponent: () => import('./pages/login/login.page').then((m) => m.LoginPage),
  },

  // ----- Carabinero -----

  // Bandeja del turno: avisos activos
  {
    path: 'carabinero/bandeja',
    canActivate: [perfilGuard('carabinero')],
    loadComponent: () => import('./pages/carabinero/bandeja/bandeja.page').then((m) => m.BandejaPage),
  },
  // Detalle de un aviso, con las acciones de la alerta
  {
    path: 'carabinero/aviso/:id',
    canActivate: [perfilGuard('carabinero')],
    loadComponent: () => import('./pages/carabinero/aviso/aviso.page').then((m) => m.AvisoPage),
  },
  // Histórico de avisos cerrados
  {
    path: 'carabinero/historico',
    canActivate: [perfilGuard('carabinero')],
    loadComponent: () => import('./pages/carabinero/historico/historico.page').then((m) => m.HistoricoPage),
  },

  // ----- Rescatista -----

  // Lista de casos: desde aquí se abre la bitácora de cada caso
  {
    path: 'rescatista/casos',
    canActivate: [perfilGuard('rescatista')],
    loadComponent: () => import('./pages/rescatista/casos/casos.page').then((m) => m.CasosPage),
  },
  // Bitácora de un caso: lista de registros
  {
    path: 'rescatista/casos/:casoId/bitacora',
    canActivate: [perfilGuard('rescatista')],
    loadComponent: () => import('./pages/rescatista/bitacora-lista/bitacora-lista.page').then((m) => m.BitacoraListaPage),
  },
  // Bitácora de un caso: formulario para crear (tiene que ir antes de ".../bitacora/:id")
  {
    path: 'rescatista/casos/:casoId/bitacora/nuevo',
    canActivate: [perfilGuard('rescatista')],
    loadComponent: () => import('./pages/rescatista/bitacora-form/bitacora-form.page').then((m) => m.BitacoraFormPage),
  },
  // Bitácora de un caso: el mismo formulario, para editar el registro con ese id
  {
    path: 'rescatista/casos/:casoId/bitacora/:id',
    canActivate: [perfilGuard('rescatista')],
    loadComponent: () => import('./pages/rescatista/bitacora-form/bitacora-form.page').then((m) => m.BitacoraFormPage),
  },
  // Ficha de búsqueda de un caso
  {
    path: 'rescatista/casos/:id',
    canActivate: [perfilGuard('rescatista')],
    loadComponent: () => import('./pages/rescatista/ficha/ficha.page').then((m) => m.FichaPage),
  },
  // Vista de terreno de un caso
  {
    path: 'rescatista/casos/:id/terreno',
    canActivate: [perfilGuard('rescatista')],
    loadComponent: () => import('./pages/rescatista/terreno/terreno.page').then((m) => m.TerrenoPage),
  },
];
