<?php

// Scheduled questions
// © kvetinac97 2015

namespace kvetinac97;

use pocketmine\plugin\PluginTask;
use kvetinac97\Main;

class QuestionTask extends PluginTask{
 
 protected $plugin;
 
 public function __construct(Main $plugin){
  $this->plugin = $plugin;
  parent::__construct($plugin);
 }
 
 public function onRun($t){
  $this->plugin->endQuiz();   
 }
 
}

