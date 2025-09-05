<?php

namespace RaheelShan\TypedView;


use Illuminate\View\Factory as BaseFactory;
use InvalidArgumentException;

class TypedViewFactory extends BaseFactory
{
    public function make($view, $data = [], $mergeData = [])
    {
        $path = $this->finder->find($view);
        $source = file_get_contents($path);

        preg_match_all('/@var\s+([^\s]+)\s+\$([a-zA-Z_][a-zA-Z0-9_]*)/', $source, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $expectedType = $match[1];
            $expectedVar  = $match[2];

            if (! array_key_exists($expectedVar, $data)) {
                throw new InvalidArgumentException(
                    "View [{$view}] expects variable \${$expectedVar} of type {$expectedType}, but it was not provided."
                );
            }

            $val = $data[$expectedVar];

            // Handle generics like Collection<Post>
            if (preg_match('/^(.+)<(.+)>$/', $expectedType, $genericParts)) {
                $outerType   = $genericParts[1];
                $genericType = $genericParts[2];

                if ($outerType === 'array') {
                    if (! is_array($val)) {
                        throw new InvalidArgumentException(
                            "View [{$view}] expects \${$expectedVar} to be array<{$genericType}>, got " . get_debug_type($val)
                        );
                    }

                    foreach ($val as $item) {
                        $checkItem = $item instanceof RestrictsColumns ? $item->getInnerModel() : $item;
                        if (! ($checkItem instanceof $genericType)) {
                            throw new InvalidArgumentException(
                                "View [{$view}] expects elements of \${$expectedVar} to be {$genericType}, got " . get_debug_type($item)
                            );
                        }
                    }
                } elseif (Str::endsWith($outerType, 'Collection')) {
                    if (! ($val instanceof \Illuminate\Support\Collection)) {
                        throw new InvalidArgumentException(
                            "View [{$view}] expects \${$expectedVar} to be {$outerType}<{$genericType}>, got " . get_debug_type($val)
                        );
                    }

                    foreach ($val as $item) {
                        $checkItem = $item instanceof RestrictsColumns ? $item->getInnerModel() : $item;
                        if (! ($checkItem instanceof $genericType)) {
                            throw new InvalidArgumentException(
                                "View [{$view}] expects elements of \${$expectedVar} to be {$genericType}, got " . get_debug_type($item)
                            );
                        }
                    }
                }
            } else {
                // Simple type
                $checkVal = $val instanceof RestrictsColumns ? $val->getInnerModel() : $val;

                if (! ($checkVal instanceof $expectedType)) {
                    throw new InvalidArgumentException(
                        "View [{$view}] expects \${$expectedVar} to be {$expectedType}, got " . get_debug_type($val)
                    );
                }
            }
        }

        return parent::make($view, $data, $mergeData);
    }
}