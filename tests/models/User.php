<?php

use Illuminate\Database\Eloquent\SoftDeletes;

class User extends  Illuminate\Database\Eloquent\Model {

    use SoftDeletes;

    public $timestamps = true;

    protected $dates = ['deleted_at'];

}