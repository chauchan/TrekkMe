<?php

namespace App\Models\Concerns;

// IDs de texto con prefijo y correlativo (exc-001, bit-001), como los documentos de Firestore
trait GeneraIdConPrefijo
{
    public $incrementing = false;

    protected $keyType = 'string';

    abstract protected function prefijoId(): string;

    protected static function bootGeneraIdConPrefijo(): void
    {
        static::creating(function ($modelo) {
            if (! empty($modelo->id)) {
                return;
            }

            $prefijo = $modelo->prefijoId();
            $ultimo = static::query()
                ->where('id', 'like', $prefijo.'-%')
                ->pluck('id')
                ->map(fn ($id) => (int) substr($id, strlen($prefijo) + 1))
                ->max() ?? 0;

            $modelo->id = sprintf('%s-%03d', $prefijo, $ultimo + 1);
        });
    }
}
