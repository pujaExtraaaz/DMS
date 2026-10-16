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
            if (is_string($value) && $this->isUppercaseNameAttribute($key)) {
                $this->attributes[$key] = NameCase::format($value);
            }
        }
    }

    /**
     * Check if a given attribute key represents a name field on this model.
     */
    public function isUppercaseNameAttribute(string $key): bool
    {
        if (property_exists($this, 'uppercaseFields') && is_array($this->uppercaseFields)) {
            return in_array($key, $this->uppercaseFields, true);
        }

        if (property_exists($this, 'nameFields') && is_array($this->nameFields)) {
            return in_array($key, $this->nameFields, true);
        }

        return NameCase::isNameField($key);
    }

    /**
     * Intercept attribute access to guarantee existing and loaded records always return uppercase.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (is_string($value) && $this->isUppercaseNameAttribute($key)) {
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
            if (is_string($value) && $this->isUppercaseNameAttribute($key)) {
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
        if (is_string($value) && $this->isUppercaseNameAttribute($key)) {
            $value = NameCase::format($value);
        }

        return parent::setAttribute($key, $value);
    }
}
