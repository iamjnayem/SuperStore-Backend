<?php

namespace App\Constants;

class OrderStatus
{
    public const WAITING_FOR_ACCEPT = 0;
    public const ACCEPTED = 1;
    public const READY_FOR_PICK_UP = 2;
    public const DELIVERED = 3;
    public const CANCEL = 4;
    public const REFUND = 5;

    // Order status name
    public const ALIAS_WAITING_FOR_ACCEPT = "Waiting for Accept";
    public const ALIAS_ACCEPTED = "Accepted";
    public const ALIAS_READY_FOR_PICK_UP = "Ready for Pick-up";
    public const ALIAS_DELIVERED = "Delivered";
    public const ALIAS_CANCEL = "Canceled";
    public const ALIAS_REFUND = "Refund";

    // Order alias name
    public const STATUS_WAITING_FOR_ACCEPT = "pending";
    public const STATUS_ACCEPTED = "accepted";
    public const STATUS_READY_FOR_PICK_UP = "ready";
    public const STATUS_DELIVERED = "delivered";
    public const STATUS_CANCEL = "canceled";
    public const STATUS_REFUND = "refunded";

    // Order types
    public const ORDER_TYPE_PICKUP = "Pickup";
    public const ORDER_TYPE_DINEIN = "Dine-in";


}
