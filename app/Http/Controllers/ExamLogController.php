<?php

namespace App\Http\Controllers;


use App\Http\Requests\ExamLogRequest;
use App\Services\ExamLogService;

class ExamLogController extends Controller
{
    //
    public function set(ExamLogRequest $request, ExamLogService $service)
    {

        $examLog = $service->record(
            $request->validated()
        );

        return response()->json([
            'result' => true,
            'message' => 'success',
            'data' => $examLog,
        ]);
    }
}
