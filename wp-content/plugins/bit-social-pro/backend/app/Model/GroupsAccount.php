<?php

namespace BitApps\SocialPro\Model;

use BitApps\Social\Config as FreeConfig;

use BitApps\Social\Model\Account;
use BitApps\SocialPro\Deps\BitApps\WPDatabase\Model;

final class GroupsAccount extends Model
{
    protected $fillable = [
        'group_id',
        'account_id',
    ];

    protected $casts = [
        'group_id'   => 'int',
        'account_id' => 'int',
    ];

    protected $prefix = FreeConfig::VAR_PREFIX;

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }
}
