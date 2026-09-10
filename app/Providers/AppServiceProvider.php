<?php

namespace App\Providers;

use Queue;
use Log;
use Redis;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        # We log in the Redis system for every second of the day how many workers were actively running.
        # This is necessary so that we can notice from which point in time we have too few workers available.
        Queue::before(function (JobProcessing $event) {
            $this->begin = strtotime(date(DATE_RFC822, mktime(date("H"),date("i"), date("s"), date("m"), date("d"), date("Y"))));
        });
        Queue::after(function (JobProcessed $event) {
            $today = strtotime(date(DATE_RFC822, mktime(0,0,0, date("m"), date("d"), date("Y"))));
            $end = strtotime(date(DATE_RFC822, mktime(date("H"),date("i"), date("s"), date("m"), date("d"), date("Y")))) - $today;
            $expireAt = strtotime(date(DATE_RFC822, mktime(0,0,0, date("m"), date("d")+1, date("Y"))));
            try{
                $redis = Redis::connection('redisLogs');
                if( !$redis )
                    return;
                $p = getmypid();
                $host = gethostname();
                $begin = $this->begin - $today;
                $redis->pipeline(function($pipe) use ($p, $expireAt, $host, $begin, $end)
                {
                    for( $i = $begin; $i <= $end; $i++)
                    {
                        $pipe->sadd("logs.worker.$host.$i", strval($p));
                        $pipe->expire("logs.worker.$host.$i", 10);
                        $pipe->eval("redis.call('hset', 'logs.worker.$host', '$i', redis.call('scard', 'logs.worker.$host.$i'))", 0);
                        $pipe->sadd("logs.worker", $host);
                        if( date("H") !== 0 )
                        {
                            $pipe->expire("logs.worker.$host", $expireAt);
                            $pipe->expire("logs.worker", $expireAt);
                        }
                    }
                });
            }catch( \Exception $e)
            {
                return;
            }
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
