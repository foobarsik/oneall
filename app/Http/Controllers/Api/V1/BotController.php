<?php

namespace App\Http\Controllers\Api\V1;

use App\Entities\Bot;
use App\Exceptions\InvalidAuthTokenException;
use App\Factories\MessengerFactory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BotController extends Controller
{
    public function index()
    {
        return Bot::select('id', 'name', 'type')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_:\-]*$/'],
            'type' => ['required', Rule::in(Bot::TYPES)],
        ]);

        $messenger = MessengerFactory::create($request->type, $request->token);

        try {
            $messenger->setWebhook(config('app.url') . '/api/v1/' . $request->type . '/callback/' . $request->token);
        } catch (InvalidAuthTokenException $e) {
            throw ValidationException::withMessages(['token' => ['Provided token is invalid']]);
        }

        return Bot::create($request->all());
    }

    public function destroy($id)
    {
        Bot::findOrFail($id)->delete();

        return response()->json([], 204);
    }
}
