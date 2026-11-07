import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'cl.trekkme.institucional',
  appName: 'TrekkMe',
  webDir: 'www',
  // Solo desarrollo: permite llamar a la API por http. Se quita al usar https
  server: { androidScheme: 'http', cleartext: true },
};

export default config;
