import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { Perfil, obtenerPerfil } from './sesion';

// Deja entrar a la ruta solo si el usuario tiene el perfil esperado; si no, lo manda al login
export const perfilGuard = (esperado: Perfil): CanActivateFn => () =>
  obtenerPerfil() === esperado ? true : inject(Router).createUrlTree(['/login']);
