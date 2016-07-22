<?php

use Illuminate\Database\Schema\Blueprint;
use Czim\CmsCore\Support\Database\CmsMigration;

class CmsMigrationCartalystSentinel extends CmsMigration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->prefixCmsTable('activations'), function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('code');
            $table->boolean('completed')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
        });

        Schema::create($this->prefixCmsTable('persistences'), function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('code');
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->unique('code');
        });

        Schema::create($this->prefixCmsTable('reminders'), function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('code');
            $table->boolean('completed')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create($this->prefixCmsTable('roles'), function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('name');
            $table->text('permissions')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->unique('slug');
        });

        Schema::create($this->prefixCmsTable('role_users'), function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->nullableTimestamps();

            $table->engine = 'InnoDB';
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create($this->prefixCmsTable('throttle'), function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('type');
            $table->string('ip')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->index('user_id');
        });

        Schema::create($this->prefixCmsTable('users'), function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('password');
            $table->text('permissions')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop($this->prefixCmsTable('activations'));
        Schema::drop($this->prefixCmsTable('persistences'));
        Schema::drop($this->prefixCmsTable('reminders'));
        Schema::drop($this->prefixCmsTable('roles'));
        Schema::drop($this->prefixCmsTable('role_users'));
        Schema::drop($this->prefixCmsTable('throttle'));
        Schema::drop($this->prefixCmsTable('users'));
    }

}
