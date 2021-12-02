<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class UserTradeStats extends Eloquent
{
    protected $connection = 'mongodb';
    protected $collection = 'user_trade_stats';

    protected $primaryKey = '_id';
    protected $dates = ['date'];

    protected $fillable = [
        'binance_id',
        'pair',
        'contracts',
        'profit',
        'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userAccount()
    {
        return $this->belongsTo(UserAccount::class);
    }
}