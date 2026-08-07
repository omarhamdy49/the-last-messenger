<?php

use App\Providers\AppServiceProvider;
use App\Providers\BlogServiceProvider;
use App\Providers\ContentServiceProvider;
use App\Providers\FavoriteServiceProvider;
use App\Providers\HadithServiceProvider;
use App\Providers\MediaServiceProvider;
use App\Providers\PeopleServiceProvider;
use App\Providers\QuranServiceProvider;
use App\Providers\SeerahServiceProvider;
use App\Providers\SharedServiceProvider;
use App\Providers\TopicsServiceProvider;
use App\Providers\TranslationServiceProvider;

return [
    AppServiceProvider::class,
    TranslationServiceProvider::class,
    BlogServiceProvider::class,
    ContentServiceProvider::class,
    QuranServiceProvider::class,
    SeerahServiceProvider::class,
    PeopleServiceProvider::class,
    HadithServiceProvider::class,
    MediaServiceProvider::class,
    SharedServiceProvider::class,
    FavoriteServiceProvider::class,
    TopicsServiceProvider::class,
];
