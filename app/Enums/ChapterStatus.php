<?php

namespace App\Enums;

enum ChapterStatus: string
{
    case Draft = 'draft';
    case Writing = 'writing';
    case Complete = 'complete';
}
