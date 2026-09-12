<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The one place that turns enrollments into money figures. The Palestra
 * dashboard's "da incassare" panel and the Contabilità page both build
 * their numbers from these methods, so the two can never disagree.
 */
class AccountingService
{
    /**
     * Active enrollments for a course year, with what they owe/have paid
     * pre-loaded so callers don't trigger N+1 queries.
     *
     * @param  Collection<int, int>|null  $courseIds
     * @return Collection<int, Enrollment>
     */
    public function enrollmentsForYear(?string $year, ?Collection $courseIds = null): Collection
    {
        return Enrollment::query()
            ->where('status', 'active')
            ->whereHas('course', fn ($q) => $q->when($year, fn ($q2) => $q2->where('year', $year)))
            ->when($courseIds, fn ($q) => $q->whereIn('course_id', $courseIds))
            ->with(['user', 'course.discipline', 'payments'])
            ->get();
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     * @return array{expected: float, collected: float, missing: float, percent: int}
     */
    public function totals(Collection $enrollments): array
    {
        $expected = round($enrollments->sum(fn (Enrollment $e) => $e->dueAmount()), 2);
        $collected = round($enrollments->sum(fn (Enrollment $e) => $e->paidAmount()), 2);
        $missing = round($enrollments->sum(fn (Enrollment $e) => max(0, $e->dueAmount() - $e->paidAmount())), 2);
        $percent = $expected > 0 ? (int) round($collected / $expected * 100) : ($collected > 0 ? 100 : 0);

        return compact('expected', 'collected', 'missing', 'percent');
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     * @return Collection<int, array{course: Course, expected: float, collected: float, missing: float}>
     */
    public function byCourse(Collection $enrollments): Collection
    {
        return $enrollments
            ->groupBy('course_id')
            ->map(fn (Collection $group) => [
                'course' => $group->first()->course,
                'expected' => round($group->sum(fn (Enrollment $e) => $e->dueAmount()), 2),
                'collected' => round($group->sum(fn (Enrollment $e) => $e->paidAmount()), 2),
                'missing' => round($group->sum(fn (Enrollment $e) => max(0, $e->dueAmount() - $e->paidAmount())), 2),
            ])
            ->sortByDesc('expected')
            ->values();
    }

    /**
     * People who still owe money, one row per person with totals summed
     * across every course they're enrolled in — never a single course
     * picked at random when someone has more than one.
     *
     * @param  Collection<int, Enrollment>  $enrollments
     * @return Collection<int, array{user: User, enrollments: Collection<int, Enrollment>, paid: float, missing: float}>
     */
    public function whoOwes(Collection $enrollments): Collection
    {
        return $enrollments
            ->groupBy('user_id')
            ->map(fn (Collection $group) => [
                'user' => $group->first()->user,
                'enrollments' => $group->values(),
                'paid' => round($group->sum(fn (Enrollment $e) => $e->paidAmount()), 2),
                'missing' => round($group->sum(fn (Enrollment $e) => max(0, $e->dueAmount() - $e->paidAmount())), 2),
            ])
            ->filter(fn (array $row) => $row['missing'] > 0.005)
            ->sortByDesc('missing')
            ->values();
    }

    /**
     * Collected totals by calendar month (real payment dates, since a
     * course's "year" is a free-text season label that doesn't
     * necessarily line up with the calendar) — for the incassi chart.
     *
     * @param  Collection<int, Enrollment>  $enrollments
     * @return array<string, float>
     */
    public function monthlyCollected(Collection $enrollments): array
    {
        $payments = $enrollments->flatMap(fn (Enrollment $e) => $e->payments);

        return $payments
            ->groupBy(fn ($payment) => $payment->date->format('Y-m'))
            ->sortKeys()
            ->mapWithKeys(fn ($group, $key) => [
                Carbon::createFromFormat('Y-m', $key)->translatedFormat('M Y') => round($group->sum('amount'), 2),
            ])
            ->toArray();
    }
}
