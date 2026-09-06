<?php

namespace App\Services;

class WorkflowConditionEvaluator
{
    /** @param array<int, array<string, mixed>> $conditions @param array<string, mixed> $data */
    public function passes(array $conditions, array $data): bool
    {
        foreach ($conditions as $condition) {
            $actual = data_get($data, $condition['field'] ?? '');
            $expected = $condition['value'] ?? null;
            $passes = match ($condition['operator'] ?? 'equals') {
                'equals' => $actual == $expected,
                'not_equals' => $actual != $expected,
                'in' => in_array($actual, (array) $expected, true),
                'greater_than' => $actual > $expected,
                'less_than' => $actual < $expected,
                'filled' => filled($actual),
                default => false,
            };
            if (! $passes) {
                return false;
            }
        }

        return true;
    }
}
