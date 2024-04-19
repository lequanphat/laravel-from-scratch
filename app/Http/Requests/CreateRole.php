<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRole extends FormRequest
{

    public function authorize(): bool
    {
        // check permission here
        return true;
    }
    public function rules()
    {
        return [
            'role_name' => 'required|min:3|max:40|unique:roles,name',
        ];
    }
    public function messages()
    {
        // response message here
        return [
            'role_name.required' => 'The role name field is required',
            'role_name.unique' => 'The role name have already existed.',
            'role_name.min' => 'The role name must be at least 3 characters.',
            'role_name.max' => 'The role name may not be greater than 40 characters.'
        ];
    }
}
