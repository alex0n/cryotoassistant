<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class UserBalance extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'user_balance';

    protected $primaryKey = '_id';
    protected $dates = ['date'];

    protected $fillable = [
//        'user_id',
//        'account_id',
        'balance',
        'profit',
        'date',
    ];

    public function users()
    {
        return $this->belongsTo(User::class);
    }

    public function userAccount()
    {
        return $this->belongsTo(UserAccount::class);
    }
}