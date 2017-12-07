<?php

namespace App\Console\Commands;

use App\Notifications\MessengerNotification;
use App\Services\CoinbaseService;
use App\User;
use Coinbase\Wallet\Exception\HttpException;
use Coinbase\Wallet\Exception\ServiceUnavailableException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckCoinbaseApiStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coinbase:api:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    const CACHE_KEY = 'coinbase-api-status';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $client = CoinbaseService::connectWithAPI();

        try {
            $client->getAccounts();

            if (!Cache::has(self::CACHE_KEY)){
                User::first()->notify(new MessengerNotification('Coinbase API up !'));
                Cache::forever(self::CACHE_KEY, true);
            }else if (Cache::get(self::CACHE_KEY) === false){
                User::first()->notify(new MessengerNotification('Coinbase API up !'));
                Cache::forever(self::CACHE_KEY, true);
            }

        } catch (ServiceUnavailableException $exception){
            $this->apiOff();
        } catch (ServerException $exception){
            $this->apiOff();
        } catch (HttpException $exception){
            $this->apiOff();
        } catch (\Exception $exception){
            $this->apiOff();
        }

    }

    private function apiOff(){
        if (!Cache::has(self::CACHE_KEY)){
            Cache::forever(self::CACHE_KEY, false);
            User::first()->notify(new MessengerNotification('Coinbase API down !'));
        } else if (Cache::get(self::CACHE_KEY)){
            User::first()->notify(new MessengerNotification('Coinbase API down !'));
            Cache::forever(self::CACHE_KEY, false);
        }
    }
}
