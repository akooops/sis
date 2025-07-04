<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // Notification types
    const TYPE_CONTACT_SUBMISSION = 'contact_submission';
    const TYPE_INQUIRY = 'inquiry';
    const TYPE_VISIT_BOOKING = 'visit_booking';
    const TYPE_JOB_APPLICATION = 'job_application';

    // Icons for different notification types
    const ICONS = [
        self::TYPE_CONTACT_SUBMISSION => 'ki-filled ki-message-text-2',
        self::TYPE_INQUIRY => 'ki-filled ki-message-text-2',
        self::TYPE_VISIT_BOOKING => 'ki-filled ki-calendar-8',
        self::TYPE_JOB_APPLICATION => 'ki-filled ki-briefcase',
    ];

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function markAsRead()
    {
        $this->update([
            'read_at' => now(),
        ]);
    }

    public function getIconAttribute($value)
    {
        return $value ?? self::ICONS[$this->type] ?? 'ki-filled ki-notification-status';
    }

    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }
} 