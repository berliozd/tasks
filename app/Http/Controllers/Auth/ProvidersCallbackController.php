<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class ProvidersCallbackController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $fieldId = null;
        $driver = null;
        if ($request->getPathInfo() === config('services.google.redirect')) {
            $fieldId = 'google_id';
            $driver = 'google';
        } elseif ($request->getPathInfo() === config('services.github.redirect')) {
            $fieldId = 'github_id';
            $driver = 'github';
        }
        if (empty($driver)) {
            return redirect('');
        }
        try {
            $providerUser = Socialite::driver($driver)->user();
            $user = User::where($fieldId, $providerUser->id)->first();
            if (empty($user)) {
                $user = User::where('email', $providerUser->email)->first();
            }
            if (!empty($user)) {
                $user->update([$fieldId => $providerUser->id, 'updated_at' => now()]);
            } else {
                $user = User::create([
                    $fieldId => $providerUser->id,
                    'name' => $providerUser->name,
                    'email' => $providerUser->email
                ]);
                $user->ownedTeams()->save(
                    Team::forceCreate([
                        'user_id' => $user->id,
                        'name' => explode(' ', $user->name, 2)[0] . "'s Team",
                        'personal_team' => true,
                    ])
                );
                event(new Registered($user));
            }
            Auth::login($user);

            return redirect(route(config('app.home-route')));
        } catch (\Exception $e) {
            return redirect()->route('login');
        }
    }

}
