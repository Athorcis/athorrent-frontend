<?php

namespace Athorrent;

class ErrorUtils
{
    private const string ERROR_GET_LAST_UNDEFINED = 'error_get_last undefined';

    public static function resetLastError(): void
    {
        @user_error(self::ERROR_GET_LAST_UNDEFINED);
    }

    /**
     * @return array{type: int, message: string, file: string, line: int}|null
     */
    public static function getLastError(): ?array
    {
        $error = error_get_last();

        if ($error && $error['message'] === self::ERROR_GET_LAST_UNDEFINED) {
            $error = null;
        }

        return $error;
    }

    public static function getLastErrorMessage(): ?string
    {
        $error = self::getLastError();

        if ($error === null) {
            return null;
        }

        return $error['message'];
    }
}
