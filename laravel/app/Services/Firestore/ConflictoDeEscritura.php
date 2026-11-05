<?php

namespace App\Services\Firestore;

// El documento cambió mientras se preparaba la escritura (la API lo traduce a 409)
class ConflictoDeEscritura extends \RuntimeException {}
