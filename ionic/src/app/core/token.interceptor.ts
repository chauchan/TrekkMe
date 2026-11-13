import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, from, switchMap, throwError } from 'rxjs';
import { cerrarSesion, obtenerToken } from './sesion';

// Pone el ID token de Firebase en cada llamada a la API y, si el servidor responde 401, manda al login
export const tokenInterceptor: HttpInterceptorFn = (req, next) => {
  const router = inject(Router);

  return from(obtenerToken()).pipe(
    switchMap((token) => {
      const headers: Record<string, string> = { Accept: 'application/json' };

      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      return next(req.clone({ setHeaders: headers }));
    }),
    catchError((error) => {
      if (error instanceof HttpErrorResponse && error.status === 401) {
        cerrarSesion();
        router.navigateByUrl('/login');
      }
      return throwError(() => error);
    })
  );
};
