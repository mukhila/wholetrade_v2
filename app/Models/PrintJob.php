<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintJob extends Model
{
    protected $fillable = [
        'invoice_id', 'station', 'format', 'payload',
        'copies', 'cols', 'status', 'error', 'printed_at',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
        'copies'     => 'integer',
        'cols'       => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }

    public function isPending():  bool { return $this->status === 'pending'; }
    public function isDone():     bool { return $this->status === 'done'; }
    public function isFailed():   bool { return $this->status === 'failed'; }

    public function markPrinting(): void
    {
        $this->update(['status' => 'printing']);
    }

    public function markDone(): void
    {
        $this->update(['status' => 'done', 'printed_at' => now()]);
    }

    public function markFailed(string $error): void
    {
        $this->update(['status' => 'failed', 'error' => $error]);
    }
}
