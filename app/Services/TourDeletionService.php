<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\GuideShift;
use App\Models\NotificationLog;
use App\Models\Tour;
use App\Models\TourPhoto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class TourDeletionService
{
    /**
     * @return array{bookings_deleted: int, photos_deleted: int}
     */
    public function delete(Tour $tour): array
    {
        return DB::transaction(function () use ($tour): array {
            $tour->load(['bookings', 'photos']);

            $bookingsDeleted = 0;
            $photosDeleted = 0;

            foreach ($tour->bookings as $booking) {
                $this->deleteBooking($booking);
                $bookingsDeleted++;
            }

            foreach ($tour->photos as $photo) {
                $this->deletePhotoFile($photo);
                $photo->delete();
                $photosDeleted++;
            }

            GuideShift::query()->where('tour_id', $tour->id)->delete();

            $tour->delete();

            return [
                'bookings_deleted' => $bookingsDeleted,
                'photos_deleted' => $photosDeleted,
            ];
        });
    }

    private function deleteBooking(Booking $booking): void
    {
        if (Schema::hasTable('notification_logs')) {
            NotificationLog::query()
                ->where('notifiable_type', Booking::class)
                ->where('notifiable_id', $booking->id)
                ->delete();
        }

        $booking->languages()->detach();
        $booking->delete();
    }

    private function deletePhotoFile(TourPhoto $photo): void
    {
        $path = $photo->path ?: $photo->image_path;

        if (! is_string($path) || $path === '') {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
