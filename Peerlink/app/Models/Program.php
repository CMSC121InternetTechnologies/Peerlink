<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $table = 'Programs';
    protected $primaryKey = 'program_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}