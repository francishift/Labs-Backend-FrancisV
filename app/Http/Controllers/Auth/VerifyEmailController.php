<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request, $id, $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            return Auth::check()
                ? redirect()->intended(route('dashboard', absolute: false).'?verified=1')
                : redirect()->route('login')->with('status', '¡Tu correo ha sido verificado correctamente! Por favor, inicia sesión.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return Auth::check()
            ? redirect()->intended(route('dashboard', absolute: false).'?verified=1')
            : redirect()->route('login')->with('status', '¡Tu correo ha sido verificado correctamente! Por favor, inicia sesión.');
    }
}
