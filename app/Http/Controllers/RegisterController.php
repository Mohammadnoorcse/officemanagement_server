<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class RegisterController extends Controller
{
   

   public function register(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => ['required', 'confirmed'],
            'role'       => 'nullable|in:user,admin,teamleader',
            'department' => 'nullable|string|max:255',
            'salary'     => 'nullable|numeric|min:0',
            'status'     => 'nullable|in:active,inactive',
            'image'      => 'nullable|image|mimes:jpg,jpeg,png|max:20480',
        ]);

        // Upload Image
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('public/users', $imageName);
            $imagePath = 'storage/users/' . $imageName;
        }

        // Create User
        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role'       => $request->role ?? 'user',
            'department' => $request->department,
            'salary'     => $request->salary,
            'status'     => $request->status ?? 'active',
            'image'      => $imagePath,
        ]);

        // Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'User registered successfully',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user
        ]);
    }

}
