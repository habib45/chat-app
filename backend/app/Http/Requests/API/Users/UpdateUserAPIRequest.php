<?php

namespace App\Http\Requests\API\Users;

use App\Http\Requests\ApiRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;


class UpdateUserAPIRequest extends ApiRequest
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
            'name' => 'required',
            'email' => 'required|unique:users|max:150',
            'password' =>'required|min:5|max:150',
        ];
    }

    public function messages()
    {
        return [
            'mobile.min' => 'The mobile number must be start at +880 and least 14 characters.',
            'password.max' => 'The password must not exceed 13 characters.',
            'password.min' => 'The password should be minimum 8 characters.',
            'password.mixed_case' => 'The password must contain mixed case characters.',
            'password.letters' => 'The password must contain letters.',
            'password.numbers' => 'The password must contain numbers.',
            'password.symbols' => 'The password must contain symbols.',
        ];
    }


    public function withValidator($validator)
    {
        $failedRules = $validator->failed();
        foreach ($failedRules as $fieldName => $failures) {
            foreach ($failures as $rule => $parameters) {
                // Set a custom error message for the failed validation rule
                $validator->errors->add($fieldName, $rule);
            }
        }
    }
}
