<?php

namespace App\Services;

use App\Models\User;
use App\Models\Profile;
use App\Traits\CaptureIpTrait;

class UserService
{
    private $ipCapture;
    
    public function __construct(CaptureIpTrait $ipCapture)
    {
        $this->ipCapture = $ipCapture;
    }

    public function createUser(array $data): User
    {
        $user = User::create([
            'name'             => strip_tags($data['name']),
            'first_name'       => strip_tags($data['first_name']),
            'last_name'        => strip_tags($data['last_name']),
            'email'            => $data['email'],
            'password'         => Hash::make($data['password']),
            'token'            => str_random(64),
            'admin_ip_address' => $this->ipCapture->getClientIp(),
            'activated'        => 1,
        ]);

        $user->profile()->save(new Profile());
        $user->attachRole($data['role']);
        
        return $user;
    }

    public function updateUser(User $user, array $data): User
    {
        // Lógica de atualização movida para cá
    }
} 