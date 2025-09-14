<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TriggerHistory;
use App\Http\Resources\TriggerHistoryResource;

class TriggerHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $query = TriggerHistory::query()
            ->leftJoin('users as partners', 'trigger_history.partner_id', '=', 'partners.id')
            ->leftJoin('users as customers', 'trigger_history.customer_id', '=', 'customers.id')
            ->select(
                'trigger_history.*',
                'partners.name as partner_name',
                'customers.name as customer_name'
            );


        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('customers.name', 'like', "%{$s}%")
                  ->orWhere('partners.name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('year')) {
            $query->whereYear('trigger_history.created_at', $request->input('year'));
        }

        if ($request->filled('month')) {
            $query->whereMonth('trigger_history.created_at', $request->input('month'));
        }

        if ($request->filled('day')) {
            $query->whereDay('trigger_history.created_at', $request->input('day'));
        }
        // 1ページあたりの件数はリクエストで指定できるように
        $perPage = $request->input('per_page', 10);

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
