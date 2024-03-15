<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    use HasFactory;

    public function files(){
        return $this->hasMany(AssignmentSubmissionFile::class);
    }

    public function url(){
        return $this->hasOne(AssignmentSubmissionOnlineText::class, 'assignment_submission_id');
    }

}
