<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|max:255|unique:users|alpha_dash',
            'first_name' => 'alpha_dash',
            'last_name' => 'alpha_dash',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|max:20|confirmed',
            'password_confirmation' => 'required|same:password',
            'role' => 'required',
        ];
    }
} 