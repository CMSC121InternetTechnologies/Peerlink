<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'Rooms';
    protected $primaryKey = 'room_id';
    public $timestamps = false;

    protected $fillable = ['room_code', 'room_name', 'room_type', 'capacity'];
}
