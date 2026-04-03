<?php

namespace App\Services\General;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class ExternalPersonLookupService
{
    public function lookupByLegalId(string $legalId): ?array
    {
        $baseUrl = rtrim((string) config('services.dinardap.base_url'), '/');
        $codigoPaquete = (string) config('services.dinardap.codigo_paquete');

        $response = Http::timeout(15)
            ->acceptJson()
            ->get("{$baseUrl}/consultar", [
                'identificacion' => $legalId,
                'codigoPaquete' => $codigoPaquete,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload) || ! data_get($payload, 'success')) {
            return null;
        }

        $row = $this->extractDemographicRow((array) data_get($payload, 'datos.entidades', []));

        if (! $row) {
            return null;
        }

        return [
            'id' => null,
            'full_name' => Arr::get($row, 'nombre'),
            'photo' => null,
            'email' => null,
            'phone' => null,
            'address' => null,
            'city' => null,
            'state' => null,
            'country' => null,
            'zip' => null,
            'legal_id' => Arr::get($row, 'cedula', $legalId),
            'legal_id_type' => 'cedula',
            'birthday' => $this->normalizeDate(Arr::get($row, 'fechaNacimiento')),
            'gender' => null,
            'marital_status' => Arr::get($row, 'estadoCivil'),
            'blood_group' => null,
            'nationality' => null,
            'status' => 'active',
            'deceased_at' => $this->resolveDeceasedAt(Arr::get($row, 'actaDefuncion')),
            'status_changed_at' => null,
        ];
    }

    protected function extractDemographicRow(array $entities): ?array
    {
        foreach ($entities as $entity) {
            $entityName = mb_strtolower((string) Arr::get($entity, 'nombre', ''));
            $rows = Arr::get($entity, 'data', []);

            if (! is_array($rows) || empty($rows) || ! is_array($rows[0])) {
                continue;
            }

            if (
                str_contains($entityName, 'datos demográficos') ||
                str_contains($entityName, 'datos demograficos') ||
                str_contains($entityName, 'registro civil')
            ) {
                return $rows[0];
            }
        }

        return null;
    }

    protected function normalizeDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        } catch (\Throwable $th) {
            return null;
        }
    }

    protected function resolveDeceasedAt(?string $value): ?string
    {
        return $value === '1' ? now()->toDateTimeString() : null;
    }
}
