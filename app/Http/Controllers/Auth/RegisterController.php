<?php

namespace App\Http\Controllers\Auth;

use App\Notifications\MessengerNotification;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers {
        register as protected registerTrait;
    }

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        $userRegistered = User::newUser([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        if (($user = User::where('admin', true)->first()) !== null){
            $user->notify(new MessengerNotification(
                "I have good news for you ;) \r\n You have a new user : $userRegistered->name ($userRegistered->email)"
            ));
        }



        return $userRegistered;
    }

    public function register(Request $request)
    {
        // messenger account linking case
        if ($request->has('redirect_uri') && $request->has('account_linking_token')){
            // process normal register
            $response = $this->registerTrait($request);
            // if success redirect to messenger
            if (($user = Auth::user()) != null){
                return Redirect::to(route('botman.confirm', [
                    'redirect_uri' => $request->get('redirect_uri'),
                    'account_linking_token' => $request->get('account_linking_token'),
                ]));
            }
            return $response;
        }

        return $this->registerTrait($request);
    }


}
