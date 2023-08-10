<?php

namespace Ezegyfa\LaravelHelperMethods\Authentication\User;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\DynamicTemplateMethods;
use Ezegyfa\LaravelHelperMethods\HttpMethods;
use Ezegyfa\LaravelHelperMethods\Authentication\User\User;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserAuthenticationController extends Controller 
{
    public function __construct() {}


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
            $rememberMe = $request->has('remember_me');
            if (auth()->attempt($loginData, $rememberMe)) {
                return redirect('/');
            }
            else {
                throw ValidationException::withMessages([ 'password' => __('auth.password') ]);
            }
        }
        catch (ValidationException $e) {
            return redirect()->back()->withInput(request()->all())->withErrors($e->errors());
        }
    }

    public function loginPage() {
        $tableInfos = DatabaseInfos::getTableInfosByColumns('users', [ 'email', 'password' ]);
        $formItemSections = $tableInfos->getFormInfos('auth');
        array_push($formItemSections, (object) [
            'type' => 'checkbox-input',
            'data' => (object) [
                'name' => 'remember_me',
                'label' => __('auth.label.remember_me')
            ]
        ]);
        $templateParams = (object) [
            'form_item_sections' => $formItemSections
        ];
        return DynamicTemplateMethods::getTemplateDynamicPage('ecom_login', $templateParams, 'app');
    }

    public function registration(Request $request) {
        try {
            $tableInfos = DatabaseInfos::getTableInfosByColumns('users', [ 'name', 'email', 'password' ]);
            $request->merge(HttpMethods::getCorrectedRequestData($request->all(), $tableInfos));
            $validators = $tableInfos->getValidators();

            $validators['password'][] = 'confirmed';

            $request->validate($validators);
            $insertData = $tableInfos->filterData(request()->all());
            $user = User::create($insertData);
            auth()->login($user);
            return redirect('/')->with('success_message', 'Registration succefully');
        }
        catch (ValidationException $e) {
            return redirect()->back()->withInput(request()->all())->withErrors($e->errors());
        }
    }

    public function registrationPage() {
        $tableInfos = DatabaseInfos::getTableInfosByColumns('users', [ 'email', 'name', 'password' ]);
        $formItemSections = $tableInfos->getFormInfos('auth');
        array_push($formItemSections, (object) [
            'type' => 'text-input',
            'data' => (object) [
                'name' => 'password_confirmation',
                'label' => 'Repeat password'
            ]
        ]);
        $templateParams = (object) [
            'form_item_sections' => $formItemSections
        ];
        return DynamicTemplateMethods::getTemplateDynamicPage('ecom_registration', $templateParams, 'app');
    }

    public function logout(Request $request) {
        if(Auth::check()) {
            Auth::logout();
            # $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return redirect('/');
    }
}