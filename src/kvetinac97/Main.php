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
  $this->getLogger()->info(TextFormat::YELLOW."Running version 1.1.0");
  
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
      if ($this->cfg->get("answer_format") === true or !(isset($args[1]))){
      if ($this->cfg->get("answer_format") === true){
       $typed = \implode(" ",$args); 
      }
      else {
       $typed = $this->curr_aw[$args[0]];
      }
      if (\in_array($typed,$this->curr_aw["correct"])){
       $sd->sendMessage($this->getMsg("answered"));
       unset($this->quiz_players[$sd->getName()]);            
       $temp_array = [$sd->getName() => "correct"];
       $this->quiz_players = array_merge($temp_array,$this->quiz_players);
       return true;
      }
      else {
       $sd->sendMessage($this->getMsg("answered"));
       unset($this->quiz_players[$sd->getName()]);  
       $temp_array = [$sd->getName() => "wrong"];
       $this->quiz_players = array_merge($temp_array,$this->quiz_players);
       return true;      
      }
       return true;
      }
      else {
       $sd->sendMessage($this->getMsg("wrong_arguments"));
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
  if ($this->curr_aw === null and $this->questions->getAll() !== []){
   $quiz_questions = array_rand($this->questions->getAll(),1);
   $this->getServer()->getScheduler()->scheduleDelayedTask(new QuestionTask($this),($this->cfg->get("time_for_answer")*20));
   $question = $quiz_questions;
   $answers = ($this->questions->get($question));
   $this->curr_aw = [
    "question" => $question,
    "answers" => $answers,
    "correct" => []
    ];
   foreach ($this->getServer()->getOnlinePlayers() as $p){
    $p->sendMessage(TextFormat::GREEN.$this->getMsg("quiz_info"));
    $p->sendMessage("-----------------------"); 
    $p->sendMessage(TextFormat::YELLOW.$question); 
    if ($this->cfg->get("show_answers") === true){
     $p->sendMessage("-----------------------");
     $p->sendMessage($this->getMsg("avaible_answers"));
    } 
     foreach ($answers as $key => $answer){
      $this->curr_aw[$key+1] = \str_replace("CORRECT_","",$answer);
      if (stripos($answer,"CORRECT_") !== false){
       $replace = \str_replace("CORRECT_","",$answer);
       $this->curr_aw["correct"] = array_merge([$key => $replace],$this->curr_aw["correct"]);
      }
     if ($this->cfg->get("show_answers") === true){      
      if ($this->cfg->get("answer_format") === true){
       $p->sendMessage(\str_replace("CORRECT_","",\str_replace("%ANSWER",$answer,\str_replace("%NUMBER","- ",$this->getMsg("answer_format")))));
      }
      else {
       $p->sendMessage(\str_replace("CORRECT_","",\str_replace("%ANSWER",$answer,\str_replace("%NUMBER",(($key+1).")"),$this->getMsg("answer_format")))));
      }      
     }
     }
     $p->sendMessage("-----------------------"); 
    if ($this->cfg->get("answer_format") === true){
     $p->sendMessage($this->getMsg("how_to_answer"));
    }
    else {
     $p->sendMessage($this->getMsg("how_to_answer_two"));
    }
   } 
   } 
  else {
   $this->getLogger()->critical($this->getMsg("no_questions"));
   $this->getServer()->getPluginManager()->disablePlugin($this);
  }
 }
 
 public function endQuiz(){
  $players_played = \count($this->quiz_players);
  foreach ($this->quiz_players as $player => $how){
   if ($how == "wrong"){
    unset($this->quiz_players[$player]);
    if ($this->cfg->get("lose_commands") !== false and !($player->hasPermission("eq.lose"))){
    try {$this->getServer()->getPlayerExact($player)->sendMessage($this->getMsg("lose_quiz")); } catch(Exception $e){}    
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
     try { $this->getServer()->getPlayerExact($player)->sendMessage($this->getMsg("win_quiz")); } catch(Exception $e){}     
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
      try {$this->getServer()->getPlayerExact($loser)->sendMessage($this->getMsg("sorry_lose")); } catch(Exception $e){} 
     }
     foreach($this->quiz_players as $player => $how){
      try {$this->getServer()->getPlayerExact($player)->sendMessage($this->getMsg("win_quiz")); }catch(Exception $e){} 
      if ($this->cfg->get("name_players") === true){
       $temp_array = [$player];
       $players_won_names = array_merge($temp_array,$players_won_names);
      }
      if ($this->cfg->get("win_commands") !== false){
       foreach ($this->cfg->get("win_commands") as $com){
        foreach ($this->quiz_players as $player => $how){
         $this->getServer()->dispatchCommand(new ConsoleCommandSender,\str_replace("%PLAYER",$player,$com));
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
     foreach ($this->curr_aw["answers"] as $answer){
      if (stripos($answer,"CORRECT_") !== false){
      $p->sendMessage(\str_replace("CORRECT_","",\str_replace("%ANSWER",$answer,\str_replace("%QUESTION",$this->curr_aw["question"],$this->getMsg("answer_was_correct")))));
     }
     else {
      $p->sendMessage(TextFormat::RED.\str_replace("CORRECT_","",\str_replace("%ANSWER",$answer,\str_replace("%QUESTION",$this->curr_aw["question"],$this->getMsg("answer_was_wrong")))));
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
  if ($this->cfg->get("delete_question") === true){
   $this->questions->remove($this->curr_aw["question"]);
   $this->questions->save();
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


