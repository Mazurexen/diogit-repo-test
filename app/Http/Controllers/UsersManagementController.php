<?php

namespace App\Http\Controllers;


    public function __construct(UserService $userService)
    {
        $this->middleware('auth');
        $this->userService = $userService;
    }

    public function index()
    {
        $users = config('usersmanagement.enablePagination')
            ? User::paginate(config('usersmanagement.paginateListSize'))
            : User::all();

            'users' => $users,
            'roles' => Role::all()
        ]);
    }


    }

    public function show(User $user)
    {
        return view('usersmanagement.show-user', compact('user'));
    }

    public function edit(User $user)
    {
        $currentRole = $user->roles->first();
        
        return view('usersmanagement.edit-user', [
            'user' => $user,
            'roles' => Role::all(),
            'currentRole' => $currentRole
        ]);
    }

    public function destroy(User $user)
    {

    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'user_search_box' => 'required|string|max:255'
        ]);

        $results = User::where('id', 'like', $validated['user_search_box'].'%')
            ->orWhere('name', 'like', $validated['user_search_box'].'%')
            ->orWhere('email', 'like', $validated['user_search_box'].'%')
            ->with('roles')
            ->get();

        return response()->json($results, Response::HTTP_OK);
    }
}
