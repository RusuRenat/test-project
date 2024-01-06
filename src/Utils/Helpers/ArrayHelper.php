<?php

namespace App\Utils\Helpers;

use DateTime;
use JsonException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ArrayHelper
{

    public static function formatDates(array $array, array $fields = ['dateUpdated', 'dateCreated', 'date'], string $format = 'Y-m-d H:i:s'): array
    {

        foreach ($fields as $iValue) {
            if (isset($array[$iValue])) {
                $array[$iValue] = is_object($array[$iValue]) ? $array[$iValue]->format($format) : $array[$iValue];
            }
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::formatDates($value, $fields, $format);
            }
        }

        return $array;
    }

    public static function filterArrayByKeys(array $array, string $fields = null, bool $multi = true): array
    {

        if (!$fields) {
            return $array;
        }

        $keys = array_flip(explode(',', $fields));

        if (!$multi) {
            return array_intersect_key($array, $keys);
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = array_intersect_key($value, $keys);
            }
        }

        return $array;
    }

    /**
     * @throws JsonException
     */
    public static function objectToArrayRecursive($data, array $elementToExclude = [])
    {

        $encoder = new JsonEncoder();
        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return $object->getId();
            },
            AbstractNormalizer::GROUPS => [
                'default'
            ]
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);

        $serializer = new Serializer([$normalizer], [$encoder]);

        $toExclude = array_merge($elementToExclude, ['__initializer__', '__cloner__', '__isInitialized__']);

        $dateFormat = static function ($innerObject) {
            return $innerObject instanceof DateTime ? $innerObject->format('Y-m-d H:i:s') : '';
        };

        $timeFormat = static function ($innerObject) {
            return $innerObject instanceof DateTime ? $innerObject->format('H:i:s') : '';
        };

        $defaultContext = [
            AbstractNormalizer::IGNORED_ATTRIBUTES => $toExclude,
            AbstractNormalizer::CALLBACKS => [
                'dateCreated' => $dateFormat,
                'dateUpdated' => $dateFormat,
                'date' => $dateFormat,
                'data' => $dateFormat,
                'publishDate' => $dateFormat,
                'startDate' => $dateFormat,
                'endDate' => $dateFormat,
                'authenticationDate' => $dateFormat,
                'shipToDate' => $dateFormat,
                'dateTerm' => $dateFormat
            ],
            AbstractNormalizer::GROUPS => [
                'default'
            ]
        ];

        return json_decode($serializer->serialize($data, 'json', $defaultContext), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function flattenConcatenateKeys(&$inputArray, $tmp = null, $name = '')
    {
        if ($tmp === null) {
            $tmp = $inputArray;
        }

        foreach ($tmp as $index => $value) {
            if (is_array($value)) {

                if ($name === '') {
                    self::flattenConcatenateKeys($inputArray, $value, $index);
                } else {
                    self::flattenConcatenateKeys($inputArray, $value, $name . '.' . $index);
                }

                if (isset($inputArray[$index])) {
                    unset($inputArray[$index]);
                }
            } else if ($name === '') {
                $inputArray[$index] = $value;
            } else {
                $inputArray[$name . '.' . $index] = $value;
            }
        }

        return $inputArray;
    }

    public static function buildTree(array &$elements, string $parentKey = 'parentId', string $childrenKey = 'pages', ?int $parentId = null, bool $sort = false): array
    {
        $branch = array();

        foreach ($elements as $element) {
            if ($element[$parentKey] === $parentId) {
                $children = self::buildTree($elements, $parentKey, $childrenKey, $element['id'], $sort);
                if ($children) {
                    $element[$childrenKey] = $children;
                    if ($sort) {
                        usort($element[$childrenKey], static fn($a, $b) => $a['priority'] <=> $b['priority']);
                    }
                }
                $branch[$element['id']] = $element;
            }
        }

        if ($sort) {
            usort($branch, static fn($a, $b) => $a['priority'] <=> $b['priority']);
        }

        return $branch;
    }

    public static function buildTreeMenu(array $elements, string $parentKey = 'parentId', string $childrenKey = 'pages', ?int $parentId = null, bool $sort = false, array $additionalObj = [], ?string $locale = null): array
    {
        $branch = array();
        foreach ($elements as $element) {
            if (isset($element['children'][0]) && is_array($element['children'][0])) {
                $children = self::buildTreeMenu($element['children'], $parentKey, $childrenKey, $element['id'], $sort, $additionalObj, $locale);
                $element[$childrenKey] = $children;
                if ($children) {
                    if ($sort) {
                        usort($element[$childrenKey], fn($a, $b) => $a['priority'] <=> $b['priority']);
                    }
                }
            }
            if ($parentId === $element['parent']) {
                if (isset($additionalObj[$element['entity']])) {
                    $branch[$element['id']] = self::getMenuFields($element, $childrenKey, $additionalObj[$element['entity']], $locale);
                }
            }
        }

        if ($sort) {
            usort($branch, static fn($a, $b) => $a['priority'] <=> $b['priority']);
        }

        return $branch;
    }

    public static function getMenuFields(array $element, string $childrenKey, array $additionalObj, ?string $locale = null): ?array
    {
        $response = [
            'id' => $element['id'],
            'entity' => $element['entity'],
            'priority' => $element['priority'],
            'parent' => $element['parent'] ?? null,
            $childrenKey => $element[$childrenKey],
            'translations' => $additionalObj['entity']['translations'],
        ];

        if (isset($additionalObj['entity']['slug'])) {
            $response['slug'] = $additionalObj['entity']['slug'];
        }

        if ($locale) {
            $response['title'] = $additionalObj['entity']['translations'][$locale]['title'];
            $response['module'] = null;
            if (isset($additionalObj['module'])) {
                $response['module'] = $additionalObj['module'];
            }
            unset($response['translations']);
        }

        return $response;
    }

    public static function sortArrayByKey(array $array, string $key = 'priority'): array
    {
        foreach ($array as $arr) {
            if (str_starts_with($key, '-')) {
                $keyTrim = ltrim($key, '-');
                usort($array, static fn($b, $a) => $a[$keyTrim] <=> $b[$keyTrim]);
            } else {
                usort($array, static fn($a, $b) => $a[$key] <=> $b[$key]);
            }
        }
        return $array;
    }

    public static function searchInArray(array $array, string $fieldToSearch, string $valueToSearch): bool|int|string
    {
        foreach ($array as $key => $value) {
            if ($value[$fieldToSearch] === $valueToSearch) {
                return $key;
            }
        }

        return false;
    }

    public function convertKeysToCamel(array $xs): array
    {
        $out = [];
        foreach ($xs as $key => $value) {
            $out[self::underToCamel((string)$key)] = is_array($value) ? $this->convertKeysToCamel($value) : $value;
        }

        return $out;
    }

    public static function underToCamel(string $str): string
    {
        return lcfirst(implode('', array_map('ucfirst', explode('_', $str))));
    }

    public static function flattenArray(array $array, string $key = 'pages'): array
    {
        $result = [];

        foreach ($array as $item) {

            $pages = $item[$key] ?? [];
            unset($item[$key]);

            $result[] = $item;

            if (!empty($pages)) {
                $result = array_merge($result, self::flattenArray($pages));
            }
        }

        return $result;
    }

    public static function getIndexedArray(array $arrays, string $key = 'id', bool $single = false, bool $object = false): array
    {
        return array_reduce($arrays, static function ($result, $array) use ($key, $single, $object) {
            if ($object) {
                $keys = explode('|', $key);
                $keysNumber = count($keys);
                if ($keysNumber > 1) {
                    $newArr = $array;
                    foreach ($keys as $value) {
                        $newArr = $newArr?->$value();
                    }
                    $arrayKey = $newArr;
                } else {
                    $arrayKey = $array?->$key();
                }
            } else {
                $arrayKey = $array[$key] ?? null;
            }

            if ($single) {
                $result[$arrayKey] = $array;

                return $result;
            }

            if (!isset($result[$arrayKey])) {
                $result[$arrayKey] = array();
            }

            $result[$arrayKey][] = $array;

            return $result;
        }, array());
    }

    public static function detectAction(array $data): array
    {
        if (isset($data['action'])) {
            $data = self::processAction($data);
        } else {
            foreach ($data as &$item) {
                $item = self::processAction($item);
            }
            unset($item);
        }

        return $data;
    }

    private static function processAction(array $item): array
    {
        if (isset($item['action'])) {
            $arr = explode("Controller::", $item['action']);
            $item['module'] = [
                'action' => str_replace('App\\Controller\\Public\\', '', $arr[0]),
                'name' => $arr[1]
            ];
            unset($item['action']);
        }

        return $item;
    }

    public static function getChildrenIds(mixed $object, array &$ids): void
    {
        if (is_object($object)) {
            foreach ($object->getChildren() as $child) {
                $ids[] = $child->getId();
                if ($child->getChildren()) {
                    self::getChildrenIds($child, $ids);
                }
            }
        }
        if (is_array($object)) {
            foreach ($object['children'] as $child) {
                $ids[] = $child['id'];
                if ($child['children']) {
                    self::getChildrenIds($child, $ids);
                }
            }
        }
    }

    public static function getIds(mixed $objects): array
    {
        if (is_object($objects)){
            return array_column(self::objectToArrayRecursive($objects), 'id');
        }
        return array_column($objects, 'id');
    }
}
