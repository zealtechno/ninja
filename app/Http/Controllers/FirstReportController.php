<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Auth;
use Utils;
use View;
use URL;
use Input;
use Session;
use Redirect;
use Cache;

class FirstReportController extends BaseController
{
    public function index()
    {      
        return View::make('list', [
            'entityType' => 'firstreport',
            'title' => trans('firstreport'),
            'sortCol' => '4',
            'columns' => Utils::trans([
              'checkbox',
              'vendor',
              'city',
              'phone',
              'email',
              'date_created',
              ''
            ]),
        ]);		
    }


    public function getDatatable()
    {
        //return $this->vendorService->getDatatable(Input::get('sSearch'));
    }
}
