<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function rules()
    {
        $user = $this->route('user');
        $emailCheck = $this->input('email') !== '' && $this->input('email') !== $user->email;

        if ($emailCheck) {
            return [
                'name' => 'required|max:255|unique:users|alpha_dash',
                'email' => 'email|max:255|unique:users',
                'first_name' => 'alpha_dash',
                'last_name' => 'alpha_dash',
                'password' => 'present|confirmed|min:6',
            ];
        }

        return [
            'name' => 'required|max:255|alpha_dash|unique:users,name,'.$user->id,
            'first_name' => 'alpha_dash',
            'last_name' => 'alpha_dash',
            'password' => 'nullable|confirmed|min:6',
        ];
    }
} 