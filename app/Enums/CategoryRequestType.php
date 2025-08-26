<?php

namespace App\Enums;

enum CategoryRequestType: string
{
    case CATEGORY = 'category';
    case SUBCATEGORY = 'subcategory';
    case BOTH = 'both';
}
