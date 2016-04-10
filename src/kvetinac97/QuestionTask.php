<?php

// Scheduled questions
// © kvetinac97 2016

namespace kvetinac97;

use pocketmine\scheduler\Task;

class QuestionTask extends Task{
 
    protected $plugin;
 
    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }
 
    public function onRun($t){
        $this->plugin->endQuiz();
    }
 
}

