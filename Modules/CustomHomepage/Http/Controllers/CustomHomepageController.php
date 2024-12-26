<?php

namespace Modules\CustomHomepage\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class CustomHomepageController extends Controller
{
    public function home()
    {
        if (config('customhomepage.homepage_redirect')) {
            return redirect()->away(config('customhomepage.homepage_redirect'), 302);
        } else {
            return view('customhomepage::home', [
                'homepage_html' => base64_decode(config('customhomepage.homepage_html'))
            ]);
        }
    }
}
