<?php

namespace ME\Efront\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ME\Models\User;

/**
 * A published storefront theme, kept so admins can preview or restore it later.
 */
class ThemeVersion extends Model
{
    /** How many versions are kept. */
    public const KEEP = 30;

    protected $table = 'efront_theme_versions';

    protected $fillable = ['settings', 'note', 'user_id'];

    protected $casts = ['settings' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
