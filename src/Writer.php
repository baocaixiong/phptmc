<?php

namespace TMC;
use TMC\Types\HeaderType;
use TMC\Types\ValueType;
use TMC\Types\UNInteger;

/**
 * Class Writer
 * @package TMC
 */
class Writer
{
    /**
     * @param Message $message
     * @return string
     */
    public static function write(Message $message)
    {
        $buffer =  new WriteBuffer();
        $buffer->byte($message->getProtocolVersion());
        $buffer->byte($message->getMessageType());

        if ($message->getToken() !== null) {
            $buffer->int16(HeaderType::TOKEN);
            $buffer->string($message->getToken());
        }
        
        if ($message->getContent()) {
            $content = $message->getContent();
            foreach ($content as $key => $value) {
                $buffer->int16(HeaderType::CUSTOM);
                $buffer->string($key);
                static::writeCustomValue($buffer, $value);
            }
        }
        
        if ($message->getStatusCode() !== null) {
            $buffer->int16(HeaderType::STATUS_CODE);
            $buffer->int32($message->getStatusCode());
        }

        if ($message->getStatusPhrase() !== null) {
            $buffer->int16(HeaderType::STATUS_PHRASE);
            $buffer->string($message->getStatusPhrase());
        }

        if ($message->getFlag() !== null) {
            $buffer->int16(HeaderType::FLAG);
            $buffer->int32($message->getFlag());
        }

        $buffer->int16(HeaderType::END_OF_HEADERS);

        return $buffer->getBuffer();
    }

    /**
     * @param WriteBuffer $buffer
     * @param mixed $value
     */
    private static function writeCustomValue(WriteBuffer $buffer, $value)
    {
        if (! $value) {
            $buffer->byte(ValueType::VOID);
        }

        if (! is_int($value) && ! is_long($value) && ! is_float($value)) {
            $buffer->byte(ValueType::COUNTED_STRING);
            $buffer->string($value);
        } else {
            if ($value < UNInteger::BYTE) {
                $buffer->byte(ValueType::BYTE);
                $buffer->byte($value);
            } elseif ($value < UNInteger::INT16) {
                $buffer->byte(ValueType::INT16);
                $buffer->int16($value);
            } elseif ($value < UNInteger::INT32) {
                $buffer->byte(ValueType::INT32);
                $buffer->int32($value);
            } elseif ($value < UNInteger::INT64) {
                if (PHP_VERSION_ID >= 50600) {
                    $buffer->byte(ValueType::INT64);
                    $buffer->int64($value);
                } else {
                    $buffer->byte(ValueType::COUNTED_STRING);
                    $buffer->string((string) $value);
                }
            } else {
                $buffer->byte(ValueType::COUNTED_STRING);
                $buffer->string($value);
            }
        }
    }
}

