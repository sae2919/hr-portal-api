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
            'phone' => ['nullable', 'string', 'max:20'],
        ];

        if ($isAdminOrHR) {
            $rules['name'] = ['required', 'string', 'max:100'];
            $rules['email'] = ['required', 'email', "unique:users,email,{$user->id}"];
        } else {
            if ($request->has('name') && $request->name !== $user->name) {
                return response()->json([
                    'message' => 'Name cannot be changed by the employee.',
                    'errors' => ['name' => ['Name cannot be changed by the employee.']]
                ], 422);
            }
        }

        $request->validate($rules);

        $updateData = $request->only('phone');
        if ($isAdminOrHR) {
            $updateData['name'] = $request->name;
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }
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