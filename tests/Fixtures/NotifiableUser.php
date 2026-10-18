<?php

namespace DanPascu\FilamentEmailCampaigns\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class NotifiableUser extends Model
{
    use Notifiable;

    protected $table = 'notifiable_users';

    protected $fillable = ['name'];
}
