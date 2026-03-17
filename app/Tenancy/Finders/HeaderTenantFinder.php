<?php

namespace App\Tenancy\Finders;

use App\Models\Administration\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class HeaderTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        // 1) Prioridad: header explícito
        $headerTenantId = $request->header('tenant_id');

        if (! empty($headerTenantId)) {
            return Tenant::query()->find($headerTenantId);
        }

        // 2) Fallback: tenant_id guardado en oauth_access_tokens usando el jti del JWT
        $jwt = $request->bearerToken();

        if (! $jwt) {
            return null;
        }

        $tokenId = $this->extractTokenIdFromJwt($jwt);

        if (! $tokenId) {
            return null;
        }

        $token = DB::table('oauth_access_tokens')
            ->where('id', $tokenId)
            ->where('revoked', false)
            ->first();

        if (! $token || empty($token->tenant_id)) {
            return null;
        }

        return Tenant::query()->find($token->tenant_id);
    }

    protected function extractTokenIdFromJwt(string $jwt): ?string
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = $parts[1];

        $decodedPayload = json_decode($this->base64UrlDecode($payload), true);

        if (! is_array($decodedPayload)) {
            return null;
        }

        return $decodedPayload['jti'] ?? null;
    }

    protected function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/'));
    }
}
