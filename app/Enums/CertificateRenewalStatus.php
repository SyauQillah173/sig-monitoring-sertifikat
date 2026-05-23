<?php

namespace App\Enums;

enum CertificateRenewalStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Rejected = 'rejected';
}
