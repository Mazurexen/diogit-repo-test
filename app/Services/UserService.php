<?php

namespace App\Services;

use App\Models\User;
use App\Models\Profile;
use App\Traits\CaptureIpTrait;
use Illuminate\Support\Facades\Hash;

class UserService
{
    private $ipAddress;

    public function __construct(CaptureIpTrait $ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    public function create(array $data)
    {
        $user = User::create([
            'name' => strip_tags($data['name']),
            'first_name' => strip_tags($data['first_name']),
            'last_name' => strip_tags($data['last_name']),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'token' => str_random(64),
            'admin_ip_address' => $this->ipAddress->getClientIp(),
            'activated' => 1,
        ]);

        $user->profile()->save(new Profile());
        $user->attachRole($data['role']);
        
        return $user;
    }

    public function update(User $user, array $data)
    {
        $user->name = strip_tags($data['name']);
        $user->first_name = strip_tags($data['first_name']);
        $user->last_name = strip_tags($data['last_name']);

        if (isset($data['email']) && $data['email'] !== $user->email) {
            $user->email = $data['email'];
        }

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if (isset($data['role'])) {
            $user->detachAllRoles();
            $user->attachRole($data['role']);
            $user->activated = $data['role'] == 3 ? 0 : 1;
        }

        $user->updated_ip_address = $this->ipAddress->getClientIp();
        $user->save();

        return $user;
    }
}