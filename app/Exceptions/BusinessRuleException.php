<?php

namespace App\Exceptions;

use RuntimeException;

/** A user-facing business rule violation (message is safe to display). */
class BusinessRuleException extends RuntimeException
{
    public function __construct(string $message, public array $errors = [])
    {
        parent::__construct($message);
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage(), 'errors' => $this->errors], 422);
        }

        return back()->withInput()->with('error', $this->getMessage());
    }
}
