<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $table = 'resource';

    protected $guarded = ['id'];

    public function files(){
        return $this->hasMany(ResourceFile::class);
    }
}
