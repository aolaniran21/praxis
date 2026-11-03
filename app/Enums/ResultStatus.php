<?php

namespace App\Enums;

enum ResultStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Rejected = 'rejected';
    case Approved = 'approved';
    case Published = 'published';
}
