<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    protected $fillable = [
        'website_name',
        'logo',
        'logo_dark',
        'favicon',
        'qr_code_image',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'meta_author',
        'primary_email',
        'secondary_email',
        'primary_phone',
        'secondary_phone',
        'address',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'linkedin_url',
    ];
}
