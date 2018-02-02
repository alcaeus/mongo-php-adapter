<?php

trait MongoAnalytics
{
    public function analyticalStore()
    {
        static $redis;
        if (!$redis) {
            $redis = new Predis\Client();
        }
        return $redis;
    }

    public function eavesdrop($opts = ['criteria' => 0, 'options' => 1], $fnc, $args = [])
    {
        if (isset($opts['readArgs']) && !$opts['readArgs']) {
            $criteria = $opts['criteria'] ?? [];
            $options = $opts['options'] ?? [];
            $op = $opts['operation'] ?: null;
            $name = $opts['name'] ?? $this->name;
        } else {
            $criteria = isset($opts['criteria']) ? $args[$opts['criteria']] ?? [] : [];
            $options = isset($opts['options']) ? ($args[$opts['options']] ?? []) : [];
            $op = is_array($fnc) && !isset($opts['operation']) ?
            ($fnc[1] ?? null) : (isset($opts['operation']) ? ($opts['operation'] ?? null) : null);
            $name = $opts['name'] ?? $this->name;
        }

        return $this->analyticalDataCapture($op ?: 'unknownOperation', $criteria, $options, $fnc, $name);
    }

    private function analyticalNormalization(&$criteria)
    {
        if (is_array($criteria)) {
            array_walk($criteria, function (&$ref) {
                if (is_array($ref)) {
                    $this->analyticalNormalization($ref);
                    return;
                }
                $ref = null;
            });
            ksort($criteria);
            return;
        }
        $criteria = null;
    }

    private function analyticalDataCapture($op = 'criteria', $criteria = [], $options = [], $call = null, $name = null)
    {
        $payload = ["criteria" => $criteria, "options" => $options];
        $this->analyticalNormalization($payload);
        $init = microtime(true);
        $serialized = json_encode($payload);
        $redisKey = sprintf("mongodb/%s/%s/%s", $name ?: $this->name, $op, md5($serialized));

        $onEnd = function () use ($serialized, $redisKey, $init) {
            $diff = ceil((microtime(true) - $init) * 1000);
            if ($diff < 10) return;

            $time = strtotime("+1 hour 00:00");
            $redis = $this->analyticalStore();

            $redis->incr("$redisKey/count");
            $redis->set("$redisKey/criteria", $serialized);
            $redis->expireat("$redisKey/count", $time);
            $redis->expireat("$redisKey/criteria", $time);
            $redis->setnx("$redisKey/time", 0);

            $redis->incrby("$redisKey/time", $diff);
            $redis->expireat("$redisKey/time", $time);
        };

        if ($call) {
            try {
                return $call();
            } finally {
                $onEnd();
            }
        }

        return $onEnd;
    }
}
