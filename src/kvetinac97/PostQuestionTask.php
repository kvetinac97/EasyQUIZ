<?php

// Scheduled question posting
// © kvetinac97 2016

namespace kvetinac97;

use pocketmine\scheduler\Task;

class PostQuestionTask extends Task{
 
    protected $plugin;
    
    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }
 
    public function onRun($t){
        $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new PostQuestionTask($this->plugin),$this->plugin->cfg->get("auto_interval")*20*60);
        $this->plugin->newQuiz();
    }
 
}

