<?php

namespace BitApps\SocialPro\Model;

use BitApps\Social\Config as FreeConfig;

use BitApps\SocialPro\Deps\BitApps\WPDatabase\Model;

final class Group extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'id'     => 'int',
        'status' => 'int',
        'name'   => 'string'
    ];

    protected $prefix = FreeConfig::VAR_PREFIX;

    public function accountIds()
    {
        return $this->hasMany(GroupsAccount::class, 'group_id', 'id');
    }
}
