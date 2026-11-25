<?php

namespace App\Support;

class SectionPayloadValidator
{
    /**
     * Validate a section payload against a section type's field definitions.
     *
     * @return array<string, string> errors keyed "payload.<dot.path>"
     */
    public static function validate(array $fields, ?array $payload): array
    {
        if (empty($payload)) {
            return [];
        }

        $errors = [];
        static::validateFields($fields, $payload, 'payload', false, $errors);

        return $errors;
    }

    private static function validateFields(array $fields, array $value, string $prefix, bool $enforceRequired, array &$errors): void
    {
        foreach ($fields as $field) {
            $name = $field['name'] ?? null;

            if ($name === null) {
                continue;
            }

            $path = $prefix . '.' . $name;

            if (! array_key_exists($name, $value)) {
                if ($enforceRequired && ! empty($field['required'])) {
                    $errors[$path] = 'Wajib diisi.';
                }

                continue;
            }

            $fieldValue = $value[$name];

            if (! empty($field['required']) && static::isEmpty($fieldValue)) {
                $errors[$path] = 'Wajib diisi.';

                continue;
            }

            static::validateValue($field, $fieldValue, $path, $errors);
        }
    }

    private static function validateValue(array $field, mixed $value, string $path, array &$errors): void
    {
        switch ($field['type'] ?? 'text') {
            case 'text':
            case 'textarea':
            case 'richtext':
                if (! is_string($value)) {
                    $errors[$path] = 'Harus berupa teks.';

                    return;
                }

                static::validateMax($field, $value, $path, $errors);

                return;

            case 'url':
                if (! is_string($value)) {
                    $errors[$path] = 'Harus berupa teks.';

                    return;
                }

                if (! preg_match('#^(https?://|/|\#)#', $value)) {
                    $errors[$path] = 'URL tidak valid.';

                    return;
                }

                static::validateMax($field, $value, $path, $errors);

                return;

            case 'image':
                if (! is_string($value)) {
                    $errors[$path] = 'Harus berupa path teks.';

                    return;
                }

                if (mb_strlen($value) > 255) {
                    $errors[$path] = 'Maksimal 255 karakter.';
                }

                return;

            case 'number':
                if (! is_int($value) && ! is_float($value)) {
                    $errors[$path] = 'Harus berupa angka.';
                }

                return;

            case 'boolean':
                if (! is_bool($value)) {
                    $errors[$path] = 'Harus berupa boolean.';
                }

                return;

            case 'select':
                if (! is_string($value) || ! in_array($value, $field['options'] ?? [], true)) {
                    $errors[$path] = 'Pilihan tidak valid.';
                }

                return;

            case 'list':
                if (! is_array($value) || ! array_is_list($value)) {
                    $errors[$path] = 'Harus berupa daftar.';

                    return;
                }

                foreach ($value as $index => $item) {
                    if (! is_string($item)) {
                        $errors["{$path}.{$index}"] = 'Harus berupa teks.';
                    }
                }

                return;

            case 'group':
                if (! is_array($value) || (array_is_list($value) && $value !== [])) {
                    $errors[$path] = 'Harus berupa objek.';

                    return;
                }

                static::validateFields($field['fields'] ?? [], $value, $path, true, $errors);

                return;

            case 'repeater':
                if (! is_array($value) || ! array_is_list($value)) {
                    $errors[$path] = 'Harus berupa daftar.';

                    return;
                }

                foreach ($value as $index => $item) {
                    $itemPath = "{$path}.{$index}";

                    if (! is_array($item) || (array_is_list($item) && $item !== [])) {
                        $errors[$itemPath] = 'Harus berupa objek.';

                        continue;
                    }

                    static::validateFields($field['fields'] ?? [], $item, $itemPath, true, $errors);
                }

                return;
        }
    }

    private static function validateMax(array $field, string $value, string $path, array &$errors): void
    {
        $max = $field['max'] ?? null;

        if (is_int($max) && mb_strlen($value) > $max) {
            $errors[$path] = "Maksimal {$max} karakter.";
        }
    }

    private static function isEmpty(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }
}
