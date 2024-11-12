<?php

namespace App\Services;

use App\Models\User;
use App\Models\Profile;
use App\Traits\CaptureIpTrait;

        ]);

        $user->profile()->save(new Profile());
        $user->attachRole($data['role']);
        
        return $user;
    }

