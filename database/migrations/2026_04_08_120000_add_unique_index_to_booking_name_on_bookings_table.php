<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fyll tomma booking_name innan unik index läggs på
        $bookings = DB::table('bookings')
            ->whereNull('booking_name')
            ->orWhere('booking_name', '')
            ->get();

        foreach ($bookings as $booking) {
            DB::table('bookings')
                ->where('id', $booking->id)
                ->update([
                    'booking_name' => 'BOK-' . now()->format('Ymd') . '-' . strtoupper(substr(md5($booking->id . microtime()), 0, 6)),
                ]);
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_name', 255)->nullable(false)->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unique('booking_name', 'bookings_booking_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_booking_name_unique');
        });
    }
};


