<?php

namespace App\DTO;

enum SubscriptionStatus: string
{
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled'; // if user was subscribed but then canceled his subscription
    case PAST_DUE = 'past_due';
    case TRIAL_DUE = 'trial_due';
}
