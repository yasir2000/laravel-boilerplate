<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class EmployeeDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hr_employee_documents';

    protected $fillable = [
        'id',
        'employee_id',
        'title',
        'description',
        'document_type',
        'file_name',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
        'uploaded_at'
    ];

    protected $casts = [
        'id' => 'string',
        'uploaded_at' => 'datetime',
        'file_size' => 'integer'
    ];

    protected $dates = ['uploaded_at', 'deleted_at'];

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Employee relationship
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Uploader relationship
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get human readable file size
     */
    public function getHumanFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file is an image
     */
    public function getIsImageAttribute()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Document type options
     */
    public static function getDocumentTypes()
    {
        return [
            'photo' => 'Profile Photo',
            'contract' => 'Employment Contract',
            'certificate' => 'Certificate/Diploma',
            'id_document' => 'ID Document',
            'resume' => 'Resume/CV',
            'other' => 'Other Document'
        ];
    }

    /**
     * Get icon class based on document type
     */
    public function getIconClassAttribute()
    {
        $icons = [
            'photo' => 'fa fa-image',
            'contract' => 'fa fa-file-text',
            'certificate' => 'fa fa-certificate',
            'id_document' => 'fa fa-id-card',
            'resume' => 'fa fa-file-pdf-o',
            'other' => 'fa fa-file'
        ];

        return $icons[$this->document_type] ?? 'fa fa-file';
    }
}