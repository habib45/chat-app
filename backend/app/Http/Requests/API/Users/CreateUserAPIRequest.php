<?php
namespace App\Http\Requests\API\Users;

use App\Http\Requests\ApiRequest;
use http\Env\Request;

class CreateUserAPIRequest extends ApiRequest
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
        $validation = [
           'name' => 'required',
           'email' => 'required|unique:users|max:150',
           'password' =>'required|min:5|max:150',
        ];

        return $validation;
    }
}
