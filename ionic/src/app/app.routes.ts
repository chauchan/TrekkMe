import { Routes } from '@angular/router';

export const routes: Routes = [
  // Al entrar a la app, vamos a la pantalla donde se elige el perfil
  {
    path: '',
    redirectTo: 'login',
    pathMatch: 'full',
  },
  // Inicio de sesión: botones para entrar como carabinero o como rescatista
  {
    path: 'login',
    loadComponent: () => import('./pages/login/login.page').then((m) => m.LoginPage),
  },

  // ----- Carabinero -----

  // Bandeja del turno: avisos activos
  {
    path: 'carabinero/bandeja',
    loadComponent: () => import('./pages/carabinero/bandeja/bandeja.page').then((m) => m.BandejaPage),
  },
  // Detalle de un aviso, con las acciones de la alerta
  {
    path: 'carabinero/aviso/:id',
    loadComponent: () => import('./pages/carabinero/aviso/aviso.page').then((m) => m.AvisoPage),
  },
  // Histórico de avisos cerrados
  {
    path: 'carabinero/historico',
    loadComponent: () => import('./pages/carabinero/historico/historico.page').then((m) => m.HistoricoPage),
  },

  // ----- Rescatista -----

  // Lista de casos: desde aquí se abre la bitácora de cada caso
  {
    path: 'rescatista/casos',
    loadComponent: () => import('./pages/rescatista/casos/casos.page').then((m) => m.CasosPage),
  },
  // Bitácora de un caso: lista de registros
  {
    path: 'rescatista/casos/:casoId/bitacora',
    loadComponent: () => import('./pages/rescatista/bitacora-lista/bitacora-lista.page').then((m) => m.BitacoraListaPage),
  },
  // Bitácora de un caso: formulario para crear (tiene que ir antes de ".../bitacora/:id")
  {
    path: 'rescatista/casos/:casoId/bitacora/nuevo',
    loadComponent: () => import('./pages/rescatista/bitacora-form/bitacora-form.page').then((m) => m.BitacoraFormPage),
  },
  // Bitácora de un caso: el mismo formulario, para editar el registro con ese id
  {
    path: 'rescatista/casos/:casoId/bitacora/:id',
    loadComponent: () => import('./pages/rescatista/bitacora-form/bitacora-form.page').then((m) => m.BitacoraFormPage),
  },
  // Ficha de búsqueda de un caso
  {
    path: 'rescatista/casos/:id',
    loadComponent: () => import('./pages/rescatista/ficha/ficha.page').then((m) => m.FichaPage),
  },
  // Vista de terreno de un caso
  {
    path: 'rescatista/casos/:id/terreno',
    loadComponent: () => import('./pages/rescatista/terreno/terreno.page').then((m) => m.TerrenoPage),
  },
];
