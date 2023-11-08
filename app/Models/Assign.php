<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assign extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'mdl_assign';

    public $timestamps = false;

    public function assignPluginConfig(){
        return $this->hasMany(AssignPluginConfig::class, 'assignment');
    }

}
