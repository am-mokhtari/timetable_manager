<?php

namespace Amm\TimetableManager;

use Exception;

class Timetable
{
    private array $table;

    public function __construct()
    {
    }

    /**
     * @param int $weekday can be between 1 and 7
     * @param int $start_hour between 0 and 23
     * @param int $start_minute between 0 and 59
     * @param int $end_hour between 0 and 23
     * @param int $end_minute between 0 and 59
     * @return Timetable
     * @throws Exception
     */
    public function setDayTime(
        int $weekday,
        int $start_hour,
        int $start_minute,
        int $end_hour,
        int $end_minute
    ): Timetable
    {
//      Validation
        if (
            $this->weekday_validation($weekday) !== true ||
            $this->time_validation($start_hour, $start_minute, $end_hour, $end_minute) !== true
        ) {
            die();
        }

//      Set Values
        $this->table[$weekday] = [
            's_h' => $this->toTwoDigit($start_hour),
            's_m' => $this->toTwoDigit($start_minute),
            'e_h' => $this->toTwoDigit($end_hour),
            'e_m' => $this->toTwoDigit($end_minute)
        ];
        return $this;
    }

    /**
     * @param int $weekday
     * @return Timetable
     * @throws Exception
     */
    public function unsetDayTime(int $weekday): Timetable
    {
        if ($this->weekday_validation($weekday) !== true) {
            die();
        }
        unset($this->table[$weekday]);
        return $this;
    }

    /**
     * @param int $weekday
     * @return True
     * @throws Exception
     */
    private function weekday_validation(int $weekday): true
    {
        if ($weekday >= 1 && $weekday <= 7) {
            return true;
        } else {
            throw new Exception('The Entered Weekday Is Invalid.');
        }
    }

    /**
     * @param int $start_hour
     * @param int $start_minute
     * @param int $end_hour
     * @param int $end_minute
     * @return True
     * @throws Exception
     */
    private function time_validation(
        int $start_hour,
        int $start_minute,
        int $end_hour,
        int $end_minute
    ): true
    {
        if ($start_hour < 0 || $start_hour > 23) {
            throw new Exception('The Entered (Start Hour) Is Invalid');
        } elseif ($end_hour < 0 || $end_hour > 23) {
            throw new Exception('The Entered (End Hour) Is Invalid');
        } elseif ($start_minute < 0 || $start_minute > 59) {
            throw new Exception('The Entered (Start Minute) Is Invalid');
        } elseif ($end_minute < 0 || $end_minute > 59) {
            throw new Exception('The Entered (End Minute) Is Invalid');
        } elseif (
            ($end_hour * 60 + $end_minute) - ($start_hour * 60 + $start_minute) < 1) {
            throw new Exception('The (Start Time) Is Smaller Than (End Time)');
        } else {
            return true;
        }
    }

    /**
     * @param int $num
     * @return string
     */
    private function dayNum_to_dayName(int $num): string
    {
        return match ($num) {
            1 => 'saturday',
            2 => 'sunday',
            3 => 'monday',
            4 => 'tuesday',
            5 => 'wednesday',
            6 => 'thursday',
            7 => 'friday',
            default => '',
        };
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getSummarizedDaysTimes(): array
    {
        if (count($this->table) === 0) {
            throw new Exception('The Table Is Empty');
        }

        ksort($this->table);
        $allDays = $this->table;
        $sameDays = [];
        $result = [];


        foreach ($allDays as $key => $times) {
            $result[$key]['day'] = $this->dayNum_to_dayName($key);
            $result[$key]['s'] = $times['s_h'] . ':' . $times['s_m'];
            $result[$key]['e'] = $times['e_h'] . ':' . $times['e_m'];
        }


        foreach ($allDays as $baseKey => $baseDay) {
            foreach ($allDays as $comparingKey => $comparingDay) {
                if (
                    $baseKey < $comparingKey &&
                    $baseDay['s_h'] === $comparingDay['s_h'] &&
                    $baseDay['s_m'] === $comparingDay['s_m'] &&
                    $baseDay['e_h'] === $comparingDay['e_h'] &&
                    $baseDay['e_m'] === $comparingDay['e_m']
                ) {
                    $sameDays[$baseKey][] = $comparingKey;
                    unset($allDays[$comparingKey]);
                }
            }
        }
        /* deleted because it is incomplete */
        unset($allDays);


        foreach ($sameDays as $baseKey => $keys) {

            $consecutiveDays = 0;
            for ($i = 0; $i < count($keys); $i++) {
                if ($keys[$i] - ($baseKey + $i) === 1) {
                    $consecutiveDays++;
                }
            }


            if ($consecutiveDays > 1 && $consecutiveDays === count($keys)) {
                $result[$baseKey]['day'] = $result[$baseKey]['day'] . ' - ' . $this->dayNum_to_dayName(end($keys));

            } else {
                $title = $this->dayNum_to_dayName($baseKey);
                $oddDaysCount = 0;
                $evenDaysCount = 0;

                foreach ($keys as $key) {
                    if (
                        $this->dayNum_to_dayName($baseKey) === 'saturday' &&
                        in_array($this->dayNum_to_dayName($key), ['monday', 'wednesday'])
                    ) {
                        $evenDaysCount++;

                    } elseif (
                        $this->dayNum_to_dayName($baseKey) === 'sunday' &&
                        in_array($this->dayNum_to_dayName($key), ['tuesday', 'thursday'])
                    ) {
                        $oddDaysCount++;
                    }

                    $title .= ', ' . $this->dayNum_to_dayName($key);
                }

                if ($oddDaysCount > 1 && $oddDaysCount === count($keys)) {
                    $title = 'odd days';
                } elseif ($evenDaysCount > 1 && $evenDaysCount === count($keys)) {
                    $title = 'even days';
                }

                $result[$baseKey]['day'] = $title;

            }

            $result = $this->forget($result, $keys);
        }

        unset($sameDays, $consecutiveDays, $title, $oddDaysCount, $evenDaysCount, $keys, $key, $times, $baseKey, $baseDay, $comparingKey, $comparingDay);

        //  OUTPUT:
        return $result;
    }

    /**
     * @param $number
     * @return string
     */
    private function toTwoDigit($number): string
    {
        if ($number < 10)
            return '0' . $number;

        return $number;
    }

    /**
     * @param array $haystack
     * @param array $keys
     * @return array
     */
    private function forget(array $haystack, array $keys): array
    {
        foreach ($keys as $key) {
            unset($haystack[$key]);
        }
        return $haystack;
    }

    /**
     * @return array
     */
    public function getNamedArray(): array
    {
        $result = [];
        foreach ($this->table as $key => $times) {
            $result[$this->dayNum_to_dayName($key)] = $times;
        }
        unset($key, $times);
        return $result;
    }

    /**
     * @param string $timeSeparator
     * @return array
     */
    public function getSummarizedTimes(string $timeSeparator = ':'): array
    {
        $timeSeparator = htmlspecialchars(trim($timeSeparator));
        $result = [];

        foreach ($this->table as $key => $times) {
            $result[$this->dayNum_to_dayName($key)]['s'] = $times['s_h'] . $timeSeparator . $times['s_m'];

            $result[$this->dayNum_to_dayName($key)]['e'] = $times['e_h'] . $timeSeparator . $times['e_m'];
        }

        unset($key, $times);
        return $result;
    }
}


try {
    var_dump(
        (new Timetable())
            ->setDayTime(1, 5, 28, 5, 30)
            ->setDayTime(2, 5, 28, 5, 30)
            ->setDayTime(7, 5, 29, 5, 30)
            ->setDayTime(5, 5, 29, 5, 30)
            ->setDayTime(6, 5, 29, 5, 30)
            ->getSummarizedTimes()
    );
} catch (Exception $e) {
    echo $e->getMessage();
}