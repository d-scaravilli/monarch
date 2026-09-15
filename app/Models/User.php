<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\NewMessageNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'theme', 'avatar_path', 'disabled_at', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, HasRoles, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'disabled_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function memberProfile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function medicalCertificates(): HasMany
    {
        return $this->hasMany(MedicalCertificate::class);
    }

    /**
     * The generalized replacement for medicalCertificates() above, used
     * everywhere now (admin scheda iscritto and "La mia area" alike).
     * medicalCertificates() stays only for historical data already in
     * that table; nothing reads it anymore.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->latest('uploaded_at');
    }

    /**
     * Instructor-authored progress/injury notes about this member,
     * added from a lesson's attendance page.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(MemberNote::class)->latest();
    }

    public function instructedCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_instructor');
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(MessageRecipient::class);
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }

    /**
     * The messages table's cascadeOnDelete() only fires on a real SQL
     * DELETE — a soft delete is an UPDATE, so it never runs — meaning
     * every deletion path (single member, admin users page, bulk delete)
     * would otherwise leave this user's sent messages and message_recipients
     * rows (as sender or recipient) behind, orphaned and unresolvable.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            $sentMessageIds = $user->sentMessages()->pluck('id');

            MessageRecipient::whereIn('message_id', $sentMessageIds)
                ->orWhere('user_id', $user->id)
                ->delete();

            NewMessageNotification::deleteFor($sentMessageIds->all());
            Message::whereIn('id', $sentMessageIds)->delete();
        });
    }
}
