<?php

namespace App\Services;

use App\Models\ThreeDObject;

class ThreeDObjectService
{
    public function get3DVisibilityConfig()
    {
        $objects = ThreeDObject::with('triggers')->get();

        return
            $objects->map(fn($obj) => [
                'id' => $obj->id,
                'name' => $obj->name,
                'obj_name' => $obj->obj_name,
                'triggers' => $obj->triggers->map(fn($trigger) => [
                    'trigger_type' => $trigger->trigger_type,
                    'trigger_id' => $trigger->trigger_id,
                ]),
            ]);
    }
}
