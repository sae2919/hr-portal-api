<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = auth()->user();
        $isAdminOrHR = $user->hasRole('admin') || $user->hasRole('hr') || $user->hasRole('super_admin');

        $rules = [
            'name'  => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];

        if ($isAdminOrHR) {
            $rules['email'] = ['required', 'email', "unique:users,email,{$user->id}"];
        }

        $request->validate($rules);

        $updateData = $request->only('name', 'phone');
        if ($isAdminOrHR && $request->has('email')) {
            $updateData['email'] = $request->email;
        }

        $user->update($updateData);

        // Sync with employee if email is changed and they have a linked employee
        if ($isAdminOrHR && $request->has('email') && $user->employee) {
            $user->employee->update([
                'email' => $user->email
            ]);
        }

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