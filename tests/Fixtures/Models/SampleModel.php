<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SampleModel extends Model
{
    public function posts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PostModel::class);
    }

    public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProfileModel::class);
    }
}
