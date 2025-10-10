<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class VerificationController extends Controller
{
    public function show(Request $request)
    {
        $email = session('email'); // store email from registration
        return view('auth.verify-otp', compact('email'));
    }

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->verification_code !== $request->code) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        $user->email_verified_at = now();
        $user->verification_code = null;
        $user->save();

        session(['verified_email' => $user->email]);
        return redirect()->route('set.password.form')->with('status', 'Your email has been verified.');

    }

    public function resend(Request $request)
    {
        $email = session('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Unable to resend code. Please register again.']);
        }

        $code = rand(100000, 999999);
        $user->verification_code = $code;
        $user->save();

        Mail::to($user->email)->send(new VerificationCodeMail($code));

        return back()->with('status', 'Verification code resent successfully!');
    }


    public function showSetPasswordForm()
    {
        $email = session('verified_email');

        if (!$email) {
            return redirect()->route('verify.otp')->withErrors(['email' => 'Please verify your email first.']);
        }

        return view('auth.set-password', compact('email'));
    }

    public function submitSetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('login')->with('status', 'Password set successfully! You can now log in.');
    }

}
