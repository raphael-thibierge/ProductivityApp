<?php

namespace App\Http\Controllers;


use App\Events\PusherDebugEvent;
use App\FinancialTransaction;
use App\Goal;
use App\Models\User;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Facebook\Extensions\ButtonTemplate;
use BotMan\Drivers\Facebook\Extensions\Element;
use BotMan\Drivers\Facebook\Extensions\ElementButton;
use BotMan\Drivers\Facebook\Extensions\GenericTemplate;
use BotMan\Drivers\Facebook\Extensions\ListTemplate;
use BotMan\Drivers\Facebook\FacebookDriver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Pusher;

class BotManController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', [
            'only' => ['showConfirm', 'confirm']
        ]);
    }


    public function handle(Request $request)
    {

        event(new PusherDebugEvent([
            'method' => 'BotManController@handle',
            'request' => $request->toArray(),
        ]));

        DriverManager::loadDriver(FacebookDriver::class);

        // Create BotMan instance
        $config = [
            'facebook' => [
                'token' => config('services.botman.facebook_token'),
                'app_secret' => config('services.botman.facebook_app_secret'),
                'verification' => config('services.botman.facebook_verification'),
            ]
        ];

        $botman = BotManFactory::create($config);

        $commandList = "Available commands : \r\n"
            . "- login : login form to link your sir edgar account\r\n"
            . "- projects : show your projects \r\n"
            . "- important : show your important goals \r\n"
            . "- estimated time {'<', '<=', '>', '>='} {time in minutes} : show your goals with this amount of estimated time \r\n"
            . "- due today : goals which have today as due date \r\n";


        try {
            $message = $request->only(['entry'])['entry'][0]['messaging'][0];
        } catch (\Exception $e){
            $message = [];
        }

        if (isset($message['account_linking'])) {

            $account_linking = $message['account_linking'];
            $sender_id = $message['sender']['id'];

            if ($account_linking['status'] === "linked"){

                $user_id = $account_linking['authorization_code'];


                if ( ($user = User::find($user_id)) !== null){

                    $user->update(['facebook_sending_id' => $sender_id]);

                    $botman->say("Wecome {$user->name} ! You're account has been successfully linked to messenger ",
                        $sender_id);
                }
            } else if ($account_linking['status'] === "unlinked") {
                User::where('facebook_sending_id', $sender_id)->update(['facebook_sending_id' => null]);

                $botman->say("Your SirEdgar account has been successfully unlinked !",
                    $sender_id);
            }
        }

        $botman->hears('Login', function (BotMan $bot) {
            $bot->reply(GenericTemplate::create()
                ->addElements([
                    Element::create('Account linking')
                        ->subtitle('Link your Sir Edgar account')
                        ->addButton(
                            ElementButton::create('Login')
                                ->url(config('app.url') . '/botman/authorize')
                                ->type('account_link')
                        )
                ])
            );
        });

        $botman->hears('Logout', function (BotMan $bot) {
            $bot->reply(GenericTemplate::create()
                ->addElements([
                    Element::create('Unlink account')
                        ->addButton(
                            ElementButton::create('Log Out')
                                ->type('account_unlink')
                        )
                ])
            );
        });

        // give the bot something to listen for.
        $botman->hears('Hi', function (BotMan $bot) use ($commandList){
            $bot->reply('Hello ! Send \'Login\' to link your SirEdgar Account');
            //$bot->reply($commandList);
        });

        $botman->hears('Hi Edgar', function (BotMan $bot) {
            $bot->reply("Hello {$bot->getUser()->getFirstName()} !");
        });

        // give the bot something to listen for.
        $botman->hears('Thanks', function (BotMan $bot) {
            $bot->reply("You are welcome!");
        });

        $botman->hears('important', function( BotMan $bot) {
            $bot->types();

            $user = $this->getCurrentUser($bot);
            $response = false;
            if ($user !== null){
                $user->goals()
                    ->where('today', true)
                    ->whereNull('completed_at')
                    ->chunk(4, function ($goals) use($bot, &$response){
                        $response = true;
                        $bot->reply($this->goalListRender($goals));
                    });
                if (!$response){
                    $bot->reply("No goal founded");
                }

            } else {
                $bot->reply('You have to connect to sir edgar. Just send \'Login\'');
            }
        });


        $botman->hears('estimated time {operator} {time}', function( BotMan $bot, $operator, $time) {
            $bot->types();

            $user = $this->getCurrentUser($bot);
            $response = false;
            if ($user !== null){

                $user->goals()
                    ->whereNull('completed_at')
                    ->whereNotNull('estimated_time')
                    ->where('estimated_time', $operator, (int)$time)
                    ->chunk(4, function ($goals) use($bot, &$response){
                        $response = true;
                        $bot->reply($this->goalListRender($goals));
                    });
                if (!$response){
                    $bot->reply("No goal founded");
                }

            } else {
                $bot->reply('You have to connect to sir edgar. Just send \'Login\'');
            }
        });

        $botman->hears('due today', function(BotMan $bot) {
            $bot->types();

            $user = $this->getCurrentUser($bot);
            $response = false;
            if ($user !== null){

                $user->goals()
                    ->whereNull('completed_at')
                    ->whereNotNull('due_date')
                    ->where('due_date', '>=', Carbon::today($user->timezone))
                    ->where('due_date', '<', Carbon::tomorrow($user->timezone))
                    ->chunk(4, function ($goals) use($bot, &$response){
                        $response = true;
                        $bot->reply($this->goalListRender($goals));
                    });
                if (!$response){
                    $bot->reply("No goal founded");
                }

            } else {
                $bot->reply('You have to connect to sir edgar. Just send \'Login\'');
            }
        });


        $botman->hears('project.goals:{projectId}', function( BotMan $bot, $projectId) {
            $user = $this->getCurrentUser($bot);
            if ($user !== null){
                $project = $user->projects()->find($projectId);
                if ($project !== null) {

                    $found = false;
                    $goals = $project->goals()->whereNull('completed_at')->chunk(4 , function ($goals) use ($bot, &$found){
                        $bot->types();
                        $found = true;
                        $bot->reply($this->goalListRender($goals));
                    });

                    if (!$found){
                        $text = 'You don\'t have any goal in this project. Visit SirEdgar web app to add goals in your project' ;
                        $bot->reply(ButtonTemplate::create($text)
                            ->addButton(ElementButton::create('Visit web app')->url(route('home')))
                        );

                    }

                } else {
                    $bot->reply('Project does not exist anymore..');
                }
            } else {
                $bot->reply('You have to connect to sir edgar. Just send \'Login\'');
            }
        });



        $botman->hears('projects', function( BotMan $bot) {

            $user = $this->getCurrentUser($bot);

            if ($user !== null){

                $found = false;

                $projects = $user->projects()->chunk(4, function($projects) use($bot, &$found){
                    $bot->types();
                    $found = true;

                    if (count($projects )== 1){

                        $project = $projects[0];

                        $nbTodo = $project->goals()->whereNull('completed_at')->count();
                        $description = "Goals to complete : {$nbTodo}";

                        $bot->reply(GenericTemplate::create()->addElement(Element::create($project->title)
                            ->subtitle($description)
                            ->addButton(
                                ElementButton::create('Display goals')
                                    ->type('postback')
                                    ->payload("project.goals:{$project->id}")
                            )));
                    } else {

                        $project_list = ListTemplate::create()
                            ->useCompactView();
                        //->addGlobalButton(ElementButton::create('view more')->url('http://test.at'));
                        foreach ($projects as $project) {


                            $nbTodo = $project->goals()->whereNull('completed_at')->count();
                            $description = "Goals to complete : {$nbTodo}";

                            $project_list->addElement(
                                Element::create($project->title)
                                    ->subtitle($description)
                                    ->addButton(
                                        ElementButton::create('Display goals')
                                            ->type('postback')
                                            ->payload("project.goals:{$project->id}")
                                    )
                            );
                        }
                        $bot->reply($project_list);
                    }
                });

                if (!$found){
                    $text = 'You don\'t have any project yet. Create your first one in the web app' ;
                    $bot->reply(ButtonTemplate::create($text)
                        ->addButton(ElementButton::create('Visit web app')->url(route('home')))
                    );
                }

            } else {
                $bot->reply('You have to connect to sir edgar. Just send \'Login\'');
            }

        });


        $botman->hears('goal.complete:{id}', function (BotMan $bot, $id) {

            $user = $this->getCurrentUser($bot);
            if ($user !== null) {

                $goal = $user->goals()->with('project')->find($id);
                if ($goal !== null) {

                    $goal->setCompleted();
                    $goal->save();
                    $bot->reply("Goal \"{$goal->title}\" set as completed");

                } else {
                    $bot->reply("Goal doesn't exist anymore");
                }

            } else {
                $bot->reply('You have to connect to sir edgar. Just send \'Login\'');
            }
        });

        $botman->hears('select:{id}', function (BotMan $bot, $id) {

            $user = $this->getCurrentUser($bot);
            if ($user !== null){

                $goal = $user->goals()->with('project')->find($id);
                if ($goal !== null){

                    $bot->reply($this->goalRender($goal));


                } else {
                    $bot->reply("Goal doesn't exist anymore");
                }

            } else {
                $bot->reply('You have to connect to sir edgar. Just send \'Login\'');
            }

        });

        $botman->hears('/expense {price} {currency} {tags}', function(Botman $bot, $price, $currency, $tags){
            $user = $this->getCurrentUser($bot);
            if ($user !== null) {

                $tagsArray = [];

                $price = str_replace(',', '.', $price);

                foreach (explode('#', $tags) as $tag){
                    if (!empty($tag)){
                        $tagsArray []= $tag;
                    }
                }

                $ft = FinancialTransaction::create([
                    'user_id' => $user->id,
                    'price' => (float)$price,
                    'currency' => $currency,
                    'tags' => $tagsArray,
                    'type' => FinancialTransaction::EXPENSE,
                ]);


                $bot->reply($ft->toString());

            } else {
                $bot->reply('You have to connect to sir edgar. Just send \'Login\'');
            }
        });

        $botman->hears('/entrance {price} {currency} {tags}', function(Botman $bot, $price, $currency, $tags){
            $user = $this->getCurrentUser($bot);
            if ($user !== null) {

                $tagsArray = [];

                $price = str_replace(',', '.', $price);

                foreach (explode('#', $tags) as $tag){
                    if (!empty($tag)){
                        $tagsArray []= $tag;
                    }
                }

                $ft = FinancialTransaction::create([
                    'user_id' => $user->id,
                    'price' => (float)$price,
                    'currency' => $currency,
                    'tags' => $tagsArray,
                    'type' => FinancialTransaction::ENTRANCE,
                ]);


                $bot->reply($ft->toString());

            } else {
                $bot->reply('You have to connect to sir edgar. Just send \'Login\'');
            }
        });

        $botman->hears('transactions', function(Botman $bot){
            $user = $this->getCurrentUser($bot);
            if ($user !== null) {

                $expenses = $user->financialTransactions()
                    ->where('created_at', '>=', Carbon::today($user->timezone))
                    ->get();

                $list = "";

                foreach ($expenses as $expense){
                    $list .= $expense->toString() . "\r\n";
                }


                $bot->reply($list !== "" ? $list : 'No financial transaction');

            } else {
                $bot->reply('You have to connect to sir edgar. Just send \'Login\'');
            }
        });

        $botman->hears('help', function($bot) use ($commandList){
            $bot->reply($commandList);
        });

        $botman->fallback(function($bot) use ($commandList){
            $bot->reply("Sorry, I did not understand these commands. Please retry :)");
            $bot->reply($commandList);
        });


        // start listening
        $botman->listen();

    }
    public function goalRender(Goal $goal){
        $description =
            "Project  : {$goal->project->title}\r\n" .
            "Score    : {$goal->score}\r\n";

        if ($goal->priority !== null){
            $description .= "Priority : {$goal->priority}\r\n";
        }

        if ($goal->due_date !== null){
            $description .= "Due Date : {$goal->due_date}\r\n";
        }

        return GenericTemplate::create()->addElement(Element::create($goal->title)
            ->subtitle($description)
            ->addButton(ElementButton::create('Set completed')
                ->type('postback')
                ->payload('goal.complete:' . $goal->id)
            )
        );
    }

    private function goalListRender($goals){
        if (count($goals) == 1){
            return $this->goalRender($goals[0]);
        }

        $goalList = ListTemplate::create()
            ->useCompactView();
        //->addGlobalButton(ElementButton::create('view more')->url('http://test.at'));

        foreach ($goals as $goal){

            $goalList->addElement(
                Element::create($goal->title)
                    ->subtitle($goal->project->title)
                    //->image('http://botman.io/img/botman-body.png')
                    ->addButton(ElementButton::create('Select')
                        ->type('postback')
                        ->payload('select:' . $goal->id))


            );
        }
        return $goalList;
    }


    private function getCurrentUser(BotMan $bot){
        return User::where('facebook_sending_id', $bot->getUser()->getId())->first();
    }


    /**
     * Display login form for messenger account linking
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showMessengerLoginForm(Request $request)
    {
        if ($request->has('redirect_uri') && $request->has('account_linking_token')){
            if (($user = Auth::user()) !== null){
                return Redirect::to(route('botman.confirm.show', [
                    'redirect_uri' => $request->get('redirect_uri'),
                    'account_linking_token' => $request->get('account_linking_token'),
                ]));
            }
        }
        return view('auth.login');
    }

    public function confirm(Request $request){
        $this->validate($request, [
            'redirect_uri' => 'required'
        ]);

        $user = Auth::user();

        if ($request->session()->has('messenger_sender_id')){
            $user->update(['facebook_sending_id' => $request->session()->get('messenger_sender_id') ]);
        }

        return Redirect::to($request->get('redirect_uri') . '&authorization_code=' . $user->id);
    }

    public function showConfirm(){
        return view('auth.messenger-confiramtion');
    }




}