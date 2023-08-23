<?php

namespace Ezegyfa\LaravelHelperMethods\Authentication\Admin;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\DynamicTemplateMethods;
use Ezegyfa\LaravelHelperMethods\HttpMethods;
use Ezegyfa\LaravelHelperMethods\Authentication\User\User;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminAuthenticationController extends Controller 
{
    public function __construct() {}

    public function dashboard(Request $request) {
        // $permission = Permission::create(['guard_name' => 'web', 'name' => 'publish articles']);
        // $role = Role::create(['name' => 'MainAdmin', 'guard_name' => 'admin']);
        // $role = Role::findByName('MainAdmin', 'admin');
        // $role->givePermissionTo('edit articles');
        // $role->givePermissionTo('create users');

        // $admin = Auth::guard('admin')->user();
        // $admin->assignRole(['MainAdmin', 'Admin']);
        // dd($admin->getAllPermissions());
        return DynamicTemplateMethods::getTemplateDynamicPage($this->dashboardPageTemplateName, [], 'app');
    }

    public function login(Request $request) {
        try {
            $input = $request->all();
            $validators = DatabaseInfos::getTableInfosByColumns('users', [ 'email', 'password' ])->getValidators();
            //$validators['password'] = 'required';
            $validators['email'] = array_filter($validators['email'], function($validator) {
                return strpos($validator, 'unique') === false;
            });
            $request->validate($validators);
            $loginData = [
                'email' => $input['email'],
                'password' => $input['password']
            ];

            $user = User::where('email', $request->email)->first();
            if ($user && $user->admin()->exists()) {
                $admin = $user->admin;
                $password = $admin->password;
                if (Hash::check($request->password, $password)) {
                    Auth::guard('admin')->loginUsingId($user->id);
                    return redirect()->route('admin.dashboard');
                }
                else {
                    throw ValidationException::withMessages([ 'password' => __('auth.password') ]);
                }
            }
            else {
                throw ValidationException::withMessages([ 'email' => __('auth.email') ]);
            }
        }
        catch (ValidationException $e) {
            return redirect()->back()->withInput(request()->all())->withErrors($e->errors());
        }
    }

    public function loginPage() {
        if (!Auth::guard('admin')->check()) {
            $tableInfos = DatabaseInfos::getTableInfosByColumns('users', [ 'email', 'password' ]);
            $formItemSections = $tableInfos->getFormInfos('auth');
            $templateParams = (object) [
                'form_item_sections' => $formItemSections
            ];
            return DynamicTemplateMethods::getTemplateDynamicPage($this->loginPageTemplateName, $templateParams, 'app');
        }
        else { 
            return redirect()->route('admin.dashboard');
        }
    }

    public function logout(Request $request) {
        $admin = Auth::guard('admin');
        if ($admin->check()) {
            $admin->logout();
            $request->session()->regenerateToken();
        }
        return redirect()->route('admin.loginPage');
    }
}