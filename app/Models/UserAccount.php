<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

class UserAccount extends Eloquent
{
    use SoftDeletes;

    public const PLATFORM_ID_BINANCE = 1;
    public const PLATFORM_ID_BITMEX = 2;
    public const PLATFORM_ID_KUCOIN = 3;
    public const PLATFORM_ID_BYBIT = 4;
    public const PLATFORM_ID_OKEX = 5;
    public const PLATFORM_ID_COINBENE = 4;
    public const PLATFORMS = [
        self::PLATFORM_ID_BINANCE => 'Binance',
        self::PLATFORM_ID_BITMEX => 'Bitmex',
    ];

    protected $connection = 'mongodb';
    protected $collection = 'user_accounts';

    protected $primaryKey = '_id';

    protected $fillable = [
//        'user_id',
        'platform_id',
        'platform_user_id',
        'api_public_key',
        'api_secret_key',
        'is_enabled',
    ];

    public function users()
    {
        return $this->belongsTo(User::class);
    }

    public function balances()
    {
        return $this->hasMany(UserBalance::class);
    }

    public function tradeStats()
    {
        return $this->hasMany(UserTradeStats::class);
    }
}