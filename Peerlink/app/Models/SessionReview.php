<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SessionReview extends Model
{
    protected $table = 'Session_Reviews';
    protected $primaryKey = 'review_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}