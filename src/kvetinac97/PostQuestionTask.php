<?php

// Scheduled question posting
// © kvetinac97 2015

namespace kvetinac97;

use pocketmine\scheduler\PluginTask;
use kvetinac97\Main;

class PostQuestionTask extends PluginTask{
 
 protected $plugin;   
    
 public function __construct(Main $plugin){
  $this->plugin = $plugin;
  parent::__construct($plugin);
 }
 
 public function onRun($t){
  $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new PostQuestionTask($this->plugin),$this->plugin->cfg->get("auto_interval")*20*60);
  $this->plugin->newQuiz();   
 }
 
}

