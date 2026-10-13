<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Throwable;

/**
 * A reporting date range built from a preset or a custom from/to pair.
 */
class DateRange
{
    public const PRESETS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'This week',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'last_30_days' => 'Last 30 days',
        'custom' => 'Custom range',
    ];

    public function __construct(
        public CarbonImmutable $from,
        public CarbonImmutable $to,
        public string $key,
        public string $label,
    ) {}

    public static function fromRequest(Request $request, string $default = 'this_month'): self
    {
        $key = (string) $request->query('range', $default);

        if ($key === 'custom') {
            try {
                $from = CarbonImmutable::parse((string) $request->query('from'))->startOfDay();
                $to = CarbonImmutable::parse((string) $request->query('to'))->endOfDay();

                if ($from->greaterThan($to)) {
                    [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
                }

                // Keep reports bounded.
                if ($from->diffInDays($to) > 731) {
                    $from = $to->subDays(731)->startOfDay();
                }

                return new self($from, $to, 'custom', $from->format('M j, Y').' - '.$to->format('M j, Y'));
            } catch (Throwable) {
                $key = $default;
            }
        }

        return self::preset(array_key_exists($key, self::PRESETS) ? $key : $default);
    }

    public static function preset(string $key): self
    {
        $now = CarbonImmutable::now();

        [$from, $to] = match ($key) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'yesterday' => [$now->subDay()->startOfDay(), $now->subDay()->endOfDay()],
            'this_week' => [$now->startOfWeek(), $now->endOfDay()],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'last_30_days' => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
            default => [$now->startOfMonth(), $now->endOfDay()],
        };

        $key = array_key_exists($key, self::PRESETS) && $key !== 'custom' ? $key : 'this_month';

        return new self($from, $to, $key, self::PRESETS[$key]);
    }

    /**
     * @return array<int, string> Y-m-d for every day in the range
     */
    public function days(): array
    {
        $days = [];
        $cursor = $this->from->startOfDay();

        while ($cursor->lessThanOrEqualTo($this->to) && count($days) < 732) {
            $days[] = $cursor->format('Y-m-d');
            $cursor = $cursor->addDay();
        }

        return $days;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function bounds(): array
    {
        return [$this->from->format('Y-m-d H:i:s'), $this->to->format('Y-m-d H:i:s')];
    }
}
