import { environment } from '../../environments/environment';

// Arma la dirección completa de un endpoint de la API: url('/login') → http://localhost:8000/api/v1/login
export const url = (ruta: string) => `${environment.apiUrl}${ruta}`;
