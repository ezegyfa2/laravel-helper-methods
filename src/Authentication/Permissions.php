<?php
namespace Ezegyfa\LaravelHelperMethods\Authentication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;


class Permissions extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'admin_role_permission';
    public $timestamps = false;
}