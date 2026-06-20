<?php

declare(strict_types=1);

namespace Athorrent\Backend;

enum BackendState: string {
    case Unknown = 'unknown';
    case Starting = 'starting';
    case Running = 'running';
    case Updating = 'updating';
    case Failed = 'failed';
    case Stopping = 'stopping';
    case Stopped = 'stopped';
}
