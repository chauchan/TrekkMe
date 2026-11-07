import { initializeApp } from 'firebase/app';
import { getAuth } from 'firebase/auth';
import { initializeFirestore, persistentLocalCache } from 'firebase/firestore';
import { environment } from '../../environments/environment';

// Conecta la app con nuestro proyecto de Firebase
const app = initializeApp(environment.firebase);

// Sesión de Firebase: se inicia con el token personalizado que entrega el login de la API
export const auth = getAuth(app);

// Base de datos Firestore, con caché local: la app sigue mostrando lo último que vio
// aunque no haya señal (importante en terreno)
export const db = initializeFirestore(app, { localCache: persistentLocalCache() });

// Nombres de las colecciones
export const COLECCION_AVISOS = 'avisos';            // lo que ve el carabinero (sin datos médicos)
export const COLECCION_EXCURSIONES = 'excursiones';  // ficha completa: solo el rescatista asignado
export const COLECCION_BITACORA = 'bitacora';
