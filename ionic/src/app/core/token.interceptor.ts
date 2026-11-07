import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { cerrarSesion, obtenerToken } from './sesion';

// Pone el token en cada llamada a la API y, si el servidor responde 401, manda al login
export const tokenInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);
  const token = obtenerToken();
  const headers: Record<string, string> = { Accept: 'application/json' };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  return next(req.clone({ setHeaders: headers })).pipe(
    catchError((error) => {
      if (error instanceof HttpErrorResponse && error.status === 401) {
        cerrarSesion();
        router.navigateByUrl('/login');
      }
      return throwError(() => error);
    })
  );
};
