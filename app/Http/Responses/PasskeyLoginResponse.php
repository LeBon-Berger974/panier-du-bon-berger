<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    public function toResponse($request): Response
    {
        $redirect = $request->user()->hasRole('admin')
            ? '/admin'
            : '/dashboard';

        return $request->wantsJson()
            ? new JsonResponse(['redirect' => url($redirect)], 200)
            : redirect($redirect);
    }
}
