<?php

namespace Statamic\Http\Controllers\CP\Users;

use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Statamic\Events\UserPasswordChanged;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

class PasswordController extends CpController
{
    public function update(Request $request, $user)
    {
        throw_unless($user = User::find($user), new NotFoundHttpException);

        $this->authorize('editPassword', $user);

        $rules = [
            'password' => ['required', 'confirmed', Password::default()],
        ];

        if ($request->user()->id === $user) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $request->validate($rules);

        $user->password($request->password)->save();

        UserPasswordChanged::dispatch($user);

        if ($currentPassword = $request->current_password) {
            Auth::logoutOtherDevices($currentPassword);
        }

        return response('', 204);
    }
}
