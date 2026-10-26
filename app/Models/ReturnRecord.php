<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['item_report_id', 'claim_id', 'recipient_id', 'admin_id', 'note'])]
class ReturnRecord extends Model
{
    public function report()
    {
        return $this->belongsTo(ItemReport::class, 'item_report_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
