<?php

use BitApps\SocialPro\Config;
use BitApps\SocialPro\Deps\BitApps\WPDatabase\Blueprint;
use BitApps\SocialPro\Deps\BitApps\WPDatabase\Connection;
use BitApps\SocialPro\Deps\BitApps\WPDatabase\Schema;
use BitApps\SocialPro\Deps\BitApps\WPKit\Migration\Migration;

if (!defined('ABSPATH')) {
    exit;
}

final class BSGroupsMigration extends Migration
{
    public function up()
    {
        Schema::withPrefix(Connection::wpPrefix() . Config::FREE_PLUGIN_VAR_PREFIX)->create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('status')->defaultValue(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::withPrefix(Connection::wpPrefix() . Config::FREE_PLUGIN_VAR_PREFIX)->drop('groups');
    }
}
