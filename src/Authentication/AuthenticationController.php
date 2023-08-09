<?php

namespace Ezegyfa\LaravelHelperMethods\Authentication;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\DynamicTemplateMethods;
use Ezegyfa\LaravelHelperMethods\HttpMethods;
use Ezegyfa\LaravelHelperMethods\Authentication\User;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

use Auth;
   
class AuthenticationController extends Controller
{
    public function __construct(Request $request)
    {
    }

    public function adminDashboard(Request $request) {
        // Admin::find(1)->user->name 
        // Auth::user()->admin()->exists() admin-e?
        // Auth::user()->admin()->permissions
        #dd(Auth::user()->admin->permissions(), Admin::find(1)->permissions);
        dd(Auth::guard('admin')->user(), Auth::guard('admin')->user()->user->id);
        return;
    }

    public function adminLogin() {
        if(Auth::guard('admin')->check()) {
            return redirect()->route('adminDashboard');
        }
        $tableInfos = DatabaseInfos::getTableInfosByColumns('users', [ 'email', 'password' ]);
        $formItemSections = $tableInfos->getFormInfos('auth');
        $templateParams = (object) [
            'form_item_sections' => $formItemSections
        ];
        return DynamicTemplateMethods::getTemplateDynamicPage('ecom_login', $templateParams, 'app');
    }

    public function adminLoginPost(Request $request) {
        try {
            $input = $request->all();
            $validators = DatabaseInfos::getTableInfosByColumns('users', [ 'email', 'password' ])->getValidators();
            foreach($validators['email'] as $k => $v) {
                if(strpos($v, 'unique') !== false) {
                    unset($validators['email'][$k]);
                }
            }
            $request->validate($validators);
            $loginData = [
                'email' => $input['email'],
                'password' => $input['password']
            ];

            $user = User::where('email', $request->email)->first();
            # $admin = Admin::where('user_id', $user->id) 
            if($user && ($admin = $user->admin()->get()->toArray())) {
                $password = $admin[0]['password'];
                if( Hash::check($request->password, $password)) {
                    Auth::guard('admin')->loginUsingId($user->id);
                    return redirect()->route('adminDashboard');
               }
            }
        }
        catch (ValidationException $e) {
            return redirect()->back()->withInput(request()->all())->withErrors($e->errors());
        }
        return redirect()->back()->withInput(request()->all())->withErrors([ 'password' => __('auth.password') ]);
    }

    public function logout(Request $request) {
        if($request->is('admin/*')) {
            Auth::shouldUse('admin');
        }
        if(Auth::check()) {
            Auth::logout();
            # $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return redirect('/');
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

    public function registration(Request $request) {
        try {
            $tableInfos = DatabaseInfos::getTableInfosByColumns('users', [ 'name', 'email', 'password' ]);
            $request->merge(HttpMethods::getCorrectedRequestData($request->all(), $tableInfos));
            $validators = $tableInfos->getValidators();
            $passwordRule = Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
            $validators['password'] = ['required', 'confirmed', $passwordRule];

            $request->validate($validators);
            $insertData = $tableInfos->filterData(request()->all());
            #$insertData['password'] = Hash::make($insertData['password']);
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
}