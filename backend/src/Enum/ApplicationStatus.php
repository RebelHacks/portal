<?php

namespace App\Enum;

enum ApplicationStatus: string
{
    case Submitted = 'submitted';
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Waitlisted = 'waitlisted';
    case Rejected = 'rejected';
}
