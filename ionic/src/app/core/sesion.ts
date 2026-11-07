import { Preferences } from '@capacitor/preferences';
import { onAuthStateChanged, signInWithCustomToken, signOut } from 'firebase/auth';
import { auth } from './firebase';

export type Perfil = 'carabinero' | 'rescatista';

// Datos de la sesión actual (en memoria)
let token: string | null = null;
let perfil: Perfil | null = null;
let usuarioId: string | null = null;
let jurisdiccionId: string | null = null;

// Después del login: guarda la sesión de la API y entra a Firebase con el token personalizado
export async function guardarSesion(datos: {
  token: string;
  firebaseToken: string;
  perfil: Perfil;
  usuarioId: string;
  jurisdiccionId: string | null;
}) {
  token = datos.token;
  perfil = datos.perfil;
  usuarioId = datos.usuarioId;
  jurisdiccionId = datos.jurisdiccionId;

  await signInWithCustomToken(auth, datos.firebaseToken);
  await Preferences.set({
    key: 'sesion',
    value: JSON.stringify({ token, perfil, usuarioId, jurisdiccionId }),
  });
}

// Al abrir la app: recupera la sesión guardada y espera a que Firebase restaure la suya.
// Se lee con los MISMOS nombres con que se guardó.
export async function cargarSesion() {
  const { value } = await Preferences.get({ key: 'sesion' });
  if (value) {
    const sesion = JSON.parse(value);
    token = sesion.token;
    perfil = sesion.perfil;
    usuarioId = sesion.usuarioId;
    jurisdiccionId = sesion.jurisdiccionId;
  }

  // Firebase tarda un momento en recuperar su sesión: esperamos el primer aviso
  await new Promise<void>((listo) => {
    const dejarDeEscuchar = onAuthStateChanged(auth, () => {
      dejarDeEscuchar();
      listo();
    });
  });
}

export async function cerrarSesion() {
  token = null;
  perfil = null;
  usuarioId = null;
  jurisdiccionId = null;
  await signOut(auth);
  await Preferences.remove({ key: 'sesion' });
}

export const obtenerToken = () => token;
export const obtenerPerfil = () => perfil;
export const obtenerUsuarioId = () => usuarioId;
export const obtenerJurisdiccionId = () => jurisdiccionId;
