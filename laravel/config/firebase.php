<?php

// Mismo proyecto de Firebase que usa Ionic (ionic/src/environments/environment.prod.ts)
return [
    'project_id' => env('FIREBASE_PROJECT_ID'),

    // Ruta al JSON de la cuenta de servicio. Debe estar FUERA del repo.
    'credentials' => env('FIREBASE_CREDENTIALS'),
];
