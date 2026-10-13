<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Key/value store for runtime settings. Read through SettingService (cached).
 */
#[Fillable(['key', 'value'])]
class Setting extends Model {}
