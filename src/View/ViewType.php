<?php

declare(strict_types=1);

namespace Athorrent\View;

enum ViewType: string
{
    case Page = 'page';
    case Fragment = 'fragment';
    case Dynamic = 'dynamic';
}
