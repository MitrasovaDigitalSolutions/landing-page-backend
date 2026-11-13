<?php

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    use HasFactory, BelongsToSite;

    protected $fillable = [
        'site_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'attachment_path',
        'status',
        'notes',
    ];

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment_path ? url('storage/' . $this->attachment_path) : null;
    }
}
