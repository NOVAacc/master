<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ClientBookingAlert extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['message'];
}
