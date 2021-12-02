<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class User extends Eloquent
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $primaryKey = '_id';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'username',
        'chat_id',
        'first_name',
        'last_name',
    ];

    public function accounts()
    {
        return $this->hasMany(UserAccount::class);
    }

    public function balances()
    {
        return $this->hasMany(UserBalance::class);
    }
}