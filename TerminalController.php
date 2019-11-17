<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;



class TerminalController extends Controller
{
    public function showTerminal()
    {
        return view('advanced.terminal', [
            'noLogin' => config('terminal.noLogin'),
        ]);
    }

    public function runCommand(Request $request)
    {
        $RPCServer = new WebConsoleRPCServer();
        $response = $RPCServer->execute();
        return response()->json($response);
    }
}
