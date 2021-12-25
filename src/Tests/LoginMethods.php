<?php

namespace Helpers\Tests;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

trait LoginMethods
{
    use TestHelpers;

    protected $loginToken = null;

    public function getLoginToken()
    {
        if ($this->loginToken == null) {
            $this->loginToken = $this->getNewLoginToken();
        }
        return $this->loginToken;
    }

    public function getNewLoginToken()
    {
        $response = $this->post('/api/users/login', [
            'email' => 'test1@test.hu',
            'password' => 'test',
        ]);
        $this->assertStatus($response, 200);
        $responseObject = json_decode($response->content());
        return $responseObject->access_token;
    }

    protected function loginSetup()
    {
        Artisan::call('passport:install');
        $this->insertTestUserToDatabase();
    }

    protected function insertTestUserToDatabase()
    {
        $newUserId = User::count() + 1;
        User::create([
            'name' => "test{$newUserId}",
            'email' => "test{$newUserId}@test.hu",
            'password' => Hash::make('test'),
        ]);
    }
}
