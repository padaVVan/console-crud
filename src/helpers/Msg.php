<?php

namespace padavvan\console\helpers;

use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;

/**
 * Class Msg
 * @package app\components
 */
class Msg extends BaseObject
{
    const ALERT_WARNING = 'warning';
    const ALERT_INFO = 'info';
    const ALERT_DANGER = 'danger';
    const ALERT_SUCCESS = 'success';

    /**
     * Шаблон сообщения
     * @var string
     */
    public $msg;

    /**
     * Параметры шаблона сообщения
     * @var array
     */
    public $params;

    /**
     * @var array
     */
    public $defaultParamConfig = [33];

    /**
     * @var string
     */
    public $template = '{icon}{msg}';

    /**
     * @var string
     */
    private $alertType = false;

    /**
     * @param $message
     * @param array $params
     * @param array $config
     * @return Msg
     */
    public static function create($message, array $params = [], array $config = [])
    {
        $config['msg'] = $message;
        $config['params'] = $params;

        return new self($config);
    }

    /**
     * @param $message
     * @param array $params
     * @param array $config
     * @return Msg
     */
    public static function warning($message, array $params = [], array $config = [])
    {
        $instance = self::create($message, $params, $config);

        return $instance->asWarning();
    }

    /**
     * @param $message
     * @param array $params
     * @param array $config
     * @return Msg
     */
    public static function info($message, array $params = [], array $config = [])
    {
        $instance = self::create($message, $params, $config);

        return $instance->asInfo();
    }

    /**
     * @param $message
     * @param array $params
     * @param array $config
     * @return Msg
     */
    public static function danger($message, array $params = [], array $config = [])
    {
        $instance = self::create($message, $params, $config);

        return $instance->asDanger();
    }

    /**
     * @param $message
     * @param array $params
     * @param array $config
     * @return Msg
     */
    public static function success($message, array $params = [], array $config = [])
    {
        $instance = self::create($message, $params, $config);

        return $instance->asSuccess();
    }

    /**
     * @param $message
     * @param array $params
     * @param array $config
     * @return Msg
     */
    public static function alert($message, array $params = [], array $config = [])
    {
        $type = ArrayHelper::remove($config, 'alertType', '');
        $instance = self::create($message, $params, $config);

        $instance->alertType = $type;

        return $instance;
    }

    /**
     * @return string
     */
    public function compile()
    {
        return $this->render();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $this->output();

        return '';
    }

    /**
     *
     */
    public function output()
    {
        Console::output($this->compile());
    }

    /**
     * @return $this
     */
    public function asWarning()
    {
        $this->alertType = self::ALERT_WARNING;

        return $this;
    }

    /**
     * @return $this
     */
    public function asInfo()
    {
        $this->alertType = self::ALERT_INFO;

        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function asType($value)
    {
        $this->alertType = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function asDanger()
    {
        $this->alertType = self::ALERT_DANGER;

        return $this;
    }

    /**
     * @return $this
     */
    public function asSuccess()
    {
        $this->alertType = self::ALERT_SUCCESS;

        return $this;
    }

    /**
     * @return string
     */
    public function render()
    {
        $icon = $this->createIcon($this->alertType);
        $msg = $this->format($this->msg, $this->params);

        $content = strtr($this->template, [
            '{icon}' => $icon,
            '{msg}' => $msg,
        ]);

        if ($this->alertType === false) {
            return $content;
        } else {
            return Console::wrapText($content, 5, true);
        }
    }

    /**
     * @param array $fromArray
     * @return Msg
     */
    public function setParams(array $fromArray)
    {
        $this->params = $fromArray;
        return $this;
    }

    /**
     * @param null $type
     * @return string
     */
    private function createIcon($type = null)
    {
        if ($type === false) {
            return null;
        }

        switch ($type) {
            case 'success':
                $icon = Console::ansiFormat("  ✔  ", [Console::FG_GREEN]);
                break;
            case 'danger':
                $icon = Console::ansiFormat("  ✖  ", [Console::FG_RED]);
                break;
            case 'warning':
                $icon = Console::ansiFormat("  !  ", [Console::FG_YELLOW]);
                break;
            case 'info':
                $icon = Console::ansiFormat("  -  ", [Console::FG_GREY]);
                break;
            default:
                $type = $type ?: ' ';
                $icon = Console::ansiFormat("  {$type}  ", [Console::FG_GREY]);
                break;
        }

        return $icon;
    }

    /**
     * @param $tpl
     * @param array $params
     * @return string
     */
    private function format($tpl, $params = [])
    {
        $params = (array)$params;
        foreach ($params as $key => $param) {
            $options = $this->defaultParamConfig;
            $value = $param;

            if (is_array($param)) {
                $value = ArrayHelper::remove($param, 0);
                $options = $param;
            }

            if ($options) {
                $params[$key] = Console::ansiFormat($value, $options);
            } else {
                $params[$key] = $value;
            }
        }

        array_unshift($params, $tpl);

        $output = call_user_func_array('sprintf', $params);

        return $output;
    }

    public static function str($message, $params = [], $config = []) {
        $instance = self::create($message, $params, $config);
        return $instance->asType(false);
    }

    /**
     * @param int $size
     * @param array $config
     * @param string $symbol
     * @param bool $return
     * @return string
     */
    public static function delimiter($size = 80, $config = [], $symbol = '-')
    {
        array_unshift($config, str_repeat($symbol, floor($size / strlen($symbol))));

        return self::str('%s', [$config]);
    }
}
