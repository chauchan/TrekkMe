import { initializeApp } from 'firebase/app';
import { getFirestore } from 'firebase/firestore';
import { environment } from '../environments/environment';

// Conecta la app con nuestro proyecto de Firebase
const app = initializeApp(environment.firebase);

// Base de datos Firestore, la usa el servicio de bitácora
export const db = getFirestore(app);
