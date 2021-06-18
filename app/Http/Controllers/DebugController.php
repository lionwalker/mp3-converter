<?php

namespace App\Http\Controllers;

use App\Mail\ConversionFinished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class DebugController extends Controller
{
    public function index()
    {
        return Auth::user()->email;
    }
}
