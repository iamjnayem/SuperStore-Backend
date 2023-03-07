<?php

namespace App\Constants;

class PaymentStatus
{

    public const DUE = 0;
    public const PROCESSING = 1;
    public const DECLINED = 3;
    public const PAID = 9;
    public const REFUND = 8;

    //Alias Name
    public const ALIAS_DUE = 'Due';
    public const ALIAS_PROCESSING = 'Processing';
    public const ALIAS_DECLINED = 'Declined';
    public const ALIAS_PAID = 'Paid';
    public const ALIAS_REFUND = 'Refund';
}
