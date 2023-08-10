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

class AdminAuthenticationController extends Controller 
{
    public function __construct() {}

    public function dashboard(Request $request) {
        // Admin::find(1)->user->name 
        // Auth::user()->admin()->exists() admin-e?
        // Auth::user()->admin()->permissions
        dd(Auth::guard('admin')->user(), Auth::guard('admin')->user()->user->id);
        return;
    }

    public function login(Request $request) {
        try {
            $input = $request->all();
            $validators = DatabaseInfos::getTableInfosByColumns('users', [ 'email', 'password' ])->getValidators();
            $validators['email'] = array_filter($validators['email'], function($validator) {
                return strpos($validator, 'unique') === false;
            });
            $request->validate($validators);
            $loginData = [
                'email' => $input['email'],
                'password' => $input['password']
            ];

            $user = User::where('email', $request->email)->first();
            if($user) {
                if($user->admin()->exists()) {
                    $admin = $user->admin;
                    $password = $admin->password;
                    if( Hash::check($request->password, $password)) {
                        Auth::guard('admin')->loginUsingId($user->id);
                        return redirect()->route('dashboard');
                   }
                   else {
                        throw ValidationException::withMessages([ 'password' => __('auth.password') ]);
                   }
                }
                else {
                    throw ValidationException::withMessages([ 'email' => __('auth.email') ]);
                }
            }
            else{
                throw ValidationException::withMessages([ 'email' => __('auth.email') ]);
            }
        }
        catch (ValidationException $e) {
            return redirect()->back()->withInput(request()->all())->withErrors($e->errors());
        }
        return redirect()->back()->withInput(request()->all())->withErrors([ 'password' => __('auth.password') ]);
    }

    public function loginPage() {
        if(Auth::guard('admin')->check()) {
            return redirect()->route('dashboard');
        }
        $tableInfos = DatabaseInfos::getTableInfosByColumns('users', [ 'email', 'password' ]);
        $formItemSections = $tableInfos->getFormInfos('auth');
        $templateParams = (object) [
            'form_item_sections' => $formItemSections
        ];
        return DynamicTemplateMethods::getTemplateDynamicPage('ecom_login', $templateParams, 'app');
    }

    public function logout(Request $request) {
        Auth::shouldUse('admin');
        if(Auth::check()) {
            Auth::logout();
            $request->session()->regenerateToken();
        }
        return redirect('/');
    }
}