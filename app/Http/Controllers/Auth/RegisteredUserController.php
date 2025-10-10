<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Mail;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'household_size' => ['required', 'integer', 'min:1'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'household_size' => $request->household_size,
        ]);

         // Generate and store 6-digit code
    $code = rand(100000, 999999);
    $user->verification_code = $code;
    $user->save();

    // Send email
    Mail::to($user->email)->send(new VerificationCodeMail($code));

    // Log the user in temporarily to allow verification
    Auth::login($user);

    // Store the email in session for verify step
    session(['email' => $user->email]);


    return redirect()->route('verify.otp', ['email' => $user->email])->with('status', 'We have sent you a verification code.');
    }
}
