<?php

namespace Tests\Unit\Enum;

use App\Enums\TaskFrequency;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TaskFrequencyTest extends TestCase
{
    public static function frequenciesThatReturnNullConfig(): array
    {
        return [
            'daily' => [TaskFrequency::Daily],
            'weekdays' => [TaskFrequency::Weekdays]
        ];
    }
    #[Test]
    #[DataProvider('frequenciesThatReturnNullConfig')]
    public function frequency_returns_null_config(TaskFrequency $frequency): void
    {
        $config = $frequency->buildConfig([]);

        $this->assertNull($config);
    }

    #[Test]
    public function weekly_frequency_returns_null_without_days(): void
    {
        $config1 = TaskFrequency::Weekly->buildConfig([]);
        $config2 = TaskFrequency::Weekly->buildConfig(['foo' => 'nodays']);

        $this->assertNull($config1);
        $this->assertNull($config2);
    }

    #[Test]

    public function weekly_frequency_builds_config_with_days(): void
    {
        $config = TaskFrequency::Weekly->buildConfig(['days' => ['monday', 'wednesday', 'friday']]);

        $this->assertSame(['days' => ['monday', 'wednesday', 'friday']], $config);
    }

    #[Test]

    public function monthly_frequency_builds_config_with_day_of_month(): void
    {
        $config = TaskFrequency::Monthly->buildConfig(['day_of_month' => 15]);

        $this->assertSame(['day_of_month' => 15], $config);
    }
}
