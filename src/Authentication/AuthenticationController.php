<?php

namespace Ezegyfa\LaravelHelperMethods\Authentication;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\DynamicTemplateMethods;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Session;
use Auth;
use Route;
   
class AuthenticationController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public static function registerRoutes() {
        Route::get('/login', 'Ezegyfa\LaravelHelperMethods\Authentication\AuthenticationController@loginPage')->name('loginPage');
        Route::post('/login', 'Ezegyfa\LaravelHelperMethods\Authentication\AuthenticationController@login')->name('login');
        Route::get('/registration', 'Ezegyfa\LaravelHelperMethods\Authentication\AuthenticationController@registrationPage')->name('registrationPage');
        Route::get('/logout', 'Ezegyfa\LaravelHelperMethods\Authentication\AuthenticationController@logout')->name('logout');
    }

    public function login(Request $request)
    {
        try {
            $input = $request->all();
            $validators = DatabaseInfos::getTableInfosByColumns('users', [ 'email', 'password' ])->getValidators();
            foreach($validators['email'] as $k => $v) {
                if(strpos($v, 'unique') !== false) {
                    unset($validators['email'][$k]);
                }
            }
            //array_push($validators, 'email in users');
            $request->validate($validators);
            $loginData = [
                'email' => $input['email'],
                'password' => $input['password']
            ];
            $rememberMe = $request->has('remember_me');
            if (auth()->attempt($loginData, $rememberMe)) {
                return redirect()->route('home');
            }
            else {
                return redirect()->back()->withInput(request()->all())->withErrors([ 'password' => __('auth.password') ]);
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

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function registration(Request $request) {
        $tableInfos = DatabaseInfos::getTableInfosByColumns('users', [ 'email', 'name', 'password' ]);
        $formItemSections = $tableInfos->getFormInfos('auth');
        array_push($formItemSections, (object) [
            'type' => 'password-input',
            'data' => (object) [
                'name' => 'password_again',
            ]
        ]);
        $templateParams = (object) [
            'form_item_sections' => $formItemSections
        ];
    }

    public function registrationPage() {
        $templateParams = (object) [
            'form_item_sections' => []
        ];
        return DynamicTemplateMethods::getTemplateDynamicPage('ecom_registration', $templateParams, 'app');
    }
}