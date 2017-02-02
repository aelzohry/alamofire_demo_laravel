<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' =>'v1'],function() {
    Route::get('users', 'Controller@users');
    Route::post('login', 'Controller@login');
    Route::post('register', 'Controller@register');

    Route::group(['middleware'=>'guest'],function() {
        Route::get('tasks', 'Controller@tasks');
        Route::post('task/create', 'Controller@new_task');
        Route::post('task/edit', 'Controller@edit_task');
        Route::post('task/delete', 'Controller@delete_task');
    });
});
