<?php namespace ostark\AsyncQueue;

use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var integer
     */
    public $concurrency;

    /**
     * @var integer
     */
    public $poolLifetime;

    /**
     * @var bool
     */
    public $enabled = true;


    /**
     * Settings constructor.
     */
    public function __construct(array $config = [])
    {
        $config = array_merge([
            'concurrency'  => (int)$this->env('ASYNC_QUEUE_CONCURRENCY', 2),
            'poolLifetime' => (int)$this->env('ASYNC_QUEUE_POOL_LIFETIME', 3600),
            'enabled'      => ($this->env('DISABLE_ASYNC_QUEUE', '0') == '1') ? false : true
        ], $config);

        parent::__construct($config);
    }

    /**
     * Env var access with default
     *
     * @param mixed  $default
     *
     */
    protected function env(string $name, $default = null): false|string
    {
        if (getenv($name) === false) {
            return (string)$default;
        }

        return getenv($name);
    }

}
