<?php

namespace App\Support\Traits;

use App\Support\NameCase;

trait HasUppercaseNames
{
    /**
     * Boot the trait to automatically uppercase name attributes on model saving.
     */
    public static function bootHasUppercaseNames(): void
    {
        static::saving(function ($model) {
            $model->uppercaseNameAttributes();
        });
    }

    /**
     * Uppercase all name attributes currently loaded in the model's attribute array.
     */
    public function uppercaseNameAttributes(): void
    {
        foreach ($this->attributes as $key => $value) {
            if (is_string($value) && $this->isUppercaseNameAttribute($key) && ! $this->isJsonPayloadString($value)) {
                $this->attributes[$key] = NameCase::format($value);
            }
        }
    }

    /**
     * Check if a given attribute key represents a name field on this model.
     */
    public function isUppercaseNameAttribute(string $key): bool
    {
        // Never uppercase attributes that are cast to array, json, collection, object, etc.
        if (method_exists($this, 'hasCast') && $this->hasCast($key, [
            'array', 'json', 'object', 'collection', 'encrypted:array', 'encrypted:collection', 'encrypted:object'
        ])) {
            return false;
        }

        if (method_exists($this, 'getCasts')) {
            $casts = $this->getCasts();
            if (isset($casts[$key])) {
                $cast = strtolower((string) $casts[$key]);
                if (in_array($cast, ['array', 'json', 'object', 'collection'], true) || str_contains($cast, 'json') || str_contains($cast, 'array')) {
                    return false;
                }
            }
        }

        if (property_exists($this, 'uppercaseFields') && is_array($this->uppercaseFields)) {
            return in_array($key, $this->uppercaseFields, true);
        }

        if (property_exists($this, 'nameFields') && is_array($this->nameFields)) {
            return in_array($key, $this->nameFields, true);
        }

        return NameCase::isNameField($key);
    }

    /**
     * Determine if a string is a valid JSON payload string (which must never be title-cased).
     */
    protected function isJsonPayloadString(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || (! str_starts_with($trimmed, '{') && ! str_starts_with($trimmed, '['))) {
            return false;
        }

        if (function_exists('json_validate')) {
            return json_validate($trimmed);
        }

        json_decode($trimmed);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Intercept attribute access to guarantee existing and loaded records always return uppercase.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (is_string($value) && $this->isUppercaseNameAttribute($key) && ! $this->isJsonPayloadString($value)) {
            return NameCase::format($value);
        }

        return $value;
    }

    /**
     * Convert array representation so toArray and toJson serialization format names in uppercase.
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        foreach ($attributes as $key => $value) {
            if (is_string($value) && $this->isUppercaseNameAttribute($key) && ! $this->isJsonPayloadString($value)) {
                $attributes[$key] = NameCase::format($value);
            }
        }

        return $attributes;
    }

    /**
     * Intercept attribute setting to guarantee assigned values are converted immediately to uppercase.
     */
    public function setAttribute($key, $value)
    {
        if (is_string($value) && $this->isUppercaseNameAttribute($key) && ! $this->isJsonPayloadString($value)) {
            $value = NameCase::format($value);
        }

        return parent::setAttribute($key, $value);
    }
}
