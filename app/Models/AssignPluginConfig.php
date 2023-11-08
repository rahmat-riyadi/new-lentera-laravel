<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignPluginConfig extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_assign_plugin_config';

    public $timestamps = false;

}
