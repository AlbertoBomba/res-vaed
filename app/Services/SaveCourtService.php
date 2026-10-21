<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Court;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SaveCourtService
{
    public function save(User $actor, array $data, ?Court $court, ?int $version, ?int $selectedCompanyId): array
    {
        Gate::forUser($actor)->authorize($court ? 'update' : 'create', $court ?? Court::class);
        $companyId = $court?->company_id ?? ($actor->hasRole('super-admin') ? $selectedCompanyId : $actor->company_id);
        Company::findOrFail($companyId);

        $newImage = null;
        $oldImage = $court?->image_path;

        if ($data['image']) {
            $newImage = $data['image']->store("courts/$companyId", 'public');

            if (! is_string($newImage) || $newImage === '') {
                throw new RuntimeException('No se ha podido guardar la imagen de la pista.');
            }
        }

        try {
            $saved = DB::transaction(function () use ($court, $actor, $version, $data, $companyId, $newImage) {
                if ($court) {
                    $record = Court::whereKey($court->id)->lockForUpdate()->firstOrFail();
                    Gate::forUser($actor)->authorize('update', $record);

                    if ($record->version !== $version) {
                        throw ValidationException::withMessages(['conflict' => 'Otra persona ha modificado esta pista. Recarga la página antes de guardar para no sobrescribir sus cambios.']);
                    }
                } else {
                    $record = new Court;
                    $record->company_id = $companyId;
                }

                if ($data['status'] === 'active' && collect($data['weekly_hours'])->flatten()->isEmpty()) {
                    throw ValidationException::withMessages(['weekly_hours' => 'Una pista activa debe tener al menos una franja disponible.']);
                }

                $record->fill(Arr::only($data, ['name', 'sport', 'type', 'surface', 'enclosure', 'status', 'capacity', 'lighting', 'accessible', 'description']));

                foreach (['hourly_price', 'two_hour_price', 'three_plus_hour_price'] as $price) {
                    $record->{$price.'_cents'} = $this->toCents($data[$price]);
                }

                if ($newImage || $data['remove_image']) {
                    $record->image_path = $newImage;
                }

                $record->version = $record->exists ? $record->version + 1 : 1;
                $record->save();
                $record->hours()->delete();

                foreach ($data['weekly_hours'] as $day => $hours) {
                    $record->hours()->createMany(array_map(fn ($hour) => ['weekday' => (int) $day, 'hour' => (int) $hour], $hours));
                }

                return $record;
            });
        } catch (Throwable $exception) {
            if ($newImage && ! Storage::disk('public')->delete($newImage)) {
                Log::error('No se pudo limpiar una imagen tras un fallo al guardar la pista.', ['path' => $newImage]);
            }

            throw $exception;
        }

        $warning = null;
        if ($oldImage && $oldImage !== $saved->image_path && ! Storage::disk('public')->delete($oldImage)) {
            Log::warning('No se pudo eliminar la imagen sustituida de una pista.', ['court_id' => $saved->id, 'path' => $oldImage]);
            $warning = 'La pista se ha guardado, pero no se pudo limpiar la imagen anterior del almacenamiento.';
        }

        return ['court' => $saved, 'warning' => $warning];
    }

    private function toCents(string $price): int
    {
        [$euros, $cents] = array_pad(explode('.', str_replace(',', '.', $price), 2), 2, '');

        return (int) $euros * 100 + (int) str_pad($cents, 2, '0');
    }
}
