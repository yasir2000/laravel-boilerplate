<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EmployeeDocument extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'employee_id',
        'document_type',
        'title',
        'description',
        'file_name',
        'file_size',
        'mime_type',
        'document_number',
        'issue_date',
        'expiry_date',
        'is_verified',
        'verified_by',
        'verified_at',
        'status',
        'access_level',
        'metadata'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'metadata' => 'array'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/jpg',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ])
            ->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('documents');

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->performOnCollections('documents');
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('documents');
    }

    public function getThumbUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('documents', 'thumb');
    }

    public function getPreviewUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('documents', 'preview');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast() === false && $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays($days));
    }

    public function scopeByAccessLevel($query, string $level)
    {
        return $query->where('access_level', $level);
    }

    public static function getDocumentTypes(): array
    {
        return [
            'id_card' => 'ID Card',
            'passport' => 'Passport',
            'drivers_license' => 'Driver\'s License',
            'birth_certificate' => 'Birth Certificate',
            'social_security' => 'Social Security Card',
            'visa' => 'Visa',
            'work_permit' => 'Work Permit',
            'resume' => 'Resume/CV',
            'diploma' => 'Diploma/Certificate',
            'transcript' => 'Academic Transcript',
            'employment_contract' => 'Employment Contract',
            'offer_letter' => 'Offer Letter',
            'bank_details' => 'Bank Account Details',
            'tax_documents' => 'Tax Documents',
            'insurance_card' => 'Insurance Card',
            'emergency_contact' => 'Emergency Contact Form',
            'medical_certificate' => 'Medical Certificate',
            'background_check' => 'Background Check',
            'reference_letter' => 'Reference Letter',
            'other' => 'Other'
        ];
    }

    public static function getAccessLevels(): array
    {
        return [
            'public' => 'Public',
            'internal' => 'Internal',
            'confidential' => 'Confidential',
            'restricted' => 'Restricted'
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            'archived' => 'Archived'
        ];
    }
}