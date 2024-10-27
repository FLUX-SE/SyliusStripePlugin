<?php

namespace FluxSE\SyliusStripePlugin\Provider;

enum AfterUrlTypeEnum: string
{
    case SUCCESS = 'success_url';
    case CANCEL = 'cancel_url';
    case ACTION = 'action_url';
}
