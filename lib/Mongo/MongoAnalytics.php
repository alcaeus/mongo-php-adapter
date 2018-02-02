<?php

trait MongoAnalytics {

    public function analyticalStore() {
      static $redis;
      if (!$redis) $redis = new Predis\Client();
      return $redis;

    }

    public function eavesdrop($opts = ['criteria' => 0, 'options' => 1], $fnc, $args) {
      if (isset($opts['readArgs']) && $opts['readArgs']) {
          $criteria = isset($opts['criteria']) ? $args[$opts['criteria']] ?? [] : [];
          $options = isset($opts['options']) ? ($args[$opts['options']] ?? []) : [];
          $op = is_array($fnc) && !isset($opts['operation']) ?
              ($fnc[1] ?? null) : (isset($opts['operation']) ? ($args[$opts['operation']] ?? null) : null);
      } else {
          $criteria = $opts['criteria'] ?? [];
          $options = $opts['options'] ?? [];
          $op = $opts['operation'] ?: null;
      }

      return $this->analyticalDataCapture($op ?: 'unknownOperation', $criteria, $options, $fnc);
    }

    private function analyticalNormalization(&$criteria) {
      if (is_array($criteria)) {
          array_walk($criteria, function (&$ref) {
            if (is_array($ref)) {
                $this->normalizecriteria($ref);
                return;
            }
            $ref = null;
          });
          uksort($criteria, strcasecmp($a, $b));
          return;
      }
      $criteria = null;
    }

    private function analyticalDataCapture($op = 'criteria', $criteria = [], $options = [], $call = null) {
        try {
            return $this->internalAnalyticalDataCapture($op, $criteria, $options, $call);
        } catch (Exception $e) {
            if ($call) return $call();
        }
        return function () { /* noop */ };
    }

    private function internalAnalyticalDataCapture($op = 'criteria', $criteria = [], $options = [], $call = null) {
        $payload = ["criteria" => $criteria, "options" => $options];
        $this->normalizecriteria($payload);
        $redis = $this->analyticalStore();

        $serialized = json_encode($criteria);
        $redisKey = sprintf("mongodb/%s/%s/%s", $this->name, $op, md5($serialized));

        $this->redis->incr("$redisKey/count");
        $this->redis->set("$redisKey/criteria", $serialized);

        $time = strtotime("+1 hour 00:00");
        $this->redis->expireat("$redisKey/count", $time);
        $this->redis->expireat("$redisKey/criteria", $time);

        $init = time();

        $onEnd = function () use ($redisKey, $init) {
          $this->redis->incr("$redisKey/execution", time() - $init);
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
