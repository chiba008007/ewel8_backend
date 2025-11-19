<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Information;
use Exception;
use Illuminate\Support\Facades\DB;

class InformationController extends Controller
{
    //
    public function getInfoList()
    {
        $informations = Information::where('status', 1)
        ->with('viewers')
        ->get();
        return response()->json($informations);
    }

    public function setInfoList(Request $request)
    {

        try {
            DB::beginTransaction();

            $info = Information::create([
                'title'      => $request->title,
                'started_at' => $request->started_at,
                'ended_at'   => $request->ended_at,
                'display'    => $request->display,
                'note'       => $request->note,
                'file'       => $request->file,
            ]);

            if ($request->has('users')) {

                // user_id の配列を pivot 用に整形
                $pivotData = collect($request->users)->mapWithKeys(function ($item) {
                    return [
                        $item['user_id'] => []  // status = 有効
                    ];
                })->toArray();

                // pivot へ保存
                $info->viewers()->syncWithoutDetaching($pivotData);

            }
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            return false;
        }
    }


}
