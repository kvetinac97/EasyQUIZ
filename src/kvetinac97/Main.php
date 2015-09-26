<?php

// EasyQUIZ plugin
// © kvetinac97 2015

namespace kvetinac97;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\utils\Config;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;

class Main extends PluginBase implements Listener{
  
 public $questions;
 public $cfg;
 public $msg;
 private $curr_aw;
 protected $quiz_players;
 
 public function onEnable(){
     
  $this->getServer()->getPluginManager()->registerEvents($this,$this);
  $this->curr_aw = null;
  $this->quiz_players = [];
  
  $this->saveDefaultConfig();
  $this->cfg = new Config($this->getDataFolder()."config.yml",Config::YAML);
  $this->saveResource("questions.yml");
  $this->questions = new Config($this->getDataFolder()."questions.yml");
  if ($this->cfg->get("language") == "fr"){
   $this->saveResource("French.yml");
   $this->msg = new Config($this->getDataFolder()."French.yml",Config::YAML);
   $this->getLogger()->info(TextFormat::GREEN."Langue choisi: Français");
  }
  elseif ($this->cfg->get("language") == "de"){
   $this->saveResource("German.yml");
   $this->msg = new Config($this->getDataFolder()."German.yml",Config::YAML);
   $this->getLogger()->info(TextFormat::GREEN."Language selected: German");
  }
  elseif ($this->cfg->get("language") == "cs"){
   $this->saveResource("Czech.yml");
   $this->msg = new Config($this->getDataFolder()."Czech.yml",Config::YAML);
   $this->getLogger()->info(TextFormat::GREEN."Vybraný jazyk: Čeština");
  }
  else {
   $this->saveResource("English.yml");
   $this->msg = new Config($this->getDataFolder()."English.yml",Config::YAML);
   $this->getLogger()->info(TextFormat::GREEN."Language selected: English");   
  }
  
  $this->getLogger()->info(TextFormat::DARK_GREEN."EasyQUIZ ENABLED!");
  $this->getLogger()->info(TextFormat::YELLOW."Running version 1.0.0");
  
  if ($this->cfg->get("auto_start") === true){
   $this->getServer()->getScheduler()->scheduleDelayedTask(new PostQuestionTask($this),$this->cfg->get("auto_interval")*20*60);   
  } 
  
 }
    
 public function onDisable(){
  $this->getLogger()->info(TextFormat::RED."EasyQUIZ DISABLED!"); 
 }  
  
 public function onCommand(CommandSender $sd, Command $cmd, $label, array $args){
  if ($cmd->getName() == "eq"){
   if ($sd->hasPermission("eq.command")){
    if (!(isset($args[0]))){
     if ($this->curr_aw === null){
     $this->newQuiz();
     return true;
     }
     else {
      $sd->sendMessage($this->getMsg("quiz_running"));   
      return true;
     }
    }
    else {
     $sd->sendMessage($this->getMsg("too_many_arguments")); 
     return true;
    }
   }
   else {
    $sd->sendMessage($this->getMsg("permission_eq_command")); 
    return true;
   }
  }
  if ($cmd->getName() == "aw"){
   if ($sd->hasPermission("eq.answer")){
    if (!($sd instanceof ConsoleCommandSender)){
     if ($this->curr_aw !== null){
       $typed = \implode(" ",$args); 
        foreach ($this->curr_aw as $question => $answers){
         foreach ($answers as $answer){
         if (stripos($answer,"CORRECT_") !== false){         
          if ($typed == \str_replace("CORRECT_","",$answer)){
           $sd->sendMessage($this->getMsg("answered"));
           $temp_array = [$sd->getName() => "correct"];
           $this->quiz_players = array_merge($temp_array,$this->quiz_players);
           return true;
          }
          else {
           $sd->sendMessage($this->getMsg("answered"));  
           $temp_array = [$sd->getName() => "wrong"];
           $this->quiz_players = array_merge($temp_array,$this->quiz_players);
           return true;
          }
         }
         return true;
        }
         return true;
        }
        return true;
       }
     else {
      $sd->sendMessage($this->getMsg("quiz_not_running"));    
      return true;
     }
     return true;
     }
    else {
     $sd->sendMessage($this->getMsg("not_for_console"));  
     return true;
    }
    return true;
   }
   else {
    $sd->sendMessage($this->getMsg("permission_eq_answer"));
    return true;
   }
  }
  }
 
 public function newQuiz(){
  if ($this->curr_aw === null){
   $quiz_questions = array_rand($this->questions->getAll(),1);
   $this->getServer()->getScheduler()->scheduleDelayedTask(new QuestionTask($this),($this->cfg->get("time_for_answer")*20));
   foreach ($this->getServer()->getOnlinePlayers() as $p){
    $p->sendMessage(TextFormat::GREEN.$this->getMsg("quiz_info"));
    $question = $quiz_questions;
    $answers = $this->questions->get($question);
    $this->curr_aw = [$question => $answers];
    $p->sendMessage("-----------------------"); 
    $p->sendMessage(TextFormat::YELLOW.$question); 
    $p->sendMessage("-----------------------");
    $p->sendMessage($this->getMsg("avaible_answers"));
    foreach ($answers as $answer){
     $p->sendMessage(\str_replace("CORRECT_","",\str_replace("%ANSWER",$answer,$this->getMsg("answer_format"))));  
    }
    $p->sendMessage("-----------------------");
    $p->sendMessage($this->getMsg("how_to_answer")); 
   } 
  }
 }
 
 public function endQuiz(){
  $players_played = \count($this->quiz_players);
  foreach ($this->quiz_players as $player => $how){
   if ($how == "wrong"){
    unset($this->quiz_players[$player]);
    if ($this->cfg->get("lose_commands") !== false and !($player->hasPermission("eq.lose"))){
     foreach ($this->cfg->get("lose_commands") as $com){
      $this->getServer()->dispatchCommand(new ConsoleCommandSender,\str_replace("%PLAYER",$player,$com));   
     }
    }
   }
   elseif ($how == "correct"){
    if ($this->cfg->get("name_players") === true){
     $players_won_names = [];
    }
    if (\count($this->quiz_players) <= $this->cfg->get("winners")){
     foreach($this->quiz_players as $player => $how){
      $this->getServer()->getPlayerExact($player)->sendMessage($this->getMsg("win_quiz"));     
      if ($this->cfg->get("name_players") === true){
       $temp_array = [$player];
       $players_won_names = array_merge($temp_array,$players_won_names);
      }
      if ($this->cfg->get("win_commands") !== false){
       foreach ($this->cfg->get("win_commands") as $com){
        $this->getServer()->dispatchCommand(new ConsoleCommandSender,\str_replace("%PLAYER",$player,$com));   
       }
      }
     }   
    }
    else {
     $difference = ((\count($this->quiz_players)) - ($this->cfg->get("winners")));
     $sad_losers = array_rand($this->quiz_players,$difference);
     if (!(is_array($sad_losers))){
      $sad_losers = [$sad_losers];
     }
     foreach ($sad_losers as $loser){
      unset($this->quiz_players[$loser]);
      $this->getServer()->getPlayerExact($loser)->sendMessage($this->getMsg("sorry_lose"));
     }
     foreach($this->quiz_players as $player => $how){
      $this->getServer()->getPlayerExact($player)->sendMessage($this->getMsg("win_quiz"));
      if ($this->cfg->get("name_players") === true){
       $temp_array = [$player];
       $players_won_names = array_merge($temp_array,$players_won_names);
      }
      if ($this->cfg->get("win_commands") !== false){
       foreach ($this->cfg->get("win_commands") as $com){
        foreach ($this->quiz_players as $player => $how){
         $this->getServer()->getPluginManager()->dispatchCommand(new ConsoleCommandSender,\str_replace("%PLAYER",$player,$com));
        }   
       }
      }
     }
    }
   }
  }
  foreach ($this->getServer()->getOnlinePlayers() as $p){
   $p->sendMessage($this->getMsg("quiz_end"));  
  }
  if ($this->cfg->get("show_stats") === true){
   $players_lost = ($players_played - count($this->quiz_players));
   foreach ($this->getServer()->getOnlinePlayers() as $p){
    $p->sendMessage("-----------------");
    $p->sendMessage($this->getMsg("answers_were"));
    foreach ($this->curr_aw as $question => $answers){
     foreach ($answers as $answer){
      if (strpos($answer,"CORRECT_") !== false){
       $p->sendMessage(\str_replace("CORRECT_","",\str_replace("%ANSWER",$answer,\str_replace("%QUESTION",$question,$this->getMsg("answer_was")))));
      }
     }
    }
    $p->sendMessage("-----------------");
    $p->sendMessage(\str_replace("%NUMBER",$players_played,$this->getMsg("quiz_end_one")));    
    if ($this->cfg->get("name_winners") === true and \count($this->quiz_players) !== 0){
     $name = [];
     foreach ($this->quiz_players as $player => $how){
      $temp_array = [$player];
      $name = array_merge($temp_array,$name);
     }
     $names = implode(" ",$name);
     $p->sendMessage(\str_replace("%VARIABLE",$names,$this->getMsg("quiz_end_two")));
    }
    else {
     $p->sendMessage(\str_replace("%VARIABLE",count($this->quiz_players),$this->getMsg("quiz_end_two")));   
    }
    $p->sendMessage(\str_replace("%NUMBER",$players_lost,$this->getMsg("quiz_end_three")));
    $p->sendMessage("-----------------");
   }
  }
  $this->resetAll();
 }
 
 public function resetAll(){
  $this->curr_aw = null;
  $this->quiz_players = [];
 }
 
 public function getMsg($msg){
  return str_replace("&","§",$this->msg->get($msg));    
 }
 
}


