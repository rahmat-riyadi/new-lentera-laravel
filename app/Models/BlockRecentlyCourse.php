<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockRecentlyCourse extends Model
{
    use HasFactory;

    protected $table = 'mdl_block_recentlyaccesseditems';

    public function course(){
        return $this->hasMany(Course::class, 'courseid');
    }

}
