<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Este proyecto es una API pura, por lo que este archivo de rutas web
| no se usa. Se mantiene porque Laravel lo requiere en bootstrap/app.php.
|
*/

Route::get('/', function () {
    return response()->json([
        'name'          => 'Laravel API Blueprint',
        'docs'          => url('/api/documentation'),
        'version'       => '1.0.0',
    ]);
});
