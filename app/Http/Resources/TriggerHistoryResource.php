<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TriggerHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // trigger_history の全カラム
            'id'          => $this->id,
            'partner_id'  => $this->partner_id,
            'customer_id' => $this->customer_id,
            'type'        => $this->type,
            'testtype'    => $this->testtype,
            'status'      => $this->status,
            'testname'    => $this->testname,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,

            // users テーブルから取得した追加情報
            'partner_name'  => $this->partner?->name,
            'customer_name' => $this->customer?->name,
        ];
    }
}
