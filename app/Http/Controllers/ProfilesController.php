<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteUserAccount;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Requests\UpdateUserProfile;
use App\Models\Profile;
use App\Models\Theme;
use App\Models\User;
use App\Notifications\SendGoodbyeEmail;
use App\Traits\CaptureIpTrait;
use File;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Image;
use jeremykenedy\Uuid\Uuid;
use Validator;
use View;

class ProfilesController extends Controller
{
    protected $idMultiKey = '618423'; //int
    protected $seperationKey = '****';
    protected $themes;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->themes = cache()->remember('active_themes', 86400, function() {
            return Theme::where('status', 1)
                       ->orderBy('name', 'asc')
                       ->get();
        });
    }

    /**
     * Fetch user
     * (You can extract this to repository method).
     *
     * @param  $username
     * @return mixed
     */
    public function getUserByUsername($username)
    {
        return cache()->remember('user.' . $username, 3600, function() use ($username) {
            return User::with(['profile', 'profile.theme'])->wherename($username)->firstOrFail();
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $username
     * @return Response
     */
    public function show($username)
    {
        try {
            $user = $this->getUserByUsername($username);
            return view('profiles.show', compact('user'));
        } catch (ModelNotFoundException $exception) {
            abort(404);
        }
    }

    /**
     * /profiles/username/edit.
     *
     * @param  $username
     * @return mixed
     */
    public function edit($username)
    {
        try {
            $user = $this->getUserByUsername($username);
        } catch (ModelNotFoundException $exception) {
            return view('pages.status')
                ->with('error', trans('profile.notYourProfile'))
                ->with('error_title', trans('profile.notYourProfileTitle'));
        }

        $themes = Theme::where('status', 1)
                        ->orderBy('name', 'asc')
                        ->get();

        $currentTheme = Theme::find($user->profile->theme_id);

        $data = [
            'user'         => $user,
            'themes'       => $themes,
            'currentTheme' => $currentTheme,

        ];

        return view('profiles.edit')->with($data);
    }

    /**
     * Update a user's profile.
     *
     * @param  \App\Http\Requests\UpdateUserProfile  $request
     * @param  $username
     * @return mixed
     *
     * @throws Laracasts\Validation\FormValidationException
     */
    public function update(UpdateUserProfile $request, $username)
    {
        $user = $this->getUserByUsername($username);

        $input = $request->only('theme_id', 'location', 'bio', 'twitter_username', 'github_username', 'avatar_status');

        $ipAddress = new CaptureIpTrait();

        if ($user->profile === null) {
            $profile = new Profile();
            $profile->fill($input);
            $user->profile()->save($profile);
        } else {
            $user->profile->fill($input)->save();
        }

        $user->updated_ip_address = $ipAddress->getClientIp();
        $user->save();

        return redirect('profile/'.$user->name.'/edit')->with('success', trans('profile.updateSuccess'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateUserAccount(Request $request, $id)
    {
        $currentUser = \Auth::user();
        $user = User::findOrFail($id);
        $emailCheck = ($request->input('email') !== '') && ($request->input('email') !== $user->email);
        $ipAddress = new CaptureIpTrait();
        $rules = [];

        if ($user->name !== $request->input('name')) {
            $usernameRules = [
                'name' => 'required|max:255|unique:users',
            ];
        } else {
            $usernameRules = [
                'name' => 'required|max:255',
            ];
        }
        if ($emailCheck) {
            $emailRules = [
                'email' => 'email|max:255|unique:users',
            ];
        } else {
            $emailRules = [
                'email' => 'email|max:255',
            ];
        }
        $additionalRules = [
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
        ];

        $rules = array_merge($usernameRules, $emailRules, $additionalRules);
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user->name = strip_tags($request->input('name'));
        $user->first_name = strip_tags($request->input('first_name'));
        $user->last_name = strip_tags($request->input('last_name'));

        if ($emailCheck) {
            $user->email = $request->input('email');
        }

        $user->updated_ip_address = $ipAddress->getClientIp();

        $user->save();

        return redirect('profile/'.$user->name.'/edit')->with('success', trans('profile.updateAccountSuccess'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateUserPasswordRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateUserPassword(UpdateUserPasswordRequest $request, $id)
    {
        $currentUser = \Auth::user();
        $user = User::findOrFail($id);
        $ipAddress = new CaptureIpTrait();

        if ($request->input('password') !== null) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->updated_ip_address = $ipAddress->getClientIp();
        $user->save();

        return redirect('profile/'.$user->name.'/edit')->with('success', trans('profile.updatePWSuccess'));
    }

    /**
     * Upload and Update user avatar.
     *
     * @param  $file
     * @return mixed
     */
    public function upload(Request $request)
    {
        if ($request->hasFile('file')) {
            $currentUser = auth()->user();
            $avatar = $request->file('file');
            
            $validator = Validator::make(['file' => $avatar], [
                'file' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Arquivo inválido'], 422);
            }

            $filename = 'avatar.' . $avatar->getClientOriginalExtension();
            $save_path = storage_path("users/id/{$currentUser->id}/uploads/images/avatar/");
            
            dispatch(function() use ($avatar, $save_path, $filename, $currentUser) {
                File::makeDirectory($save_path, 0755, true, true);
                
                Image::make($avatar)
                    ->fit(300, 300)
                    ->save($save_path . $filename, 80);
                    
                $public_path = "/images/profile/{$currentUser->id}/avatar/{$filename}";
                $currentUser->profile->update(['avatar' => $public_path]);
            })->afterResponse();

            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false]);
    }

    /**
     * Show user avatar.
     *
     * @param  $id
     * @param  $image
     * @return string
     */
    public function userProfileAvatar($id, $image)
    {
        return Image::make(storage_path().'/users/id/'.$id.'/uploads/images/avatar/'.$image)->response();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\DeleteUserAccount  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteUserAccount(DeleteUserAccount $request, $id)
    {
        $currentUser = \Auth::user();
        $user = User::findOrFail($id);
        $ipAddress = new CaptureIpTrait();

        if ($user->id !== $currentUser->id) {
            return redirect('profile/'.$user->name.'/edit')->with('error', trans('profile.errorDeleteNotYour'));
        }

        // Create and encrypt user account restore token
        $sepKey = $this->getSeperationKey();
        $userIdKey = $this->getIdMultiKey();
        $restoreKey = config('settings.restoreKey');
        $encrypter = config('settings.restoreUserEncType');
        $level1 = $user->id * $userIdKey;
        $level2 = urlencode(Uuid::generate(4).$sepKey.$level1);
        $level3 = base64_encode($level2);
        $level4 = openssl_encrypt($level3, $encrypter, $restoreKey);
        $level5 = base64_encode($level4);

        // Save Restore Token and Ip Address
        $user->token = $level5;
        $user->deleted_ip_address = $ipAddress->getClientIp();
        $user->save();

        // Send Goodbye email notification
        $this->sendGoodbyEmail($user, $user->token);

        // Soft Delete User
        $user->delete();

        // Clear out the session
        $request->session()->flush();
        $request->session()->regenerate();

        return redirect('/login/')->with('success', trans('profile.successUserAccountDeleted'));
    }

    /**
     * Send GoodBye Email Function via Notify.
     *
     * @param  array  $user
     * @param  string  $token
     * @return void
     */
    public static function sendGoodbyEmail(User $user, $token)
    {
        $user->notify(new SendGoodbyeEmail($token));
    }

    /**
     * Get User Restore ID Multiplication Key.
     *
     * @return string
     */
    public function getIdMultiKey()
    {
        return $this->idMultiKey;
    }

    /**
     * Get User Restore Seperation Key.
     *
     * @return string
     */
    public function getSeperationKey()
    {
        return $this->seperationKey;
    }
}
