<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Cohort extends Model
{
    public const FORMATS = ['online', 'in_person', 'hybrid'];

    public const STATUSES = ['draft', 'open', 'full', 'closed'];

    protected $fillable = [
        'course_id',
        'title',
        'start_date',
        'end_date',
        'schedule_text',
        'format',
        'seats',
        'min_students',
        'price',
        'currency',
        'installment_count',
        'installment_amount',
        'deposit_amount',
        'early_bird_price',
        'early_bird_until',
        'early_bird_seats',
        'referral_discount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'seats' => 'integer',
            'min_students' => 'integer',
            'price' => 'decimal:2',
            'installment_count' => 'integer',
            'installment_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'early_bird_price' => 'decimal:2',
            'early_bird_until' => 'date',
            'early_bird_seats' => 'integer',
            'referral_discount' => 'decimal:2',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(CourseEnquiry::class);
    }

    /** Confirmed seats (status = enrolled). Used for seats_left and early-bird seats. */
    public function enrolledCount(): int
    {
        if ($this->relationLoaded('enquiries')) {
            return $this->enquiries->where('status', 'enrolled')->count();
        }

        return $this->enquiries()->where('status', 'enrolled')->count();
    }

    /**
     * Requests that count toward opening the class (new + contacted + enrolled).
     * Exposed as enrolled_count in the API for the frontend progress UI.
     */
    public function openingRequestCount(): int
    {
        if ($this->relationLoaded('enquiries')) {
            return $this->enquiries->whereIn('status', ['new', 'contacted', 'enrolled'])->count();
        }

        return $this->enquiries()
            ->whereIn('status', ['new', 'contacted', 'enrolled'])
            ->count();
    }

    public function effectiveMinStudents(): int
    {
        if ($this->min_students !== null) {
            return (int) $this->min_students;
        }

        return (int) ($this->course?->min_students ?? 4);
    }

    public function seatsLeft(): int
    {
        return max(0, (int) $this->seats - $this->enrolledCount());
    }

    public function earlyBirdSeatsLeft(): ?int
    {
        if ($this->early_bird_seats === null) {
            return null;
        }

        return max(0, (int) $this->early_bird_seats - $this->enrolledCount());
    }

    public function earlyBirdActive(): bool
    {
        if ($this->early_bird_price === null || $this->price === null) {
            return false;
        }

        if ((float) $this->early_bird_price >= (float) $this->price) {
            return false;
        }

        if ($this->early_bird_until !== null) {
            $until = $this->early_bird_until instanceof Carbon
                ? $this->early_bird_until->toDateString()
                : (string) $this->early_bird_until;

            if (today()->toDateString() > $until) {
                return false;
            }
        }

        if ($this->early_bird_seats !== null && $this->earlyBirdSeatsLeft() <= 0) {
            return false;
        }

        return true;
    }

    public function effectivePrice(): ?string
    {
        $amount = $this->earlyBirdActive() ? $this->early_bird_price : $this->price;

        if ($amount === null) {
            return null;
        }

        return number_format((float) $amount, 2, '.', '');
    }
}
