<?php

function normalizeProductSpecifications($names, $values, &$error)
{
    $error = '';
    if (!is_array($names) || !is_array($values)) {
        $error = 'Thông số kỹ thuật không hợp lệ.';
        return [];
    }
    if (count($names) !== count($values)) {
        $error = 'Thông số kỹ thuật không hợp lệ.';
    }
    if (count($names) > 50 && $error === '') {
        $error = 'Mỗi sản phẩm chỉ được có tối đa 50 thông số kỹ thuật.';
    }

    $specifications = [];
    foreach (array_slice($names, 0, 50, true) as $index => $rawName) {
        $rawValue = $values[$index] ?? '';
        if (!is_scalar($rawName) || !is_scalar($rawValue)) {
            if ($error === '') {
                $error = 'Thông số kỹ thuật không hợp lệ.';
            }
            continue;
        }
        $name = trim((string)$rawName);
        $value = trim((string)$rawValue);
        if ($name === '' && $value === '') {
            continue;
        }
        if ($name === '' || $value === '') {
            if ($error === '') {
                $error = 'Vui lòng nhập đầy đủ tên và giá trị cho từng thông số.';
            }
            $specifications[] = ['name' => $name, 'value' => $value];
            continue;
        }
        if (mb_strlen($name, 'UTF-8') > 100 || mb_strlen($value, 'UTF-8') > 2000) {
            if ($error === '') {
                $error = 'Tên thông số tối đa 100 ký tự và giá trị tối đa 2.000 ký tự.';
            }
        }
        $specifications[] = ['name' => $name, 'value' => $value];
    }

    return $specifications;
}

function decodeProductSpecifications($serialized, $cpu = null, $ram = null)
{
    $specifications = json_decode((string)$serialized, true);
    if (!is_array($specifications)) {
        $specifications = [];
    }

    if (!$specifications) {
        foreach (['Bộ xử lý (CPU)' => $cpu, 'Bộ nhớ RAM' => $ram] as $name => $value) {
            if (trim((string)$value) !== '') {
                $specifications[] = ['name' => $name, 'value' => (string)$value];
            }
        }
    }

    return array_values(array_filter($specifications, static function ($specification) {
        return is_array($specification)
            && isset($specification['name'], $specification['value'])
            && trim((string)$specification['name']) !== ''
            && trim((string)$specification['value']) !== '';
    }));
}
