<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'enrollment_date',
        'discount',
        'status',
        'billing_frequency',
    ];

    protected function casts(): array
    {
        return [
            'enrollment_date' => 'date',
            'discount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Renewal cycle length in months: 12 for an annual subscription, 1 for
     * a monthly one. Everything else (progress bar, "days until renewal")
     * is derived from this single place so it never drifts between views.
     */
    private function cycleMonths(): int
    {
        return $this->billing_frequency === 'monthly' ? 1 : 12;
    }

    /**
     * Next renewal date: the enrollment's anniversary, rolled forward by
     * whole cycles until it lands in the future.
     */
    public function renewalDate(): Carbon
    {
        $next = $this->enrollment_date->copy();
        $months = $this->cycleMonths();

        while ($next->isPast()) {
            $next = $next->addMonths($months);
        }

        return $next;
    }

    public function renewalCycleStart(): Carbon
    {
        return $this->renewalDate()->copy()->subMonths($this->cycleMonths());
    }

    public function daysUntilRenewal(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->renewalDate(), false);
    }

    /**
     * How far through the current cycle we are, 0-100 — same "fill bar"
     * language used for room capacity elsewhere in the app.
     */
    public function renewalProgressPercent(): int
    {
        $start = $this->renewalCycleStart();
        $end = $this->renewalDate();

        $total = $start->diffInDays($end) ?: 1;
        $elapsed = $start->diffInDays(now());

        return (int) min(100, max(0, round($elapsed / $total * 100)));
    }
}
