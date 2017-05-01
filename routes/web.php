<?php

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

Route::get('/excel-into-db', function () {

	//5000 rows might take awhile on some systems
	ini_set('max_execution_time', 600); //600 seconds = 10 minutes

	//Time Tracker
	$time_start = microtime(true);

	//Make sure database is empty first
	if(!\App\Client::limit(1)->get()->isEmpty()) {
		return redirect('/')->with('error', 'Please clear database before initiating import');
	}

	//Load the rows/data from the excel sheet
	$data = \Excel::load(storage_path().'/mock_data.xlsx', function($reader) {})->get();

	//Loop each row of the excel sheet and put the data in db
	$data->each(function($row) {
		$client = new \App\Client;
		$address = new \App\Address;
		$contact = new \App\Contact;

		$client->name = $row->name;
		$client->ic_no = $row->ic_no;
		$client->account_no = $row->account_no;
		$client->save();
		$client->fresh(); //use fresh to get fresh copy of row from db with its id

		$address->client_id = $client->getKey();
		$address->address = $row->address;
		$address->save();

		$contact->client_id = $client->getKey();
		$contact->phone = $row->phone;
		$contact->email = $row->email;
		$contact->save();
    });

	$time_end = microtime(true);
	//dividing with 60 will give the execution time in minutes
	$execution_time = round(($time_end - $time_start)/60, 2);

    return redirect('/')->with('success', 'Excel sheet successfully imported in ' . $execution_time.' Mins');
});

Route::get('/clear-db', function () {

		\Eloquent::unguard();
		//disable foreign key check for this connection
		\DB::statement('SET FOREIGN_KEY_CHECKS=0;');

		\App\Client::truncate();
		\App\Address::truncate();
		\App\Contact::truncate();

		\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

		return redirect('/')->with('success', 'Database cleared. You may click the "Import Excel Into Database" link');

});