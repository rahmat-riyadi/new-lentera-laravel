<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignSubmission extends Model
{
    use HasFactory;

    protected $table = 'mdl_assign_submission';

    protected $guarded = ['id'];

    public function assign(){
        return $this->belongsTo(Assign::class, 'assignment');
    }

    public function user(){
        return $this->belongsTo(User::class, 'userid');
    }

    public function urlSubmission(){
        return $this->hasOne(AssignSubmissionOnlineText::class, 'submission');
    }

}
