<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->route('role');
        
        return [
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ];
    }
}