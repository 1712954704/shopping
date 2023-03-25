<?php

use App\Http\Controllers\EmployeesController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});




Route::get('/ehr/user/{id}', function () {
    return ['test', 'id'];
});




Route::get('/email', [\App\Http\Controllers\Hr\AssessmentController::class, 'email']);
Route::get('/basic_email', [\App\Http\Controllers\Hr\AssessmentController::class, 'basic_email']);





