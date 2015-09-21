<?php

// EasyQUIZ plugin
// © kvetinac97 2015

namespace kvetinac97;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\utils\Config;

use pocketmine\tile\Sign;
use pocketmine\tile\Tile;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;

use pocketmine\event\player\PlayerInteractEvent;

class Main extends PluginBase implements Listener{
  
 public $questions;
 public $cfg;
 public $msg;
 
 public function onEnable(){
     
  $this->getServer()->getPluginManager()->registerEvents($this,$this);
  
  $this->saveDefaultConfig();
  $this->cfg = new Config($this->getDataFolder()."config.yml",Config::YAML);
  if ($this->cfg->get("language") == "fr"){
   $this->saveResource("config_francais.yml");
   $this->cfg = new Config($this->getDataFolder()."config-francais.yml",Config::YAML);
   $this->saveResource("questions_francais.yml");
   $this->questions = new Config($this->getDataFolder()."questions-francais.yml",Config::YAML);
   $this->saveResource("French.yml");
   $this->msg = new Config($this->getDataFolder()."French.yml",Config::YAML);
   $this->getLogger()->info(TextFormat::GREEN."Langue choisi: Français");
  }
  elseif ($this->cfg->get("language") == "cs"){
   $this->saveResource("config_czech.yml");
   $this->cfg = new Config($this->getDataFolder()."config-czech.yml",Config::YAML);
   $this->saveResource("questions_czech.yml");
   $this->questions = new Config($this->getDataFolder()."questions-czech.yml",Config::YAML);
   $this->saveResource("Czech.yml");
   $this->msg = new Config($this->getDataFolder()."Czech.yml",Config::YAML);
   $this->getLogger()->info(TextFormat::GREEN."Vybraný jazyk: Čeština");
  }
  else {
   $this->saveResource("questions.yml");
   $this->questions = new Config($this->getDataFolder()."questions.yml",Config::YAML);
   $this->saveResource("English.yml");
   $this->msg = new Config($this->getDataFolder()."English.yml",Config::YAML);
   $this->getLogger()->info(TextFormat::GREEN."Language selected: English");   
  }
  
  $this->getLogger()->info(TextFormat::DARK_GREEN.$this->msg->get("plugin_enable"));
  $this->getLogger()->info(TextFormat::YELLOW.$this->msg->get("version_message"));
  
  if ($this->cfg->get("auto_start") === true){
   $this->getServer()->getScheduler()->scheduleRepeatingTask(new PostQuestionTask($this),$this->cfg->get("auto_interval"));   
  }
  
 }
    
 public function onDisable(){
  $this->getLogger()->info(TextFormat::RED.$this->msg->get("plugin_disable")); 
 }  
  
 public function onCommand(CommandSender $sd, Command $cmd, $label, array $args){
  if ($cmd->getName() == "eq"){
   if ($sd->hasPermission("eq.command")){
    if (!(isset($args[0]))){
     $this->newQuiz(); 
     return true;
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
 }
 
 public function newQuiz(){
  $quiz_questions   
 }
 
 public function getMsg($msg){
  return str_replace("&","§",$this->msg->get($msg));    
 }
 
}


