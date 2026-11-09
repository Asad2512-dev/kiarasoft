<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SignupRequest;
use Illuminate\Http\Request;

/** Public "register your shop" form; requests are approved by the Super Admin. */
class SignupController extends Controller
{
    public function create()
    {
        return view('auth.signup');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shop_name' => ['required', 'string', 'max:150'],
            'owner_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]{7,30}$/'],
            'city' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);
        if (SignupRequest::where('email', $data['email'])->where('status', 'pending')->exists()) {
            return back()->withInput()->with('error', 'A request with this e-mail is already awaiting review.');
        }
        SignupRequest::create($data);

        return redirect()->route('login')->with('status', 'Thank you! Your shop registration request was received and will be reviewed by the KiraSoft team.');
    }
}
