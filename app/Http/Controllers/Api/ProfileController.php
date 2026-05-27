<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', "unique:users,email,{$user->id}"],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);
        $user->update($request->only('name', 'email', 'phone'));
        return response()->json(['data' => $user->fresh()]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'          => ['required'],
            'new_password'              => ['required', 'min:8', 'confirmed'],
        ]);

        if (!\Hash::check($request->current_password, auth()->user()->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        auth()->user()->update(['password' => $request->new_password]);
        return response()->json(['message' => 'Password updated successfully.']);
    }
}