<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function store(RegisterRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        $attributes['password'] = Hash::make($attributes['password']);

        try {
            User::create($attributes);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'This email address is already registered.']);
        }

        return to_route('login')->with('status', 'Account created successfully! Please log in to continue.');
    }
}
