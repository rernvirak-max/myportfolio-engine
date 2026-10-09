<?php

namespace App\Http\Requests\Admin;

use App\Models\Cohort;
use Illuminate\Validation\Validator;

trait ValidatesCohortPaymentOptions
{
    /** @return array<string, list<string>> */
    protected function paymentOptionRules(bool $sometimes = false): array
    {
        $wrap = function (array $rules) use ($sometimes): array {
            return $sometimes ? array_merge(['sometimes'], $rules) : $rules;
        };

        return [
            'installment_count' => $wrap(['nullable', 'integer', 'min:2', 'max:12', 'required_with:installment_amount']),
            'installment_amount' => $wrap(['nullable', 'numeric', 'min:0', 'max:99999999.99', 'required_with:installment_count']),
            'deposit_amount' => $wrap(['nullable', 'numeric', 'min:0', 'max:99999999.99']),
            'early_bird_price' => $wrap(['nullable', 'numeric', 'min:0', 'max:99999999.99']),
            'early_bird_until' => $wrap(['nullable', 'date']),
            'early_bird_seats' => $wrap(['nullable', 'integer', 'min:1', 'max:500']),
            'referral_discount' => $wrap(['nullable', 'numeric', 'min:0', 'max:99999999.99']),
        ];
    }

    protected function addPaymentOptionChecks(Validator $validator, array $values): void
    {
        $validator->after(function (Validator $validator) use ($values) {
            $price = $values['price'] ?? null;
            $currency = $values['currency'] ?? 'USD';
            $seats = $values['seats'] ?? null;
            $earlyBirdPrice = $values['early_bird_price'] ?? null;
            $earlyBirdUntil = $values['early_bird_until'] ?? null;
            $earlyBirdSeats = $values['early_bird_seats'] ?? null;
            $deposit = $values['deposit_amount'] ?? null;
            $referral = $values['referral_discount'] ?? null;

            if ($earlyBirdPrice !== null && $earlyBirdPrice !== '') {
                if ($price === null || $price === '') {
                    $validator->errors()->add('early_bird_price', 'Set the class price before adding an early-bird price.');
                } elseif ((float) $earlyBirdPrice >= (float) $price) {
                    $validator->errors()->add('early_bird_price', 'Early-bird price must be less than the class price.');
                }
            }

            $needsEarlyBird = ($earlyBirdUntil !== null && $earlyBirdUntil !== '')
                || ($earlyBirdSeats !== null && $earlyBirdSeats !== '');

            if ($needsEarlyBird && ($earlyBirdPrice === null || $earlyBirdPrice === '')) {
                $validator->errors()->add('early_bird_price', 'Add an early-bird price for this offer.');
            }

            if ($earlyBirdSeats !== null && $earlyBirdSeats !== '' && $seats !== null && (int) $earlyBirdSeats > (int) $seats) {
                $validator->errors()->add('early_bird_seats', 'Early-bird seats can’t exceed class seats.');
            }

            if ($deposit !== null && $deposit !== '' && $price !== null && $price !== '' && (float) $deposit >= (float) $price) {
                $validator->errors()->add('deposit_amount', 'Deposit must be less than the class price.');
            }

            if ($referral !== null && $referral !== '' && $price !== null && $price !== '' && (float) $referral >= (float) $price) {
                $validator->errors()->add('referral_discount', 'Referral discount must be less than the class price.');
            }

            if (strtoupper((string) $currency) === 'KHR') {
                foreach ([
                    'price' => $price,
                    'installment_amount' => $values['installment_amount'] ?? null,
                    'deposit_amount' => $deposit,
                    'early_bird_price' => $earlyBirdPrice,
                    'referral_discount' => $referral,
                ] as $field => $amount) {
                    if ($amount === null || $amount === '') {
                        continue;
                    }

                    if (! $this->isWholeRielAmount($amount)) {
                        $validator->errors()->add($field, 'KHR amounts must be whole numbers (no decimals).');
                    }
                }
            }
        });
    }

    /** @return array<string, mixed> */
    protected function paymentValuesFromInput(): array
    {
        return [
            'price' => $this->input('price'),
            'currency' => $this->input('currency', 'USD'),
            'seats' => $this->input('seats'),
            'installment_count' => $this->input('installment_count'),
            'installment_amount' => $this->input('installment_amount'),
            'deposit_amount' => $this->input('deposit_amount'),
            'early_bird_price' => $this->input('early_bird_price'),
            'early_bird_until' => $this->input('early_bird_until'),
            'early_bird_seats' => $this->input('early_bird_seats'),
            'referral_discount' => $this->input('referral_discount'),
        ];
    }

    /** @return array<string, mixed> */
    protected function paymentValuesMergedWithCohort(Cohort $cohort): array
    {
        $fields = [
            'price',
            'currency',
            'seats',
            'installment_count',
            'installment_amount',
            'deposit_amount',
            'early_bird_price',
            'early_bird_until',
            'early_bird_seats',
            'referral_discount',
        ];

        $values = [];
        foreach ($fields as $field) {
            if ($this->exists($field)) {
                $values[$field] = $this->input($field);
            } else {
                $raw = $cohort->{$field};
                $values[$field] = $field === 'early_bird_until' && $raw !== null
                    ? $raw->toDateString()
                    : $raw;
            }
        }

        return $values;
    }

    private function isWholeRielAmount(mixed $amount): bool
    {
        if (! is_numeric($amount)) {
            return false;
        }

        return abs((float) $amount - round((float) $amount)) < 0.00001;
    }
}
