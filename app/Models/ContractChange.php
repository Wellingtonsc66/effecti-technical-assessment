<?php

namespace App\Models;

use Database\Factories\ContractChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['contract_id', 'description', 'data'])]
class ContractChange extends Model
{
    /** @use HasFactory<ContractChangeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
