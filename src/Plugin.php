<?php
/**
 * AsyncQueue plugin for Craft CMS 3.x
 *
 * A queue handler that moves queue execution to a non-blocking background process
 *
 * @link      http://www.fortrabbit.com
 * @copyright Copyright (c) 2017 Oliver Stark
 */

namespace ostark\AsyncQueue;


use Craft;
use craft\base\Plugin as BasePlugin;
use craft\queue\BaseJob;
use craft\queue\Queue;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use yii\queue\PushEvent;


/**
 * AsyncQueue
 *
 * @author    Oliver Stark
 * @package   AsyncQueue
 * @since     1.0.0
 *
 */
class Plugin extends BasePlugin
{
    /**
     * Init plugin
     */
    public function init()
    {
        parent::init();

        // Listen to
        PushEvent::on(Queue::class, Queue::EVENT_AFTER_PUSH, function (PushEvent $event) {

            // Prevent frontend queue runner
            Craft::$app->getConfig()->getGeneral()->runQueueAutomatically = false;

            if ($event->job instanceof BaseJob) {
                Craft::info(
                    sprintf("Handling PushEvent for '%s' job", $event->job->getDescription()),
                    'craft-async-queue'
                );
            }

            // Run queue in the background
            $this->startBackgroundProcess();
        });

    }


    /**
     * Runs craft queue/run in the background
     */
    protected function startBackgroundProcess()
    {
        $cmd = $this->getCommand();
        $cwd = CRAFT_BASE_PATH;
 
        Craft::info(
            array(
                'cmd' => $cmd,
                'cwd' => $cwd
            ), 'craft-async-queue'
        );

        $process = new Process($cmd, $cwd);

        try {
            $process->run();
        } catch (\Exception $e) {
            Craft::info($e, 'craft-async-queue');
        }

        Craft::info(
            sprintf("Job status: %s. Exit code: %s",
                $process->getStatus(),
                $process->getExitCodeText() ?: '(not terminated)'
            ), 'craft-async-queue'
        );
    }


    /**
     * Construct queue command
     *
     * @return string
     */
    protected function getCommand()
    {
        $executableFinder = new PhpExecutableFinder();
        if (false === $php = $executableFinder->find(false)) {
            return null;
        } else {
            $cmd = array_merge(
                array('nice', $php),
                $executableFinder->findArguments(),
                array('craft', 'queue/run')
            );
            return $this->getBackgroundCommand(implode(' ', $cmd));
        }
    }


    /**
     * Extend command with background syntax
     *
     * @param string $cmd
     *
     * @return string
     */
    protected function getBackgroundCommand(string $cmd): string
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return 'start /B ' . $cmd . ' > NUL';
        } else {
            return $cmd . ' > /dev/null 2>&1 &';
        }
    }


}
