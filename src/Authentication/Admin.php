<?php

namespace Ezegyfa\LaravelHelperMethods\Authentication;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasFactory;
    protected $guard = 'admin';
    public $timestamps = false;

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function permissions() {
        return $this->hasMany(Permissions::class);
    }
}