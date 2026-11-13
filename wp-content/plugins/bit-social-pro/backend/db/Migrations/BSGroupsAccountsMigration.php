<?php

use BitApps\SocialPro\Config;
use BitApps\SocialPro\Deps\BitApps\WPDatabase\Blueprint;
use BitApps\SocialPro\Deps\BitApps\WPDatabase\Connection;
use BitApps\SocialPro\Deps\BitApps\WPDatabase\Schema;
use BitApps\SocialPro\Deps\BitApps\WPKit\Migration\Migration;

if (!defined('ABSPATH')) {
    exit;
}

final class BSGroupsAccountsMigration extends Migration
{
    public function up()
    {
        Schema::withPrefix(Connection::wpPrefix() . Config::FREE_PLUGIN_VAR_PREFIX)->create('groups_accounts', function (Blueprint $table) {
            $table->id();
            $table->bigint('group_id', 20)->unsigned()->foreign('groups', 'id')->onDelete()->cascade();
            $table->bigint('account_id', 20)->unsigned()->foreign('accounts', 'id')->onDelete()->cascade();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::withPrefix(Connection::wpPrefix() . Config::FREE_PLUGIN_VAR_PREFIX)->drop('groups_accounts');
    }
}
