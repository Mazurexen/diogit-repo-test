<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => [
                'required',
                'max:255',
                'unique:users',
                'string',
                'regex:/^[\pL\s\-0-9]+$/u'
            ],
            'first_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\pL\s\-]+$/u'
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\pL\s\-]+$/u'
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'unique:users'
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:64',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/'
            ],
            'role' => ['required', 'exists:roles,id'],
        ];
    }

    public function messages()
    {
        return [
            'name.unique' => trans('auth.userNameTaken'),
            'name.required' => trans('auth.userNameRequired'),
            'name.regex' => trans('auth.userNameInvalid'),
            'first_name.required' => trans('auth.fNameRequired'),
            'first_name.regex' => trans('auth.firstNameInvalid'),
            'last_name.required' => trans('auth.lNameRequired'),
            'last_name.regex' => trans('auth.lastNameInvalid'),
            'email.email' => trans('auth.emailInvalid'),
            'email.unique' => trans('auth.emailTaken'),
            'password.regex' => trans('auth.passwordComplexity'),
            'role.exists' => trans('auth.invalidRole'),
            // ... outros messages
        ];
    }
} 