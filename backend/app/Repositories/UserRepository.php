<?php

namespace App\Repositories;

use App\Http\Requests\UserNotificationRequest;
use App\Models\User;
use App\Http\Requests\DeviceTokenRequest;
use Illuminate\Http\Request;

/**
 * Class UserRepository
 *
 * @package App\Repositories
 */
class UserRepository extends BaseRepository
{
    protected $modelName = User::class;

    /**
     * @var array
     */
    protected $fieldSearchable = [
        'id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'activation_key',
        'timezone',
        'reset_code',
        'updated_by',
        'deleted_at',
        'password',
        'remember_token',
    ];

    /**
     * UserRepository constructor.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;
    }


    /**
     * @param $email
     * @return mixed
     */
    public function getUserInfo($email,$field='email')
    {
       $user =  $this->model::where("$field", $email)->first();

       return $user;
    }

    /**
     * @param $userName
     * @return mixed
     */
    public function getUserInfoByEmail($userName)
    {
        return $this->model->where('email', $userName)->first();
    }

    /**
     * @param $userName
     * @return mixed
     */
    public function getUserDetail($id)
    {
        return $this->model->where('id', $id)->first();
    }


}
