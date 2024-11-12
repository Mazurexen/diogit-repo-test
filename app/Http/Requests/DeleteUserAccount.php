<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteUserAccount extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'checkConfirmDelete' => 'required|accepted',
            'current_password' => 'required|current_password',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'checkConfirmDelete.required' => trans('profile.confirmDeleteRequired'),
            'checkConfirmDelete.accepted' => trans('profile.mustAcceptDeletion'),
            'current_password.required' => trans('auth.currentPasswordRequired'),
            'current_password.current_password' => trans('auth.currentPasswordInvalid'),
        ];
    }
}
