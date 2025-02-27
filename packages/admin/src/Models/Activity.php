<?php

namespace Lunar\Admin\Models;

use App\Models\Trait\HasTenant;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{    
    use HasTenant;
}