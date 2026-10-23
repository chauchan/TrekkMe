import { collection, doc, getDoc, getDocs, query, runTransaction, where } from 'firebase/firestore';
import { Excursion } from '../models/excursion';
import { aExcursion } from './conversores';
import { traducirError } from './errores';
import { COLECCION_EXCURSIONES, db } from './firebase';

// Consultas a la colección "excursiones" que necesitan los servicios de varias páginas.
// No son un servicio: son funciones que cada servicio importa, para no repetir código.
// Cuando exista Laravel, estas funciones se reemplazan por llamadas a la API.

// Excursiones que están en alguno de los estados pedidos
export async function buscarPorEstados(estados: string[]): Promise<Excursion[]> {
  try {
    const consulta = query(collection(db, COLECCION_EXCURSIONES), where('estado', 'in', estados));
    const resultado = await getDocs(consulta);
    return resultado.docs.map((documento) => aExcursion(documento.id, documento.data()));
  } catch (error) {
    throw traducirError(error);
  }
}

// Excursiones asignadas a un rescatista (en cualquier estado)
export async function buscarPorRescatista(rescatistaId: string): Promise<Excursion[]> {
  try {
    const consulta = query(collection(db, COLECCION_EXCURSIONES), where('rescatistaId', '==', rescatistaId));
    const resultado = await getDocs(consulta);
    return resultado.docs.map((documento) => aExcursion(documento.id, documento.data()));
  } catch (error) {
    throw traducirError(error);
  }
}

// Una excursión por su ID, o null si no existe
export async function obtenerPorId(id: string): Promise<Excursion | null> {
  try {
    const documento = await getDoc(doc(db, COLECCION_EXCURSIONES, id));
    if (!documento.exists()) {
      return null;
    }
    return aExcursion(documento.id, documento.data());
  } catch (error) {
    throw traducirError(error);
  }
}

// Aplica los cambios SOLO si la excursión sigue en el estado que vimos en pantalla.
// Así dos personas no pueden gestionar el mismo aviso al mismo tiempo.
// "cambios" trae el estado nuevo y, si hace falta, otros campos (por ejemplo el rescatista asignado).
export async function cambiarEstadoSi(id: string, estadoEsperado: string, cambios: Record<string, unknown>): Promise<void> {
  try {
    const referencia = doc(db, COLECCION_EXCURSIONES, id);

    await runTransaction(db, async (transaccion) => {
      const documento = await transaccion.get(referencia);

      if (!documento.exists()) {
        throw new Error('El aviso ya no existe.');
      }
      if (documento.data()['estado'] !== estadoEsperado) {
        throw new Error('Otra persona ya gestionó este aviso. Vuelve a abrirlo.');
      }

      transaccion.update(referencia, cambios);
    });
  } catch (error) {
    throw traducirError(error);
  }
}
