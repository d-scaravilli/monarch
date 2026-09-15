<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
     * The goals set for this specific enrollment (person + course + year)
     * — in creation order, which doubles as their sequence along the
     * year's "percorso".
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class)->oldest();
    }

    /**
     * Attendances for lessons that happened on or after this enrollment
     * started. A lesson before the member joined the course is one they
     * couldn't possibly have attended, so it must never count toward
     * their presence/absence stats — everywhere those are shown reads
     * this instead of the raw `attendances` relation. Expects
     * `attendances.lesson` to already be eager-loaded.
     *
     * @return Collection<int, Attendance>
     */
    public function validAttendances(): Collection
    {
        return $this->attendances->filter(fn (Attendance $a) => $a->lesson->date->gte($this->enrollment_date));
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

    /**
     * What's owed for this specific enrollment: the course's per-cycle
     * cost (monthly or annual, matching how this enrollment was
     * registered) plus the course's one-time enrollment fee if any,
     * minus this enrollment's discount. Never negative. This is the one
     * place course-cost math happens — the Palestra dashboard, the
     * Contabilità page and the scheda iscritto all read it from here so
     * the numbers can never drift apart.
     */
    public function dueAmount(): float
    {
        $course = $this->course;
        $cycleCost = $this->billing_frequency === 'monthly' ? $course->monthly_cost : $course->annual_cost;
        $due = (float) $cycleCost + (float) ($course->enrollment_cost ?? 0) - (float) $this->discount;

        return max(0.0, round($due, 2));
    }

    public function paidAmount(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function balance(): float
    {
        return round($this->paidAmount() - $this->dueAmount(), 2);
    }

    /**
     * 'missing', 'even' or 'overpaid' — a small tolerance absorbs float
     * rounding so a balance of e.g. 0.001 still reads as "even".
     */
    public function balanceStatus(): string
    {
        return match (true) {
            $this->balance() < -0.005 => 'missing',
            $this->balance() > 0.005 => 'overpaid',
            default => 'even',
        };
    }
}
