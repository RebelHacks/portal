<?php

namespace App\Enum;

enum ApplicationStatus: string
{
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Waitlisted = 'waitlisted';
    case Rejected = 'rejected';
}
