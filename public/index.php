<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 定义根路径
define('ROOT_PATH', dirname(dirname(__FILE__)) . '/');
// 如果存在环境常量
if (file_exists(ROOT_PATH . 'env.php')) {
    require_once ROOT_PATH . 'env.php';
}

// 容错处理，假如没有环境常量，默认为生产环境，防止上线时还未配置环境常量导致找不到配置报错
!defined('PHP_ENV') && define('PHP_ENV', 'prod');

// 定义配置文件路径
define('CONFIG_PATH', ROOT_PATH . 'config/'.PHP_ENV.'/');
////加载配置文件
//define('GET_CONFIG', require_once KERNEL_DIR . '/../env_conf/'.PHP_ENV.'/config.php');

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

// 记录请求开始时间
//ETS::start(ETS::STAT_ET_REQUEST);

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
