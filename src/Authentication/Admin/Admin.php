<?php

namespace Ezegyfa\LaravelHelperMethods\Authentication\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Ezegyfa\LaravelHelperMethods\Authentication\User\User;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasFactory, HasRoles;
    protected $guard_name = 'admin';

    public $timestamps = false;

    public function user() {
        return $this->belongsTo(User::class);
    }
}