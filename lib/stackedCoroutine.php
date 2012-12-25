<?php

class CoroutineValueWrapper {
    protected $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }
}

class CoroutineReturnValue extends CoroutineValueWrapper { }
class CoroutinePlainValue extends CoroutineValueWrapper { }

function retval($value) {
    return new CoroutineReturnValue($value);
}

function plainval($value) {
    return new CoroutinePlainValue($value);
}

function stackedCoroutine(Generator $gen) {
    $stack = new SplStack;
    $exception = null;

    for (;;) {
        try {
            if ($exception) {
                $gen->throw($exception);
                $exception = null;
                continue;
            }

            $value = $gen->current();

            if ($value instanceof Generator) {
                $stack->push($gen);
                $gen = $value;
                continue;
            }

            $isReturnValue = $value instanceof CoroutineReturnValue;
            if (!$gen->valid() || $isReturnValue) {
                if ($stack->isEmpty()) {
                    return;
                }

                $gen = $stack->pop();
                $gen->send($isReturnValue ? $value->getValue() : NULL);
                continue;
            }

            $gen->send(yield $gen->key() => $value);
        } catch (Exception $e) {
            if ($exception !== null) {
                $gen = $this->stack->pop();
            }
            $exception = $e;
        }
    }
}
