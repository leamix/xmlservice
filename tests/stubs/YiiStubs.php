<?php

/**
 * Minimal stubs for Yii 1.x classes so the service files can be loaded
 * and tested without the full Yii framework being installed.
 */

class CException extends \RuntimeException {}

class CComponent
{
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        throw new CException('Property ' . get_class($this) . '.' . $name . ' is not defined.');
    }

    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            return $this->$setter($value);
        }
        throw new CException('Property ' . get_class($this) . '.' . $name . ' is read-only.');
    }

    public function __call($name, $parameters)
    {
        throw new CException(get_class($this) . ' does not have a method named "' . $name . '".');
    }
}

class FakeUrlManager
{
    public function createPathInfo(array $params, string $eq, string $sep): string
    {
        $parts = [];
        foreach ($params as $k => $v) {
            $parts[] = urlencode($k) . $eq . urlencode($v);
        }
        return implode($sep, $parts);
    }
}

class FakeApp
{
    private FakeUrlManager $urlManager;

    public function __construct()
    {
        $this->urlManager = new FakeUrlManager();
    }

    public function getUrlManager(): FakeUrlManager
    {
        return $this->urlManager;
    }
}

class Yii
{
    private static ?FakeApp $app = null;

    public static function trace(string $msg, string $category): void {}

    public static function app(): FakeApp
    {
        if (self::$app === null) {
            self::$app = new FakeApp();
        }
        return self::$app;
    }
}
