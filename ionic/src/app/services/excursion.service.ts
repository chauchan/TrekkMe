import { Injectable } from '@angular/core';
import { Observable, from } from 'rxjs';
import {
  Timestamp,
  collection,
  doc,
  getDoc,
  getDocs,
  query,
  runTransaction,
  setDoc,
  where,
} from 'firebase/firestore';
import { db } from '../firebase';
import {
  Acompanante,
  ESTADO_CERRADA,
  ESTADO_CIERRE_PENDIENTE,
  ESTADO_EN_ALERTA,
  ESTADO_EN_CURSO,
  ESTADO_EN_OPERATIVO,
  ESTADO_PLANIFICADA,
  Excursion,
} from '../models/excursion';

// Nombre de la colección en Firestore
const COLECCION = 'excursiones';

// Habla con Firestore para todo lo que tiene que ver con excursiones (avisos y casos)
@Injectable({
  providedIn: 'root',
})
export class ExcursionService {

  constructor() { }

  // Devuelve las excursiones que están en alguno de los estados pedidos
  listar(estados: string[]): Observable<Excursion[]> {
    return from(this.buscarExcursiones(estados));
  }

  // Hace la consulta a Firestore (listar() la convierte en Observable)
  private async buscarExcursiones(estados: string[]): Promise<Excursion[]> {
    try {
      const consulta = query(
        collection(db, COLECCION),
        where('estado', 'in', estados)
      );
      const resultado = await getDocs(consulta);

      // Convierte cada documento en un objeto Excursion
      const excursiones: Excursion[] = [];
      for (const documento of resultado.docs) {
        excursiones.push(this.convertir(documento.id, documento.data()));
      }
      return excursiones;
    } catch (error) {
      throw this.error(error);
    }
  }

  // Devuelve los casos asignados a un rescatista: los que están en operativo
  // o esperando que Carabineros confirme el cierre
  listarCasos(rescatistaId: string): Observable<Excursion[]> {
    return from(this.buscarCasos(rescatistaId));
  }

  // Hace la consulta a Firestore (listarCasos() la convierte en Observable)
  private async buscarCasos(rescatistaId: string): Promise<Excursion[]> {
    try {
      const consulta = query(
        collection(db, COLECCION),
        where('rescatistaId', '==', rescatistaId)
      );
      const resultado = await getDocs(consulta);

      // De las excursiones de este rescatista, nos quedamos con las que siguen abiertas
      const casos: Excursion[] = [];
      for (const documento of resultado.docs) {
        const excursion = this.convertir(documento.id, documento.data());
        if (excursion.estado === ESTADO_EN_OPERATIVO || excursion.estado === ESTADO_CIERRE_PENDIENTE) {
          casos.push(excursion);
        }
      }
      return casos;
    } catch (error) {
      throw this.error(error);
    }
  }

  // Devuelve una excursión por su ID, o null si no existe
  async obtener(id: string): Promise<Excursion | null> {
    try {
      const documento = await getDoc(doc(db, COLECCION, id));
      if (!documento.exists()) {
        return null;
      }
      return this.convertir(documento.id, documento.data());
    } catch (error) {
      throw this.error(error);
    }
  }

  // Cambia el estado SOLO si la excursión sigue en el estado que vimos en pantalla.
  // Así dos personas no pueden gestionar el mismo aviso al mismo tiempo.
  async cambiarEstado(id: string, estadoEsperado: string, estadoNuevo: string): Promise<void> {
    try {
      const referencia = doc(db, COLECCION, id);

      await runTransaction(db, async (transaccion) => {
        const documento = await transaccion.get(referencia);

        if (!documento.exists()) {
          throw new Error('El aviso ya no existe.');
        }
        if (documento.data()['estado'] !== estadoEsperado) {
          throw new Error('Otra persona ya gestionó este aviso. Vuelve a abrirlo.');
        }

        transaccion.update(referencia, {
          estado: estadoNuevo,
        });
      });
    } catch (error) {
      throw this.error(error);
    }
  }

  // Escala un aviso a operativo y lo asigna a un rescatista.
  // Solo funciona si el aviso sigue en alerta.
  async escalar(id: string, rescatistaId: string): Promise<void> {
    try {
      const referencia = doc(db, COLECCION, id);

      await runTransaction(db, async (transaccion) => {
        const documento = await transaccion.get(referencia);

        if (!documento.exists()) {
          throw new Error('El aviso ya no existe.');
        }
        if (documento.data()['estado'] !== ESTADO_EN_ALERTA) {
          throw new Error('Otra persona ya gestionó este aviso. Vuelve a abrirlo.');
        }

        transaccion.update(referencia, {
          estado: ESTADO_EN_OPERATIVO,
          rescatistaId: rescatistaId,
        });
      });
    } catch (error) {
      throw this.error(error);
    }
  }

  // Crea (o reinicia) nueve excursiones de ejemplo, una por cada situación que queremos probar.
  // En la versión final los avisos los crea el excursionista desde su aplicación.
  async cargarEjemplos(): Promise<void> {
    try {
      // En operativo, asignado al rescatista actual. Tiene bitácora de ejemplo
      await setDoc(doc(db, COLECCION, 'exc-001'), {
        excursionista: 'Camila Rojas Soto',
        rut: '20.000.001-1',
        telefono: '+56 9 5555 0101',
        edad: 29,
        experiencia: 'Intermedio',
        condicionesMedicas: 'Asma',
        acompanantes: [
          {
            nombre: 'Diego Rojas Soto',
            rut: '20.000.011-1',
            edad: 26,
            condicionesMedicas: 'Ninguna',
            tieneCuenta: true,
            telefono: '+56 9 5555 0201',
            experiencia: 'Principiante',
          },
        ],
        ruta: 'PN Nonguén - Sendero Los Canelos',
        contactoNombre: 'Jorge Rojas (padre)',
        contactoTelefono: '+56 9 5555 0102',
        salidaAt: this.haceHoras(30),
        retornoAt: this.haceHoras(22),
        estado: ESTADO_EN_OPERATIVO,
        rescatistaId: 'resc-001',
        fotoUrl: 'assets/ejemplos/foto-ejemplo.svg',
        detalle: 'Chaqueta azul y mochila gris de 30 litros. Pelo castaño largo, usa lentes. Va con su perro, un labrador negro.',
        llevaCarpa: false,
        colorCarpa: '',
        llevaLinterna: true,
        ultimaLat: -36.8795,
        ultimaLng: -72.9731,
        ultimaUbicacionAt: this.haceHoras(25),
        bateria: 12,
      });

      // En alerta: sirve para probar "Escalar a operativo"
      await setDoc(doc(db, COLECCION, 'exc-002'), {
        excursionista: 'Matías Fuentes Vera',
        rut: '20.000.002-2',
        telefono: '+56 9 5555 0103',
        edad: 41,
        experiencia: 'Avanzado',
        condicionesMedicas: 'Ninguna',
        acompanantes: [],
        ruta: 'Laguna del Laja - Sierra Velluda',
        contactoNombre: 'Daniela Vera (hermana)',
        contactoTelefono: '+56 9 5555 0104',
        salidaAt: this.haceHoras(11),
        retornoAt: this.haceHoras(2),
        estado: ESTADO_EN_ALERTA,
        rescatistaId: '',
        fotoUrl: 'assets/ejemplos/foto-ejemplo.svg',
        detalle: 'Chaqueta roja, casco blanco y piolet. Barba y gorro de lana negro. Llevo comida para dos días.',
        llevaCarpa: true,
        colorCarpa: 'Naranja',
        llevaLinterna: true,
        ultimaLat: -37.4702,
        ultimaLng: -71.4188,
        ultimaUbicacionAt: this.haceHoras(5),
        bateria: 34,
      });

      // En curso: todavía no vence su hora de retorno
      await setDoc(doc(db, COLECCION, 'exc-003'), {
        excursionista: 'Valentina Muñoz Paredes',
        rut: '20.000.003-3',
        telefono: '+56 9 5555 0105',
        edad: 35,
        experiencia: 'Principiante',
        condicionesMedicas: 'Ninguna',
        acompanantes: [
          {
            nombre: 'Luis Muñoz Tapia',
            rut: '20.000.012-2',
            edad: 37,
            condicionesMedicas: 'Ninguna',
            tieneCuenta: true,
            telefono: '+56 9 5555 0202',
            experiencia: 'Intermedio',
          },
          {
            nombre: 'Sofía Muñoz Muñoz',
            rut: '20.000.013-3',
            edad: 12,
            condicionesMedicas: 'Alergia al maní',
            tieneCuenta: false,
            telefono: '',
            experiencia: '',
          },
          {
            nombre: 'Benjamín Muñoz Muñoz',
            rut: '20.000.014-4',
            edad: 9,
            condicionesMedicas: 'Ninguna',
            tieneCuenta: false,
            telefono: '',
            experiencia: '',
          },
        ],
        ruta: 'PN Nahuelbuta - Piedra del Águila',
        contactoNombre: 'Luis Muñoz (esposo)',
        contactoTelefono: '+56 9 5555 0106',
        salidaAt: this.haceHoras(3),
        retornoAt: this.haceHoras(-4),
        estado: ESTADO_EN_CURSO,
        rescatistaId: '',
        fotoUrl: '',
        detalle: '',
        llevaCarpa: false,
        colorCarpa: '',
        llevaLinterna: false,
        ultimaLat: -37.8235,
        ultimaLng: -73.0312,
        ultimaUbicacionAt: this.haceHoras(1),
        bateria: 78,
      });

      // Planificada: todavía no sale, por eso no tiene ubicación
      await setDoc(doc(db, COLECCION, 'exc-004'), {
        excursionista: 'Ignacio Salazar Díaz',
        rut: '20.000.004-4',
        telefono: '+56 9 5555 0107',
        edad: 23,
        experiencia: 'Principiante',
        condicionesMedicas: 'Alergia a picaduras de abeja',
        acompanantes: [
          {
            nombre: 'Tomás Garrido Lagos',
            rut: '20.000.015-5',
            edad: 23,
            condicionesMedicas: 'Ninguna',
            tieneCuenta: true,
            telefono: '+56 9 5555 0203',
            experiencia: 'Principiante',
          },
          {
            nombre: 'Catalina Ríos Peña',
            rut: '20.000.016-6',
            edad: 22,
            condicionesMedicas: 'Epilepsia controlada',
            tieneCuenta: false,
            telefono: '',
            experiencia: '',
          },
        ],
        ruta: 'PN Nonguén - Sendero Los Copihues',
        contactoNombre: 'Paula Díaz (madre)',
        contactoTelefono: '+56 9 5555 0108',
        salidaAt: this.haceHoras(-2),
        retornoAt: this.haceHoras(-8),
        estado: ESTADO_PLANIFICADA,
        rescatistaId: '',
        fotoUrl: '',
        detalle: 'Polerón amarillo. Vamos tres amigos de la universidad, todos con mochilas chicas.',
        llevaCarpa: false,
        colorCarpa: '',
        llevaLinterna: true,
        ultimaLat: null,
        ultimaLng: null,
        ultimaUbicacionAt: null,
        bateria: null,
      });

      // Cerrada: aparece solo en el histórico
      await setDoc(doc(db, COLECCION, 'exc-005'), {
        excursionista: 'Fernanda Castro Leal',
        rut: '20.000.005-5',
        telefono: '+56 9 5555 0109',
        edad: 52,
        experiencia: 'Avanzado',
        condicionesMedicas: 'Hipertensión',
        acompanantes: [
          {
            nombre: 'Pedro Castro Leal',
            rut: '20.000.017-7',
            edad: 55,
            condicionesMedicas: 'Ninguna',
            tieneCuenta: false,
            telefono: '',
            experiencia: '',
          },
        ],
        ruta: 'PN Nahuelbuta - Piedra del Águila',
        contactoNombre: 'Pedro Castro (hermano)',
        contactoTelefono: '+56 9 5555 0110',
        salidaAt: this.haceHoras(52),
        retornoAt: this.haceHoras(45),
        estado: ESTADO_CERRADA,
        rescatistaId: '',
        fotoUrl: 'assets/ejemplos/foto-ejemplo.svg',
        detalle: 'Chaqueta negra y bastones de trekking.',
        llevaCarpa: true,
        colorCarpa: 'Verde',
        llevaLinterna: true,
        ultimaLat: -37.8201,
        ultimaLng: -73.0297,
        ultimaUbicacionAt: this.haceHoras(46),
        bateria: 55,
      });

      // En operativo hace tres días, asignado al rescatista actual. Su teléfono nunca envió ubicación
      await setDoc(doc(db, COLECCION, 'exc-006'), {
        excursionista: 'Roberto Sepúlveda Araya',
        rut: '20.000.006-6',
        telefono: '+56 9 5555 0111',
        edad: 67,
        experiencia: 'Intermedio',
        condicionesMedicas: 'Diabetes tipo 2',
        acompanantes: [],
        ruta: 'Laguna del Laja - Sierra Velluda',
        contactoNombre: 'Marcela Araya (hija)',
        contactoTelefono: '+56 9 5555 0112',
        salidaAt: this.haceHoras(80),
        retornoAt: this.haceHoras(72),
        estado: ESTADO_EN_OPERATIVO,
        rescatistaId: 'resc-001',
        fotoUrl: 'assets/ejemplos/foto-ejemplo.svg',
        detalle: 'Parka gris y sombrero de ala ancha. Camino lento por una lesión en la rodilla. Llevo insulina en la mochila.',
        llevaCarpa: true,
        colorCarpa: 'Azul',
        llevaLinterna: true,
        ultimaLat: null,
        ultimaLng: null,
        ultimaUbicacionAt: null,
        bateria: null,
      });

      // Cierre pendiente: sirve para probar "Confirmar cierre" y "Rechazar cierre"
      await setDoc(doc(db, COLECCION, 'exc-007'), {
        excursionista: 'Javiera Contreras Mella',
        rut: '20.000.007-7',
        telefono: '+56 9 5555 0113',
        edad: 31,
        experiencia: 'Avanzado',
        condicionesMedicas: 'Ninguna',
        acompanantes: [
          {
            nombre: 'Cristóbal Mella Jara',
            rut: '20.000.018-8',
            edad: 33,
            condicionesMedicas: 'Ninguna',
            tieneCuenta: true,
            telefono: '+56 9 5555 0204',
            experiencia: 'Avanzado',
          },
        ],
        ruta: 'PN Nonguén - Sendero Los Copihues',
        contactoNombre: 'Cristóbal Mella (pareja)',
        contactoTelefono: '+56 9 5555 0114',
        salidaAt: this.haceHoras(40),
        retornoAt: this.haceHoras(33),
        estado: ESTADO_CIERRE_PENDIENTE,
        rescatistaId: 'resc-001',
        fotoUrl: 'assets/ejemplos/foto-ejemplo.svg',
        detalle: '',
        llevaCarpa: false,
        colorCarpa: '',
        llevaLinterna: true,
        ultimaLat: -36.8842,
        ultimaLng: -72.9655,
        ultimaUbicacionAt: this.haceHoras(30),
        bateria: 41,
      });

      // En operativo, pero asignado a OTRO rescatista: no debe aparecer en "Casos asignados"
      await setDoc(doc(db, COLECCION, 'exc-008'), {
        excursionista: 'Sebastián Vidal Ortiz',
        rut: '20.000.008-8',
        telefono: '+56 9 5555 0115',
        edad: 19,
        experiencia: 'Principiante',
        condicionesMedicas: 'Ninguna',
        acompanantes: [
          {
            nombre: 'Martín Vidal Ortiz',
            rut: '20.000.019-9',
            edad: 17,
            condicionesMedicas: 'Ninguna',
            tieneCuenta: false,
            telefono: '',
            experiencia: '',
          },
          {
            nombre: 'Agustín Pérez Soto',
            rut: '20.000.020-K',
            edad: 19,
            condicionesMedicas: 'Asma',
            tieneCuenta: true,
            telefono: '+56 9 5555 0205',
            experiencia: 'Principiante',
          },
        ],
        ruta: 'PN Nahuelbuta - Piedra del Águila',
        contactoNombre: 'Rosa Ortiz (madre)',
        contactoTelefono: '+56 9 5555 0116',
        salidaAt: this.haceHoras(20),
        retornoAt: this.haceHoras(14),
        estado: ESTADO_EN_OPERATIVO,
        rescatistaId: 'resc-002',
        fotoUrl: '',
        detalle: 'Cortaviento blanco y jeans. Sin mochila grande, solo una botella de agua.',
        llevaCarpa: false,
        colorCarpa: '',
        llevaLinterna: false,
        ultimaLat: -37.8179,
        ultimaLng: -73.0344,
        ultimaUbicacionAt: this.haceHoras(16),
        bateria: 3,
      });

      // En alerta: sirve para probar "Cerrar como falsa alarma"
      await setDoc(doc(db, COLECCION, 'exc-009'), {
        excursionista: 'Antonia Herrera Pino',
        rut: '20.000.009-9',
        telefono: '+56 9 5555 0117',
        edad: 26,
        experiencia: 'Intermedio',
        condicionesMedicas: 'Ninguna',
        acompanantes: [
          {
            nombre: 'Felipe Herrera Pino',
            rut: '20.000.021-1',
            edad: 30,
            condicionesMedicas: 'Ninguna',
            tieneCuenta: true,
            telefono: '+56 9 5555 0206',
            experiencia: 'Intermedio',
          },
        ],
        ruta: 'PN Nonguén - Sendero Los Canelos',
        contactoNombre: 'Felipe Herrera (hermano)',
        contactoTelefono: '+56 9 5555 0118',
        salidaAt: this.haceHoras(7),
        retornoAt: this.haceHoras(1),
        estado: ESTADO_EN_ALERTA,
        rescatistaId: '',
        fotoUrl: '',
        detalle: '',
        llevaCarpa: false,
        colorCarpa: '',
        llevaLinterna: true,
        ultimaLat: -36.8811,
        ultimaLng: -72.9702,
        ultimaUbicacionAt: this.haceHoras(2),
        bateria: 60,
      });
    } catch (error) {
      throw this.error(error);
    }
  }

  // Devuelve la fecha de hace N horas (con N negativo, dentro de N horas)
  private haceHoras(horas: number): Timestamp {
    const milisegundos = horas * 60 * 60 * 1000;
    return Timestamp.fromMillis(Date.now() - milisegundos);
  }

  // Arma un objeto Excursion, campo por campo, con los datos de Firestore
  private convertir(id: string, datos: any): Excursion {
    // La última ubicación puede no existir todavía
    let ultimaUbicacionAt = null;
    if (datos.ultimaUbicacionAt) {
      ultimaUbicacionAt = datos.ultimaUbicacionAt.toDate();
    }

    // Convierte cada acompañante, campo por campo
    const acompanantes: Acompanante[] = [];
    for (const persona of datos.acompanantes || []) {
      const acompanante: Acompanante = {
        nombre: persona.nombre,
        rut: persona.rut,
        edad: persona.edad,
        condicionesMedicas: persona.condicionesMedicas,
        tieneCuenta: persona.tieneCuenta,
        telefono: persona.telefono,
        experiencia: persona.experiencia,
      };
      acompanantes.push(acompanante);
    }

    const excursion: Excursion = {
      id: id,
      excursionista: datos.excursionista,
      rut: datos.rut,
      telefono: datos.telefono,
      edad: datos.edad,
      experiencia: datos.experiencia,
      condicionesMedicas: datos.condicionesMedicas,
      acompanantes: acompanantes,
      ruta: datos.ruta,
      contactoNombre: datos.contactoNombre,
      contactoTelefono: datos.contactoTelefono,
      // Firestore guarda las fechas como Timestamp: las pasamos a Date
      salidaAt: datos.salidaAt.toDate(),
      retornoAt: datos.retornoAt.toDate(),
      estado: datos.estado,
      // Solo las excursiones escaladas tienen rescatista
      rescatistaId: datos.rescatistaId || '',
      // La foto y el detalle son opcionales
      fotoUrl: datos.fotoUrl || '',
      detalle: datos.detalle || '',
      llevaCarpa: datos.llevaCarpa,
      colorCarpa: datos.colorCarpa,
      llevaLinterna: datos.llevaLinterna,
      ultimaLat: datos.ultimaLat ?? null,
      ultimaLng: datos.ultimaLng ?? null,
      ultimaUbicacionAt: ultimaUbicacionAt,
      bateria: datos.bateria ?? null,
    };
    return excursion;
  }

  // Traduce los errores de Firebase a un mensaje que el usuario entienda
  private error(error: any): Error {
    console.error(error);

    if (error.code) {
      return new Error('No se pudo conectar con la base de datos.');
    }
    // Sin "code" es uno de nuestros propios mensajes: lo dejamos pasar tal cual
    return error;
  }
}
