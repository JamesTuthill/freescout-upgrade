<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    public $user;
    protected function setUp(): void {

        //User
        factory(App\User::class, 1)->make([
            'id' => 1
        ]);

        $this->user = User::find(1);
    }

    public function test_HasPermission_GrantPermissionFromGlobalConfig()
    {

        //GIVEN
        $userStub = $this->createStub($this->user);

        $userStub->method('getGlobalUserPermissions')
            ->willReturn([2,3,7]);

        //WHEN
        $permission = $userStub->hasPermission(2,false);

        //THEN
        $this->assertTrue($permission);

    }

    public function test_HasPermission_GlobalUserPermissionsMissing()
    {

        //GIVEN
        $userStub = $this->createStub($this->user);

        $userStub->method('getGlobalUserPermissions')
            ->willReturn(null);

        //WHEN
        $permission = $userStub->hasPermission(2,false);

        //THEN
        $this->assertNotTrue($permission);

    }

    public function test_HasPermission_GlobalUserPermissionsNotArray()
    {
        //GIVEN
        $userStub = $this->createStub($this->user);

        $userStub->method('getGlobalUserPermissions')
            ->willReturn('doh');

        //WHEN
        $permission = $userStub->hasPermission(2,false);

        //THEN
        $this->assertNotTrue($permission);
    }

    public function test_HasPermission_GrantPermissionFromUserModel()
    {

        //GIVEN
        $this->user->permissions = [2,7,5];
        $this->user->save();

        $userStub = $this->createStub($this->user);

        $userStub->method('getGlobalUserPermissions')
            ->willReturn(null);

        //WHEN
        $permission = $userStub->hasPermission(2,true);

        //THEN
        $this->assertTrue($permission);

    }

    public function test_HasPermission_CheckOwnPermissionTurnedOff()
    {

        //GIVEN
        $this->user->permissions = [2,7,5];
        $this->user->save();
        $userStub = $this->createStub($this->user);

        $userStub->method('getGlobalUserPermissions')
            ->willReturn(null);

        //WHEN
        $permission = $userStub->hasPermission(2,false);

        //THEN
        $this->assertNotTrue($permission);

    }

    public function test_HasPermission_GlobalConfigPermissionsMissingAndUserModelHasNoPermissions()
    {

        //GIVEN
        $userStub = $this->createStub($this->user);

        $userStub->method('getGlobalUserPermissions')
            ->willReturn(null);

        //WHEN
        $permission = $userStub->hasPermission(2,true);

        //THEN
        $this->assertTrue($permission);

    }

    public function test_HasPermission_GlobalConfigPermissionsMissingAndUserModelPermissionsNotArray()
    {

        //GIVEN
        $this->user->permissions = 'not an array';
        $this->user->save();
        $userStub = $this->createStub($this->user);

        $userStub->method('getGlobalUserPermissions')
            ->willReturn(null);

        //WHEN
        $permission = $userStub->hasPermission(2,true);

        //THEN
        $this->assertNotTrue($permission);

    }

    public function test_HasPermission_DontGrantPermissionFromUserModel()
    {

        //GIVEN
        $this->user->permissions = [2,7,5];
        $this->user->save();

        $userStub = $this->createStub($this->user);

        $userStub->method('getGlobalUserPermissions')
            ->willReturn(null);

        //WHEN
        $permission = $userStub->hasPermission(8,true);

        //THEN
        $this->assertNotTrue($permission);

    }
}
