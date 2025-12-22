<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveSetting extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'created_by'
    ];

    /**
     * Get the user who created the setting.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Helper to get a setting value.
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Helper to set a setting value.
     */
    public static function set($key, $value, $createdBy = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'created_by' => $createdBy ?? auth()->id()]
        );
    }
}
