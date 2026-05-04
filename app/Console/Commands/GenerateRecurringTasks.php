<?php

namespace App\Console\Commands;

use App\Enums\TaskFrequency;
use App\Models\RecurringTask;
use App\Models\Task;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Signature('app:generate-recurring-tasks')]
#[Description('Generate recurring tasks')]
class GenerateRecurringTasks extends Command
{
    public function handle()
    {
        $targetDate = today();

        $recurringTaskQuery = RecurringTask::query()
            ->where(fn (Builder $query) => $query->whereNull('start_date')->orWhere('start_date', '<=', $targetDate))
            ->where(fn (Builder $query) => $query->where('end_date', '>=', $targetDate)->orWhereNull('end_date'))
            ->whereDoesntHave('tasks', fn ($q) => $q->whereDate('task_date', $targetDate));

        $totalRecurringTasks = $recurringTaskQuery->count();

        if (! $totalRecurringTasks) {
            $this->info('No active recurring tasks found.');

            return self::FAILURE;
        }

        $this->info('Processing '.$totalRecurringTasks.' recurring task templates...');

        $created = 0;
        $skipped = 0;

        $recurringTaskQuery->chunkById(
            250,
            function (Collection $recurringTasks) use ($targetDate, $skipped, $created) {

                try {
                    $insertTasksBatch = [];
                    foreach ($recurringTasks as $recurringTask) {
                        try {

                            if (! $this->isRecurringTaskDue($recurringTask, $targetDate)) {
                                $skipped++;

                                continue;
                            }

                            $insertTasksBatch[] = [
                                'uuid' => (string) Str::uuid(),
                                'user_id' => $recurringTask->user_id,
                                'category_id' => $recurringTask->category_id,
                                'title' => $recurringTask->title,
                                'description' => $recurringTask->description,
                                'recurring_task_id' => $recurringTask->id,
                                'task_date' => $targetDate,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        } catch (\Exception $e) {
                            report($e);
                        }
                    }

                    if ($insertTasksBatch) {
                        Task::insert($insertTasksBatch);

                        $created += count($insertTasksBatch);
                    }
                } catch (\Exception $e) {
                    report($e);
                }
            }
        );

        $this->info('Created '.$created.' recurring Tasks.');

        if ($skipped > 0) {
            $this->warn('Skipped '.$skipped.' recurring Tasks.');
        }

        $this->newLine();

        return self::SUCCESS;
    }

    private function isRecurringTaskDue(RecurringTask $recurringTask, Carbon $targetDate): bool
    {

        return match ($recurringTask->frequency) {
            TaskFrequency::Daily => true,
            TaskFrequency::Weekdays => $targetDate->isWeekday(),
            TaskFrequency::Weekly => $this->isWeeklyRecurringTaskDue($recurringTask, $targetDate),
            TaskFrequency::Monthly => $this->isMonthlyRecurringTaskDue($recurringTask, $targetDate),
            default => false,
        };
    }

    private function isWeeklyRecurringTaskDue(RecurringTask $recurringTask, Carbon $targetDate): bool
    {
        $config = $recurringTask->frequency_config;

        if (! $config || ! isset($config['days']) || ! is_array($config['days'])) {
            return false;
        }

        return in_array(strtolower($targetDate->englishDayOfWeek), $config['days']);
    }

    private function isMonthlyRecurringTaskDue(RecurringTask $recurringTask, Carbon $targetDate): bool
    {
        $config = $recurringTask->frequency_config;

        if (! $config || ! isset($config['days']) || ! is_array($config['days'])) {
            return false;
        }

        $dayOfMonth = min($config['day'], $targetDate->daysInMonth);

        return $targetDate->day === $dayOfMonth;
    }
}
