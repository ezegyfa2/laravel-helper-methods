<?php

namespace Ezegyfa\LaravelHelperMethods\Authentication\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Ezegyfa\LaravelHelperMethods\Authentication\User\User;

class Admin extends Authenticatable
{
    use HasFactory;
    protected $guard = 'admin';
    public $timestamps = false;

    public function user() {
        return $this->belongsTo(User::class);
    }
}