<?php

declare(strict_types=1);

final class Validator
{
    private array $errors = [];

    public function required(string $field, string $value, string $label): self
    {
        if ($value === '') {
            $this->errors[$field] = "$label is required.";
        }
        return $this;
    }

    public function maxLength(string $field, string $value, int $max, string $label): self
    {
        if (mb_strlen($value) > $max) {
            $this->errors[$field] = "$label may be at most $max characters.";
        }
        return $this;
    }

    public function email(string $field, string $value): self
    {
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field] = 'Please enter a valid email address.';
        }
        return $this;
    }

    public function phone(string $field, string $value): self
    {
        if ($value !== '' && !preg_match('/^\+?[0-9 ()-]{8,20}$/', $value)) {
            $this->errors[$field] = 'Please enter a valid phone number.';
        }
        return $this;
    }

    public function date(string $field, string $value): self
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            $this->errors[$field] = 'Please choose a valid date.';
        }
        return $this;
    }

    public function time(string $field, string $value): self
    {
        if (!preg_match('/^([01]\d|2[0-3]):(00|15|30|45)$/', $value)) {
            $this->errors[$field] = 'Please choose a valid time.';
        }
        return $this;
    }

    public function add(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
