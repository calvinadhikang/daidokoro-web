<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OmakaseSession extends Model
{
    protected $table = 'omakase_session';
    protected $fillable = ['name', 'date'];

    public function omakaseMenus()
    {
        return $this->hasMany(OmakaseMenu::class);
    }
}
