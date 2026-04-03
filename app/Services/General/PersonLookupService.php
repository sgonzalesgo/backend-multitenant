<?php

namespace App\Services\General;

use App\Models\General\Person;

class PersonLookupService
{
    public function __construct(
        protected ExternalPersonLookupService $externalLookupService
    ) {}

    public function lookup(string $legalId): array
    {
        $person = Person::query()
            ->where('legal_id', $legalId)
            ->first();

        if ($person) {
            return [
                'source' => 'database',
                'person' => $person->toArray(),
            ];
        }

        $externalPerson = $this->externalLookupService->lookupByLegalId($legalId);

        if ($externalPerson) {
            return [
                'source' => 'external',
                'person' => $externalPerson,
            ];
        }

        return [
            'source' => null,
            'person' => null,
        ];
    }
}
