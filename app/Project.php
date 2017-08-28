<?php

namespace App;


use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Relations\BelongsTo;
use Jenssegers\Mongodb\Relations\HasMany;

class Project extends Model
{

    /**
     * @var string
     */
    protected $collection = 'projects';

    /**
     * @var string
     */
    protected $primaryKey = '_id';

    /**
     * @var array
     */
    protected $fillable = [
        'title',
        'user_id'
    ];

    /**
     * Project's user
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo {
        return $this->belongsTo('App\User');
    }

    /**
     * Project's goals
     *
     * @return HasMany
     */
    public function goals(): HasMany {
        return $this->hasMany('App\Goal');
    }

}