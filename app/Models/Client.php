<?php

namespace App\Models;

use App\Enums\ClientStatusEnum;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'document', 'email', 'status'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ClientStatusEnum::class,
        ];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
