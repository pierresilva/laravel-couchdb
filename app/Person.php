<?php

namespace App;

use pierresilva\CouchDB\Eloquent\Model as Model;

class Person extends Model
{
    //
    protected $connection = 'couchdb';

    protected $collection = 'people';

    protected $fillable = [
        'username',
        'firstname',
        'lastname',
    ];
//
    protected $dates = [
        'created_at',
        'updated_at'
    ];
}
