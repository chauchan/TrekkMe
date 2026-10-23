import { initializeApp } from 'firebase/app';
import { getFirestore } from 'firebase/firestore';
import { environment } from '../../environments/environment';

// Conecta la app con nuestro proyecto de Firebase
const app = initializeApp(environment.firebase);

// Base de datos Firestore. La usan las funciones de core/ y los servicios de cada página
export const db = getFirestore(app);

// Nombres de las colecciones
export const COLECCION_EXCURSIONES = 'excursiones';
export const COLECCION_BITACORA = 'bitacora';
