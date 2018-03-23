<?php

namespace App\Http\Middleware;

use App\BrowserDetection;
use App\Models\Visitor;
use Closure;

class Visitors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $broser = new BrowserDetection();
        $clientIP = getIP();
        $client = json_decode(file_get_contents('http://ip-api.com/json/' . $clientIP), true);
        $protocol = isset($_SERVER["HTTPS"]) ? 'https' : 'http';

        if (!$request->session()->has('visitor.' . $clientIP)) {

            $visitor = Visitor::where('ip', '=', $clientIP)->first();

            if ($visitor == null) {
                Visitor::create([
                    'ip' => isset($clientIP) ? $clientIP : '',
                    'country' => isset($client['country']) ? $client['country'] : '',
                    'city' => isset($client['city']) ? $client['city'] : '',
                    'estate' => isset($client['regionName']) ? $client['regionName'] : '',
                    'os_system' => $broser->getPlatformVersion(),
                    'browser' => $broser->getName(),
                    'referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Desconhecido',
                    'full_link' => $protocol .'/'. $_SERVER['HTTP_HOST'] .'/'. $_SERVER['REQUEST_URI'],
                    'load_time' => round((microtime(true) - LARAVEL_START), 8)
                ]);
            } else {
                $visitor->browser = $broser->getName();
                $visitor->has_returned = 1;
                $visitor->access += 1;
                $visitor->update();
            }
        } else {
            $request->session()->put('visitor.' . $clientIP);
        }
        return $next($request);
    }
}
