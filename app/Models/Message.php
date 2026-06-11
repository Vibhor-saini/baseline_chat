<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'type',          // text | image | file
        'file_path',
        'is_read',
        'delivered_at',
        'read_at',
        'forwarded_from_id',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'read_at'      => 'datetime',
        'deleted_at'   => 'datetime',
        'is_read'      => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function forwardedFrom()
    {
        return $this->belongsTo(Message::class, 'forwarded_from_id')->withTrashed();
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * WhatsApp-style delivery status for a sent message:
     *   'read'      → double blue tick
     *   'delivered' → double grey tick
     *   'sent'      → single grey tick
     */
    public function deliveryStatus(): string
    {
        if ($this->read_at)      return 'read';
        if ($this->delivered_at) return 'delivered';
        return 'sent';
    }

    /**
     * Public URL for file/image messages.
     */
    public function fileUrl(): ?string
    {
        return $this->file_path
            ? asset('storage/' . $this->file_path)
            : null;
    }

    /**
     * Original filename extracted from stored path.
     */
    public function fileName(): ?string
    {
        return $this->file_path
            ? basename($this->file_path)
            : null;
    }
}
