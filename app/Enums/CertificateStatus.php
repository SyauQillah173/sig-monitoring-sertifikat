<?php

namespace App\Enums;

enum CertificateStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case ExpiringSoon = 'expiring_soon';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
