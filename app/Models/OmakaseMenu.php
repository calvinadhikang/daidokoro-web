<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OmakaseMenu extends Model
{
    protected $table = 'omakase_menu';
    protected $fillable = ['omakase_session_id', 'name'];

    public function omakaseSession()
    {
        return $this->belongsTo(OmakaseSession::class);
    }
}
