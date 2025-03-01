<?php

namespace Athorrent\Backend;

enum BackendState: string {
    case Unknown = 'unknown';
    case Starting = 'starting';
    case Running = 'running';
    case Updating = 'updating';
    case Failed = 'failed';
    case Stopped = 'stopped';
}
