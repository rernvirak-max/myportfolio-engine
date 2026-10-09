<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cohorts', function (Blueprint $table) {
            $table->unsignedTinyInteger('installment_count')->nullable()->after('currency');
            $table->decimal('installment_amount', 10, 2)->nullable()->after('installment_count');
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('installment_amount');
            $table->decimal('early_bird_price', 10, 2)->nullable()->after('deposit_amount');
            $table->date('early_bird_until')->nullable()->after('early_bird_price');
            $table->unsignedSmallInteger('early_bird_seats')->nullable()->after('early_bird_until');
            $table->decimal('referral_discount', 10, 2)->nullable()->after('early_bird_seats');
        });
    }

    public function down(): void
    {
        Schema::table('cohorts', function (Blueprint $table) {
            $table->dropColumn([
                'installment_count',
                'installment_amount',
                'deposit_amount',
                'early_bird_price',
                'early_bird_until',
                'early_bird_seats',
                'referral_discount',
            ]);
        });
    }
};
